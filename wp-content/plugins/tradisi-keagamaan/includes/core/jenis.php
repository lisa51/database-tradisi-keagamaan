<?php
/**
 * Jenis konten: Tradisi atau Budaya Material.
 *
 *   Tradisi          sesuatu yang DILAKUKAN: ritual, upacara, tarian, kebiasaan.
 *   Budaya Material  sesuatu yang BERWUJUD: benda, bangunan/situs, naskah, makanan.
 *
 * Keduanya memakai post type "tradisi" yang sama (form, kurasi, peta, filter
 * ikut berlaku). Jenis disimpan di taxonomy "jenis" (untuk filter & hitungan),
 * dan diisi lewat field ACF "Jenis" (jenis_warisan) di grup Detail Tradisi,
 * supaya field lain bisa tampil/sembunyi mengikuti pilihan (conditional logic).
 * Field itu tidak menyimpan nilainya sendiri: dibaca dari & ditulis ke taxonomy.
 *
 * Daftar jenis: TK_JENIS (config.php).
 *
 * KATEGORI PER JENIS
 *   Setiap term "kategori-tradisi" punya jenis (term meta tk_jenis, diisi di
 *   Koleksi → Kategori Tradisi → edit kategori). Di grup Detail Tradisi ada
 *   dua field Kategori (satu per jenis) yang tampil mengikuti field "Jenis"
 *   dan hanya berisi kategori jenis itu. Field yang tersembunyi tidak terkirim,
 *   jadi saat jenis diganti, kategori jenis lama ikut terhapus.
 *   Panel taxonomy bawaan "Kategori Tradisi" di editor disembunyikan.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', 'tk_jenis_register' );

/**
 * Daftarkan taxonomy "jenis" dan pastikan term-nya ada.
 * Panelnya disembunyikan di editor: jenis dipilih lewat field ACF.
 */
function tk_jenis_register() {
    register_taxonomy( 'jenis', 'tradisi', array(
        'labels'            => array(
            'name'          => 'Jenis',
            'singular_name' => 'Jenis',
        ),
        'public'            => true,
        'hierarchical'      => false,
        'show_in_rest'      => false, // Tidak tampil di sidebar editor blok.
        'meta_box_cb'       => false, // Tidak tampil di editor klasik.
        'show_admin_column' => true,  // Kolom "Jenis" di daftar Semua Tradisi.
        'rewrite'           => array( 'slug' => 'jenis' ),
    ) );

    // Pastikan term ada, sekali setiap daftar TK_JENIS berubah (bukan tiap request).
    $versi = implode( ',', array_keys( TK_JENIS ) );
    if ( get_option( 'tk_jenis_terms' ) !== $versi ) {
        foreach ( TK_JENIS as $slug => $label ) {
            if ( ! term_exists( $slug, 'jenis' ) ) {
                wp_insert_term( $label, 'jenis', array( 'slug' => $slug ) );
            }
        }
        update_option( 'tk_jenis_terms', $versi );
    }
}

/**
 * Jenis sebuah tradisi. Tanpa term dianggap "tradisi".
 * Memakai cache term (get_the_terms), jadi murah dipanggil berulang.
 *
 * @param int $id
 * @return string Slug dari TK_JENIS.
 */
function tk_get_jenis( $id ) {
    $terms = get_the_terms( $id, 'jenis' );
    return ( $terms && ! is_wp_error( $terms ) && isset( TK_JENIS[ $terms[0]->slug ] ) ) ? $terms[0]->slug : 'tradisi';
}

add_filter( 'acf/load_value/name=jenis_warisan', 'tk_jenis_load_value', 10, 2 );

/**
 * Field "Jenis" menampilkan term taxonomy "jenis" yang tersimpan.
 *
 * @param mixed      $value
 * @param int|string $post_id
 * @return mixed
 */
function tk_jenis_load_value( $value, $post_id ) {
    return is_numeric( $post_id ) && 'tradisi' === get_post_type( $post_id ) ? tk_get_jenis( $post_id ) : $value;
}

add_filter( 'acf/update_value/name=jenis_warisan', 'tk_jenis_update_value', 10, 2 );

/**
 * Pilihan di field "Jenis" disimpan sebagai term taxonomy "jenis".
 *
 * @param mixed      $value
 * @param int|string $post_id
 * @return mixed
 */
