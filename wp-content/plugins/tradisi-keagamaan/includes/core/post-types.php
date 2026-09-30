<?php
/**
 * Struktur data: post type "tradisi", taxonomy, tags, dan template single.
 *
 *   Post type   tradisi            Label "Koleksi". URL mengikuti jenis:
 *                                  /tradisi/nama/ atau /budaya-material/nama/.
 *                                  Arsip /koleksi/ (TK_SLUG_KOLEKSI). Alamat lama
 *                                  atau salah awalan dialihkan otomatis.
 *                                  Mendukung revisi & author.
 *   Taxonomy    agama              hierarkis
 *               wilayah            hierarkis; level teratas = provinsi
 *               kategori-tradisi   hierarkis; boleh lebih dari satu per tradisi
 *               post_tag           tags bawaan WP sebagai "kata kunci"
 *               jenis              Tradisi / Budaya Material (includes/core/jenis.php)
 *   Template    templates/single-tradisi.php
 *
 * Setelah mengubah slug taxonomy di sini, buka Settings → Permalinks lalu klik Save.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', 'tk_register_data_types' );

/**
 * Daftarkan CPT "tradisi", ketiga taxonomy, dan hubungkan Tags.
 */
function tk_register_data_types() {

    // --- Post type: Koleksi (Tradisi & Budaya Material) ---------------------
    // Nama internal tetap "tradisi" (tersimpan di database); yang tampil "Koleksi".
    register_post_type( 'tradisi', array(
        'labels'       => array(
            'name'               => 'Koleksi',
            'singular_name'      => 'Koleksi',
            'menu_name'          => 'Koleksi',
            'add_new'            => 'Tambah Koleksi',
            'add_new_item'       => 'Tambah Koleksi Baru',
            'edit_item'          => 'Edit Koleksi',
            'new_item'           => 'Koleksi Baru',
            'view_item'          => 'Lihat Koleksi',
            'search_items'       => 'Cari Koleksi',
            'not_found'          => 'Belum ada koleksi.',
            'not_found_in_trash' => 'Tidak ada koleksi di tong sampah.',
            'all_items'          => 'Semua Koleksi',
        ),
        'public'       => true,
        'has_archive'  => false,
        'show_in_rest' => true, // Wajib untuk editor blok.
        'menu_icon'    => 'dashicons-book-alt',
        'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'author' ), // revisions: riwayat suntingan di Panel Kurator.
        // URL diatur sendiri per jenis: lihat tk_rewrite_jenis() & tk_permalink_jenis().
        'rewrite'      => false,
    ) );
    tk_rewrite_jenis();

    // --- Taxonomy: slug => array( nama jamak, nama tunggal ) ---------------
    // "wilayah" berisi provinsi; slug tetap "wilayah" agar data & URL tidak berubah.
    $taxonomies = array(
        'agama'            => array( 'Agama', 'Agama' ),
        'wilayah'          => array( 'Provinsi', 'Provinsi' ),
        'kategori-tradisi' => array( 'Kategori', 'Kategori' ),
    );

    foreach ( $taxonomies as $slug => $label ) {
        $args = array(
            'labels'       => array(
                'name'          => $label[0],
                'singular_name' => $label[1],
            ),
            'public'       => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite'      => array( 'slug' => $slug ),
        );

        // Kategori dipilih lewat field ACF per jenis (includes/core/jenis.php),
        // jadi panel bawaan di editor disembunyikan.
        if ( 'kategori-tradisi' === $slug ) {
            $args['show_in_rest']      = false;
            $args['meta_box_cb']       = false;
            $args['show_admin_column'] = true;
        }

        register_taxonomy( $slug, 'tradisi', $args );
    }

    // --- Tags bawaan WordPress sebagai "kata kunci" -------------------------
    register_taxonomy_for_object_type( 'post_tag', 'tradisi' );
}

/**
 * Aturan URL koleksi:
 *   /tradisi/nama/          koleksi berjenis Tradisi
 *   /budaya-material/nama/  koleksi berjenis Budaya Material
 *   /koleksi/               arsip semua koleksi (TK_SLUG_KOLEKSI)
 *   /koleksi/nama/          link lama → dialihkan ke alamat sesuai jenis
 *   /tradisi/, /budaya-material/  → dialihkan ke Jelajahi dengan filter jenis
 * Satu aturan per jenis (bukan rewrite tag), supaya awalan lain tidak ikut
 * tertangkap. Pengalihan: tk_alihkan_url_koleksi().
 */
