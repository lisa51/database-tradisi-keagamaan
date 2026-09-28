<?php
/**
 * Struktur data: post type "tradisi", taxonomy, tags, dan template single.
 *
 *   Post type   tradisi            /tradisi/nama-tradisi/  (mendukung revisi & author)
 *   Taxonomy    agama              hierarkis
 *               wilayah            hierarkis; level teratas = provinsi
 *               kategori-tradisi   hierarkis; boleh lebih dari satu per tradisi
 *               post_tag           tags bawaan WP sebagai "kata kunci"
 *   Template    templates/single-tradisi.php
 *
 * Setelah mengubah slug di sini, buka Settings → Permalinks lalu klik Save.
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

    // --- Post type: Tradisi -------------------------------------------------
    register_post_type( 'tradisi', array(
        'labels'       => array(
            'name'          => 'Tradisi',
            'singular_name' => 'Tradisi',
            'add_new_item'  => 'Tambah Tradisi Baru',
            'edit_item'     => 'Edit Tradisi',
            'all_items'     => 'Semua Tradisi',
        ),
        'public'       => true,
        'has_archive'  => true,
        'show_in_rest' => true, // Wajib untuk editor blok.
        'menu_icon'    => 'dashicons-book-alt',
        'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'author' ), // revisions: riwayat suntingan di Panel Kurator.
        'rewrite'      => array( 'slug' => 'tradisi' ),
    ) );

    // --- Taxonomy: slug => array( nama jamak, nama tunggal ) ---------------
    $taxonomies = array(
        'agama'            => array( 'Agama', 'Agama' ),
        'wilayah'          => array( 'Wilayah', 'Wilayah' ),
        'kategori-tradisi' => array( 'Kategori Tradisi', 'Kategori' ),
    );

    foreach ( $taxonomies as $slug => $label ) {
        register_taxonomy( $slug, 'tradisi', array(
            'labels'       => array(
                'name'          => $label[0],
                'singular_name' => $label[1],
            ),
            'public'       => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite'      => array( 'slug' => $slug ),
        ) );
    }

    // --- Tags bawaan WordPress sebagai "kata kunci" -------------------------
    register_taxonomy_for_object_type( 'post_tag', 'tradisi' );
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