function tk_jenis_update_value( $value, $post_id ) {
    if ( is_numeric( $post_id ) && isset( TK_JENIS[ $value ] ) ) {
        wp_set_object_terms( (int) $post_id, $value, 'jenis' );
    }
    return $value;
}

/* =============================================================================
 * Kategori per jenis
 * ========================================================================== */

/**
 * ID kategori milik suatu jenis.
 *
 * @param string $jenis Slug dari TK_JENIS.
 * @return int[]
 */
function tk_kategori_ids_jenis( $jenis ) {
    static $memo = array(); // Dipakai berulang dalam satu request (form, validasi, dropdown).
    if ( ! isset( $memo[ $jenis ] ) ) {
        $ids = get_terms( array(
            'taxonomy'   => 'kategori-tradisi',
            'hide_empty' => false,
            'fields'     => 'ids',
            'meta_key'   => 'tk_jenis', // phpcs:ignore WordPress.DB.SlowDBQuery -- daftar kategori kecil.
            'meta_value' => $jenis,     // phpcs:ignore WordPress.DB.SlowDBQuery
        ) );
        $memo[ $jenis ] = is_wp_error( $ids ) ? array() : array_map( 'intval', $ids );
    }
    return $memo[ $jenis ];
}

/**
 * Key field ACF Kategori per jenis (lihat includes/core/acf-fields.php).
 *
 * @return string[] field key => slug jenis
 */
function tk_kategori_field_jenis() {
    return array(
        'field_tk_kategori_tradisi'  => 'tradisi',
        'field_tk_kategori_material' => 'budaya-material',
    );
}

add_action( 'acf/include_fields', 'tk_kategori_register_field_jenis' );

/**
 * Field "Jenis" di halaman edit kategori (term meta tk_jenis).
 */
function tk_kategori_register_field_jenis() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }
    acf_add_local_field_group( array(
        'key'      => 'group_tk_kategori_jenis',
        'title'    => 'Jenis Kategori',
        'fields'   => array(
            array(
                'key'           => 'field_tk_kategori_jenis',
                'label'         => 'Untuk Jenis',
                'name'          => 'tk_jenis',
                'type'          => 'button_group',
                'instructions'  => 'Kategori ini hanya bisa dipilih untuk koleksi berjenis ini.',
                'choices'       => TK_JENIS,
                'default_value' => 'tradisi',
                'required'      => 1,
                'return_format' => 'value',
            ),
        ),
        'location' => array( array( array( 'param' => 'taxonomy', 'operator' => '==', 'value' => 'kategori-tradisi' ) ) ),
        'active'   => true,
    ) );
}

// Setiap field Kategori: pilihan disaring & divalidasi sesuai jenisnya.
foreach ( tk_kategori_field_jenis() as $tk_key => $tk_jenis ) {
    add_filter( 'acf/fields/taxonomy/wp_list_categories/key=' . $tk_key, function ( $args ) use ( $tk_jenis ) {
        return tk_kategori_saring( $args, $tk_jenis );
    } );
    add_filter( 'acf/validate_value/key=' . $tk_key, function ( $valid, $value ) use ( $tk_jenis ) {
        return tk_kategori_validasi( $valid, $value, $tk_jenis );
    }, 10, 2 );
}
unset( $tk_key, $tk_jenis );

/**
 * Batasi daftar checkbox kategori ke jenis tertentu.
 *
 * @param array  $args  Argumen wp_list_categories().
 * @param string $jenis
 * @return array
 */
function tk_kategori_saring( $args, $jenis ) {
    $ids             = tk_kategori_ids_jenis( $jenis );
    $args['include'] = $ids ? $ids : array( 0 ); // Kosong = tidak ada pilihan.
    return $args;
}

/**
 * Tolak kategori yang bukan milik jenisnya (mis. kiriman yang dimanipulasi).
 *
 * @param bool|string $valid
 * @param mixed       $value Daftar term ID.
 * @param string      $jenis
 * @return bool|string
 */
function tk_kategori_validasi( $valid, $value, $jenis ) {
    if ( true !== $valid || ! $value ) {
        return $valid;
    }
    $salah = array_diff( array_map( 'intval', (array) $value ), tk_kategori_ids_jenis( $jenis ) );
    return $salah ? 'Pilih kategori dari daftar untuk ' . TK_JENIS[ $jenis ] . '.' : $valid;
}