function tk_rewrite_jenis() {
    foreach ( array_keys( TK_JENIS ) as $jenis ) {
        $awal = '^' . preg_quote( $jenis, '#' );
        add_rewrite_rule( $awal . '/([^/]+)/?$', 'index.php?tradisi=$matches[1]&tk_jenis_url=' . $jenis, 'top' );
        add_rewrite_rule( $awal . '/?$', 'index.php?tk_jenis_arsip=' . $jenis, 'top' );
    }
    add_rewrite_rule( '^' . TK_SLUG_KOLEKSI . '/?$', 'index.php?post_type=tradisi', 'top' );
    add_rewrite_rule( '^' . TK_SLUG_KOLEKSI . '/page/([0-9]+)/?$', 'index.php?post_type=tradisi&paged=$matches[1]', 'top' );
    add_rewrite_rule( '^' . TK_SLUG_KOLEKSI . '/([^/]+)/?$', 'index.php?tradisi=$matches[1]&tk_jenis_url=' . TK_SLUG_KOLEKSI, 'top' );
}

add_filter( 'query_vars', 'tk_query_var_jenis' );

/**
 * Query var URL koleksi:
 *   tk_jenis_url    awalan yang diminta (slug jenis, atau TK_SLUG_KOLEKSI untuk link lama)
 *   tk_jenis_arsip  /tradisi/ atau /budaya-material/ tanpa nama
 */
function tk_query_var_jenis( $vars ) {
    $vars[] = 'tk_jenis_url';
    $vars[] = 'tk_jenis_arsip';
    return $vars;
}

add_filter( 'post_type_link', 'tk_permalink_jenis', 10, 2 );

/**
 * Permalink koleksi terbit: /<slug jenis>/<nama>/. Draf & pratinjau tetap
 * memakai link bawaan (?tradisi=...), dan situs tanpa pretty permalink juga.
 *
 * @param string  $link
 * @param WP_Post $post
 * @return string
 */
function tk_permalink_jenis( $link, $post ) {
    if ( 'tradisi' !== $post->post_type || ! get_option( 'permalink_structure' )
        || ! $post->post_name || in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ), true ) ) {
        return $link;
    }
    return home_url( user_trailingslashit( tk_get_jenis( $post->ID ) . '/' . $post->post_name ) );
}

add_action( 'init', 'tk_flush_bila_url_berubah', 99 );

/**
 * Perbarui aturan URL sekali setiap struktur URL diubah (slug arsip atau
 * daftar jenis), supaya tidak perlu membuka Settings → Permalinks manual.
 */
function tk_flush_bila_url_berubah() {
    $struktur = TK_SLUG_KOLEKSI . '|' . implode( ',', array_keys( TK_JENIS ) ) . '|arsip-jenis';
    if ( get_option( 'tk_struktur_url' ) !== $struktur ) {
        flush_rewrite_rules( false );
        update_option( 'tk_struktur_url', $struktur );
    }
}

add_action( 'template_redirect', 'tk_alihkan_url_koleksi', 5 ); // Sebelum redirect_canonical (10).

/**
 * Pastikan setiap koleksi dibuka lewat alamat yang benar (301):
 *   - awalan tidak sesuai jenis (mis. jenis baru saja diganti) atau link lama
 *     /koleksi/nama/ → alamat sesuai jenis;
 *   - /tradisi/ atau /budaya-material/ saja → Jelajahi dengan filter jenis.
 */
function tk_alihkan_url_koleksi() {
    $arsip = get_query_var( 'tk_jenis_arsip' );
    if ( isset( TK_JENIS[ $arsip ] ) ) {
        wp_safe_redirect( add_query_arg( 'tipe', $arsip, home_url( '/' ) ) . '#jelajahi', 301 );
        exit;
    }

    $diminta = get_query_var( 'tk_jenis_url' );
    if ( $diminta && is_singular( 'tradisi' ) && tk_get_jenis( get_queried_object_id() ) !== $diminta ) {
        wp_safe_redirect( get_permalink( get_queried_object_id() ), 301 );
        exit;
    }
}

add_filter( 'single_template', 'tk_single_tradisi_template' );

/**
 * Pakai templates/single-tradisi.php untuk halaman detail tradisi.
 *
 * @param string $template Path template bawaan tema.
 * @return string Path template yang dipakai.
 */
function tk_single_tradisi_template( $template ) {
    if ( is_singular( 'tradisi' ) ) {
        $custom = TK_PATH . 'templates/single-tradisi.php';
        if ( file_exists( $custom ) ) {
            return $custom;
        }
    }
    return $template;
}
