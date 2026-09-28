<?php
/**
 * Fungsi bantu (helper) yang dipakai bersama oleh beberapa modul.
 *
 * Isi:
 *   - tk_term_names()   : ambil nama term sebuah post sebagai teks.
 *   - tk_icon()         : ikon SVG kecil (pin, gedung).
 *   - tk_url_tambah()   : URL halaman "Tambah Tradisi".
 *   - tk_url_jelajahi() : URL ke panel pencarian di Beranda.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ambil nama term (kategori, wilayah, tag) sebuah post sebagai satu string.
 *
 * Contoh:
 *   tk_term_names( 12, 'kategori-tradisi' )     // "Keagamaan, Perayaan Adat"
 *   tk_term_names( 12, 'wilayah', ', ', 1 )     // "Jawa Timur" (hanya term pertama)
 *
 * @param int    $post_id  ID post.
 * @param string $taxonomy Nama taxonomy.
 * @param string $sep      Pemisah antar-nama. Default ", ".
 * @param int    $limit    Maksimal jumlah term. 0 = semua.
 * @return string Nama term, atau string kosong kalau tidak ada.
 */
function tk_term_names( $post_id, $taxonomy, $sep = ', ', $limit = 0 ) {
    $terms = get_the_terms( $post_id, $taxonomy );
    if ( ! $terms || is_wp_error( $terms ) ) {
        return '';
    }
    if ( $limit > 0 ) {
        $terms = array_slice( $terms, 0, $limit );
    }
    return implode( $sep, wp_list_pluck( $terms, 'name' ) );
}

/**
 * Ikon SVG garis yang mengikuti warna teks (currentColor).
 *
 * Contoh:
 *   tk_icon( 'pin' )         // 14px, untuk lokasi di card
 *   tk_icon( 'buku', 22 )    // 22px, untuk kotak stats
 *
 * @param string $name Nama ikon: 'pin', 'gedung', 'buku', 'user', 'mata', 'link'.
 * @param int    $size Ukuran dalam piksel. Default 14.
 * @return string Markup SVG, atau string kosong kalau nama tidak dikenal.
 */
function tk_icon( $name, $size = 14 ) {
    $paths = array(
        'pin'    => '<path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/>',
        'gedung' => '<path d="M3 21h18M5 21V8l7-4 7 4v13M9 21v-6h6v6"/>',
        'user'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'mata'   => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'link'   => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
        'buku'   => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/>',
    );
    if ( ! isset( $paths[ $name ] ) ) {
        return '';
    }
    $size = absint( $size );
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . $paths[ $name ] . '</svg>';
}

/**
 * URL halaman "Tambah Tradisi" (slug: tambah-tradisi).
 *
 * @return string
 */
function tk_url_tambah() {
    $page = get_page_by_path( 'tambah-tradisi' );
    return $page ? get_permalink( $page ) : home_url( '/tambah-tradisi/' );
}

/**
 * URL panel pencarian di Beranda (anchor #jelajahi dari [tk_koleksi]).
 *
 * @return string
 */
function tk_url_jelajahi() {
    return home_url( '/#jelajahi' );
}
