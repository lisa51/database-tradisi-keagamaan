<?php
/**
 * Isian Kabupaten/Kota (field ACF asal_daerah) yang seragam.
 *
 *   Saran saat mengetik  assets/js/kabkota.js (datalist bawaan browser),
 *                        disaring sesuai provinsi yang dipilih.
 *   Validasi             nilai harus ada di daftar resmi. Bila provinsi
 *                        diketahui, harus ada di provinsi tersebut.
 *   Penyeragaman         "tana toraja" / "Kab. Tana Toraja" disimpan sebagai
 *                        "Kabupaten Tana Toraja".
 *
 * Daftar resmi: includes/data/kabupaten-kota.php (Kepmendagri 2025).
 * "Simpan Draf" di form depan tetap boleh berisi teks bebas (lihat
 * tk_form_validasi_draf()); penyeragaman tetap dijalankan.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Daftar kabupaten/kota per provinsi.
 *
 * @return array<string, string[]> Nama provinsi => daftar kabupaten/kota.
 */
function tk_kabkota_daftar() {
    static $daftar = null;
    if ( null === $daftar ) {
        $daftar = require TK_PATH . 'includes/data/kabupaten-kota.php';
    }
    return $daftar;
}

/**
 * Cari nama resmi dari isian pengguna. Tidak peka huruf besar/kecil, spasi
 * ganda, dan singkatan "Kab.". Tanpa awalan ("Tana Toraja") dicocokkan ke
 * "Kabupaten ..."/"Kota ..." bila hasilnya hanya satu.
 *
 * @param string $nama     Isian pengguna.
 * @param string $provinsi Nama provinsi untuk mempersempit pencarian ('' = semua).
 * @return string Nama resmi, atau '' bila tidak ditemukan / ambigu.
 */
function tk_kabkota_cari( $nama, $provinsi = '' ) {
    $daftar = tk_kabkota_daftar();
    $calon  = ( $provinsi && isset( $daftar[ $provinsi ] ) ) ? $daftar[ $provinsi ] : array_merge( ...array_values( $daftar ) );

    $rapikan = function ( $teks ) {
        $teks = strtolower( trim( preg_replace( '/\s+/', ' ', (string) $teks ) ) );
        return preg_replace( '/^kab\.?\s+/', 'kabupaten ', $teks );
    };

    $cari = $rapikan( $nama );
    if ( '' === $cari ) {
        return '';
    }

    $cocok = array();
    foreach ( $calon as $resmi ) {
        $r = $rapikan( $resmi );
        if ( $r === $cari ) {
            return $resmi;
        }
        if ( in_array( $r, array( 'kabupaten ' . $cari, 'kota ' . $cari, 'kota administrasi ' . $cari ), true ) ) {
            $cocok[] = $resmi;
        }
    }
    return 1 === count( $cocok ) ? $cocok[0] : '';
}

/**
 * Provinsi tempat sebuah kabupaten/kota berada.
 *
 * @param string $resmi Nama resmi (hasil tk_kabkota_cari()).
 * @return string Nama provinsi, atau ''.
 */
function tk_kabkota_provinsi_dari( $resmi ) {
    foreach ( tk_kabkota_daftar() as $provinsi => $list ) {
        if ( in_array( $resmi, $list, true ) ) {
            return $provinsi;
        }
    }
    return '';
}

/**
 * Provinsi yang dipilih di form yang sedang dikirim.
 *   Form depan        acf[field_tk_form_wilayah] (satu term ID)
 *   wp-admin klasik   tax_input[wilayah][]
 * Editor blok tidak mengirim provinsi bersama field ACF, jadi hasilnya ''.
 *
 * @return string Nama provinsi (term level teratas), atau ''.
 */
function tk_kabkota_provinsi_dikirim() {
    // phpcs:disable WordPress.Security.NonceVerification -- dipanggil di tengah validasi/penyimpanan ACF yang sudah memeriksa nonce.
    $ids = array();
    if ( isset( $_POST['acf']['field_tk_form_wilayah'] ) ) {
        $ids = (array) wp_unslash( $_POST['acf']['field_tk_form_wilayah'] );
    } elseif ( isset( $_POST['tax_input']['wilayah'] ) ) {
        $ids = (array) wp_unslash( $_POST['tax_input']['wilayah'] );
    }
    // phpcs:enable

    foreach ( array_filter( array_map( 'absint', $ids ) ) as $id ) {
        $induk = get_ancestors( $id, 'wilayah', 'taxonomy' );
        $term  = get_term( $induk ? end( $induk ) : $id, 'wilayah' );
        if ( $term && ! is_wp_error( $term ) ) {
            return $term->name;
        }
    }
    return '';
}

add_filter( 'acf/validate_value/name=asal_daerah', 'tk_kabkota_validasi', 10, 2 );

/**
 * Isian harus ada di daftar resmi (dan di provinsi yang dipilih).
 *
 * @param bool|string $valid
 * @param mixed       $value
 * @return bool|string
 */
function tk_kabkota_validasi( $valid, $value ) {
    if ( true !== $valid || '' === trim( (string) $value ) ) {
        return $valid;
    }

    $provinsi = tk_kabkota_provinsi_dikirim();
    if ( tk_kabkota_cari( $value, $provinsi ) ) {
        return true;
    }

    $resmi = $provinsi ? tk_kabkota_cari( $value ) : '';
    $lain  = $resmi ? tk_kabkota_provinsi_dari( $resmi ) : '';
    if ( $lain ) {
        return sprintf( '%s ada di Provinsi %s, bukan %s. Periksa kembali pilihan Provinsi.', $resmi, $lain, $provinsi );
    }

    return 'Pilih dari saran yang muncul saat mengetik, misalnya "Kabupaten Tana Toraja" atau "Kota Makassar".';
}

add_filter( 'acf/update_value/name=asal_daerah', 'tk_kabkota_seragamkan' );

/**
 * Simpan dengan penulisan resmi. Isian yang tidak dikenali disimpan apa adanya.
 *
 * @param mixed $value
 * @return mixed
 */
function tk_kabkota_seragamkan( $value ) {
    $resmi = tk_kabkota_cari( $value, tk_kabkota_provinsi_dikirim() );
    if ( ! $resmi ) {
        $resmi = tk_kabkota_cari( $value );
    }
    return $resmi ? $resmi : $value;
}

add_action( 'acf/input/admin_enqueue_scripts', 'tk_kabkota_enqueue' );

/**
 * Muat saran Kabupaten/Kota di mana pun form ACF tradisi tampil
 * (form depan [tk_form_tradisi] dan editor tradisi di wp-admin).
 */
function tk_kabkota_enqueue() {
    if ( is_admin() ) {
        $layar = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $layar || 'tradisi' !== $layar->post_type ) {
            return;
        }
    }

    // Term ID provinsi => nama, untuk membaca pilihan Provinsi di editor blok.
    $id_provinsi = array();
    foreach ( get_terms( array( 'taxonomy' => 'wilayah', 'hide_empty' => false, 'parent' => 0 ) ) as $term ) {
        $id_provinsi[ $term->term_id ] = $term->name;
    }

    $js = 'assets/js/kabkota.js';
    wp_enqueue_script( 'tk-kabkota', TK_URL . $js, array( 'acf-input' ), filemtime( TK_PATH . $js ), true );
    wp_add_inline_script(
        'tk-kabkota',
        'window.tkKabkota = ' . wp_json_encode( array(
            'daftar'     => tk_kabkota_daftar(),
            'idProvinsi' => $id_provinsi,
        ) ) . ';',
        'before'
    );
}
