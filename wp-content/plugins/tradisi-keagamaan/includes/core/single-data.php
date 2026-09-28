<?php
/**
 * Data untuk halaman single tradisi (templates/single-tradisi.php).
 *
 * Template hanya berisi HTML; semua pengambilan & pengolahan data ada di sini.
 * Data dibaca dengan get_post_meta() (bukan get_field()), sehingga halaman
 * tetap tampil walaupun ACF nonaktif.
 *
 *   tk_single_get_data()     Kumpulkan semua data satu tradisi.
 *   tk_get_galeri_ids()      Isi field galeri → daftar ID gambar (Image maupun Gallery ACF Pro).
 *   tk_format_tanggal_acf()  Tanggal ACF (Ymd) → "17 Agustus 2026".
 *   tk_single_get_terkait()  Tradisi terkait (kategori ATAU wilayah sama).
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Kumpulkan semua data yang ditampilkan di halaman single tradisi.
 *
 * @param int $id ID tradisi.
 * @return array {
 *     @type string   $kategori  Nama kategori, dipisah koma.
 *     @type string   $wilayah   Nama wilayah, dipisah koma.
 *     @type string   $agama     Nama agama, dipisah koma.
 *     @type string   $asal      Asal daerah (kabupaten/kota).
 *     @type string   $abstrak   Deskripsi singkat.
 *     @type string   $tanggal   Tanggal perayaan terformat, atau ''.
 *     @type string   $sumber    URL sumber referensi.
 *     @type int[]    $galeri    Daftar ID gambar galeri.
 *     @type WP_Term[] $tags     Kata kunci.
 *     @type int      $pembaca   Jumlah pembaca.
 * }
 */
function tk_single_get_data( $id ) {
    $tags = get_the_terms( $id, 'post_tag' );

    return array(
        'kategori' => tk_term_names( $id, 'kategori-tradisi' ),
        'wilayah'  => tk_term_names( $id, 'wilayah' ),
        'agama'    => tk_term_names( $id, 'agama' ),
        'asal'     => get_post_meta( $id, 'asal_daerah', true ),
        'abstrak'  => get_post_meta( $id, 'deskripsi_singkat', true ),
        'tanggal'  => tk_format_tanggal_acf( get_post_meta( $id, 'tanggal_perayaan', true ) ),
        'sumber'   => get_post_meta( $id, 'sumber_referensi', true ),
        'galeri'   => tk_get_galeri_ids( get_post_meta( $id, 'galeri_foto', true ) ),
        'tags'     => ( $tags && ! is_wp_error( $tags ) ) ? $tags : array(),
        'pembaca'  => tk_get_view_count( $id ),
    );
}

/**
 * Ubah isi field galeri menjadi daftar ID gambar.
 *
 * Mendukung semua format yang mungkin:
 *   - 123                  field Image (ACF gratis), disimpan sebagai ID
 *   - "123,456"            daftar ID dipisah koma
 *   - array( 123, 456 )    field Gallery (ACF Pro)
 *   - array( array( 'ID' => 123, ... ) )  format array hasil get_field()
 *
 * Jadi template tidak perlu diubah saat galeri_foto diganti ke Gallery.
 *
 * @param mixed $value Isi field galeri.
 * @return int[] ID gambar yang valid (tanpa duplikat).
 */
function tk_get_galeri_ids( $value ) {
    if ( empty( $value ) ) {
        return array();
    }

    // Satu gambar dalam format array ACF → jadikan daftar berisi satu item.
    if ( is_array( $value ) && ( isset( $value['ID'] ) || isset( $value['url'] ) ) ) {
        $value = array( $value );
    }

    if ( ! is_array( $value ) ) {
        $value = explode( ',', (string) $value );
    }

    $ids = array();
    foreach ( $value as $item ) {
        if ( is_array( $item ) ) {
            $item = isset( $item['ID'] ) ? $item['ID'] : ( isset( $item['id'] ) ? $item['id'] : 0 );
        }
        $ids[] = absint( $item );
    }

    return array_values( array_unique( array_filter( $ids ) ) );
}

/**
 * Format tanggal dari ACF Date Picker (disimpan sebagai "Ymd", mis. 20260817)
 * menjadi teks sesuai bahasa situs, mis. "17 Agustus 2026".
 *
 * Bahasa bulan mengikuti Settings → General → Site Language.
 *
 * @param string $raw Nilai mentah dari database.
 * @return string Tanggal terformat, atau '' kalau kosong/tidak valid.
 */
function tk_format_tanggal_acf( $raw ) {
    if ( ! $raw ) {
        return '';
    }
    $date = DateTime::createFromFormat( 'Ymd', $raw );
    return $date ? date_i18n( 'j F Y', $date->getTimestamp() ) : '';
}

/**
 * Cari tradisi terkait: kategori ATAU wilayah yang sama.
 * Kalau tidak ada, tampilkan tradisi terbaru lainnya.
 *
 * @param int $id     ID tradisi yang sedang dibuka.
 * @param int $jumlah Jumlah tradisi terkait. Default 3.
 * @return int[] Daftar ID tradisi.
 */
function tk_single_get_terkait( $id, $jumlah = 3 ) {
    $dasar = array(
        'post_type'      => 'tradisi',
        'post_status'    => 'publish',
        'posts_per_page' => $jumlah,
        'post__not_in'   => array( $id ),
        'fields'         => 'ids',
        'no_found_rows'  => true,
    );

    $tax_query = array( 'relation' => 'OR' );
    foreach ( array( 'kategori-tradisi', 'wilayah' ) as $tax ) {
        $term_ids = wp_get_post_terms( $id, $tax, array( 'fields' => 'ids' ) );
        if ( $term_ids && ! is_wp_error( $term_ids ) ) {
            $tax_query[] = array(
                'taxonomy' => $tax,
                'field'    => 'term_id',
                'terms'    => $term_ids,
            );
        }
    }

    $hasil = array();
    if ( count( $tax_query ) > 1 ) {
        $hasil = get_posts( $dasar + array( 'tax_query' => $tax_query ) );
    }

    if ( ! $hasil ) {
        $hasil = get_posts( $dasar );
    }

    return $hasil;
}
