<?php
/**
 * Latitude/Longitude otomatis dari Provinsi + Kabupaten/Kota.
 *
 *   Form depan   field Latitude/Longitude disembunyikan; koordinat diisi
 *                saat disimpan.
 *   wp-admin     field tetap tampil. Kosongkan = diisi otomatis; isi angka
 *                sendiri = titik manual yang tidak ditimpa lagi.
 *   Titik        ibu kota kabupaten/kota (asal_daerah). Bila kosong atau
 *                tidak dikenali, ibu kota provinsi (taxonomy "wilayah").
 *                Data: includes/data/koordinat-wilayah.php.
 *
 * Meta KOORDINAT_OTOMATIS menyimpan titik terakhir yang diisi otomatis
 * ("lat,lng"). Selama latitude/longitude masih sama dengan titik itu,
 * koordinat dianggap otomatis dan ikut berubah bila lokasi diganti.
 * Awalannya bukan _tk_, jadi ikut disalin ke/dari usulan perubahan.
 *
 * Koleksi lama tanpa koordinat diisi sekali (tk_koordinat_isi_lama()).
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const TK_KOORDINAT_OTOMATIS = '_koordinat_otomatis';

/**
 * Koordinat ibu kota per provinsi dan kabupaten/kota.
 *
 * @return array<string, array{provinsi: float[], kabkota: array<string, float[]>}>
 */
function tk_koordinat_data() {
    static $data = null;
    if ( null === $data ) {
        $data = require TK_PATH . 'includes/data/koordinat-wilayah.php';
    }
    return $data;
}

/**
 * Nama provinsi (term "wilayah" level teratas) sebuah tradisi.
 *
 * @param int $post_id
 * @return string Nama provinsi, atau ''.
 */
function tk_koordinat_provinsi( $post_id ) {
    $terms = get_the_terms( $post_id, 'wilayah' );
    return ( $terms && ! is_wp_error( $terms ) ) ? tk_wilayah_provinsi( $terms[0]->term_id ) : '';
}

/**
 * Titik otomatis untuk sebuah tradisi.
 *
 * @param int $post_id
 * @return float[]|null array( lat, lng ), atau null bila lokasi belum diisi.
 */
function tk_koordinat_dari_wilayah( $post_id ) {
    $data     = tk_koordinat_data();
    $provinsi = tk_koordinat_provinsi( $post_id );
    $kabkota  = tk_kabkota_cari( (string) get_post_meta( $post_id, 'asal_daerah', true ), $provinsi );

    if ( ! $provinsi && $kabkota ) {
        $provinsi = tk_kabkota_provinsi_dari( $kabkota );
    }
    if ( ! isset( $data[ $provinsi ] ) ) {
        return null;
    }
    return isset( $data[ $provinsi ]['kabkota'][ $kabkota ] )
        ? $data[ $provinsi ]['kabkota'][ $kabkota ]
        : $data[ $provinsi ]['provinsi'];
}

/**
 * Isi atau perbarui koordinat otomatis. Titik manual tidak disentuh.
 *
 * @param int $post_id
 */
function tk_koordinat_perbarui( $post_id ) {
    $lat   = get_post_meta( $post_id, 'latitude', true );
    $lng   = get_post_meta( $post_id, 'longitude', true );
    $tanda = (string) get_post_meta( $post_id, TK_KOORDINAT_OTOMATIS, true );
    $kosong = ! is_numeric( $lat ) || ! is_numeric( $lng );

    // Diisi sendiri (berbeda dari titik otomatis terakhir) → manual.
    if ( ! $kosong && ! tk_koordinat_sama( $tanda, $lat . ',' . $lng ) ) {
        delete_post_meta( $post_id, TK_KOORDINAT_OTOMATIS );
        return;
    }

    $titik = tk_koordinat_dari_wilayah( $post_id );
    if ( ! $titik ) {
        if ( '' !== $tanda ) { // Lokasi dihapus: titik otomatis lama ikut dihapus.
            update_field( 'field_tk_latitude', '', $post_id );
            update_field( 'field_tk_longitude', '', $post_id );
            delete_post_meta( $post_id, TK_KOORDINAT_OTOMATIS );
        }
        return;
    }

    update_field( 'field_tk_latitude', (string) $titik[0], $post_id );
    update_field( 'field_tk_longitude', (string) $titik[1], $post_id );
    update_post_meta( $post_id, TK_KOORDINAT_OTOMATIS, $titik[0] . ',' . $titik[1] );
}

/**
 * Bandingkan dua teks "lat,lng" sebagai angka.
 *
 * @param string $a
 * @param string $b
 * @return bool
 */
function tk_koordinat_sama( $a, $b ) {
    $a = array_map( 'floatval', explode( ',', $a ) );
    $b = array_map( 'floatval', explode( ',', $b ) );
    return 2 === count( $a ) && 2 === count( $b )
        && abs( $a[0] - $b[0] ) < 1e-6 && abs( $a[1] - $b[1] ) < 1e-6;
}

// Setelah ACF menyimpan field (10), sebelum tk_form_after_save() (20).
add_action( 'acf/save_post', 'tk_koordinat_saat_simpan', 15 );

/**
 * @param int|string $post_id
 */
function tk_koordinat_saat_simpan( $post_id ) {
    if ( is_numeric( $post_id ) && 'tradisi' === get_post_type( $post_id ) ) {
        tk_koordinat_perbarui( (int) $post_id );
    }
}

add_filter( 'acf/prepare_field/key=field_tk_latitude', 'tk_koordinat_field' );
add_filter( 'acf/prepare_field/key=field_tk_longitude', 'tk_koordinat_field' );

/**
 * Sembunyikan Latitude/Longitude di form depan.
 *
 * @param array $field
 * @return array|false
 */
function tk_koordinat_field( $field ) {
    return is_admin() ? $field : false;
}

add_action( 'admin_init', 'tk_koordinat_isi_lama' );

/**
 * Sekali jalan: isi koordinat koleksi yang belum punya. Koordinat yang sudah
 * ada dianggap manual dan tidak diubah.
 */
function tk_koordinat_isi_lama() {
    if ( get_option( 'tk_koordinat_isi_lama' ) || ! current_user_can( 'edit_others_posts' ) ) {
        return;
    }

    $ids = get_posts( array(
        'post_type'      => 'tradisi',
        'post_status'    => array( 'publish', 'pending', 'draft' ),
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ) );
    foreach ( $ids as $id ) {
        tk_koordinat_perbarui( $id ); // Koordinat yang sudah ada (tanpa tanda) dibiarkan.
    }

    update_option( 'tk_koordinat_isi_lama', 1, true ); // Setelah selesai: terhenti di tengah = diulang.
}
