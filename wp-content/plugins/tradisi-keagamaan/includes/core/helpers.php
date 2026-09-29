<?php
/**
 * Fungsi bantu umum yang dipakai banyak modul.
 *
 *   tk_term_names()    Nama term sebuah post sebagai teks ("Bali, Jawa Timur").
 *   tk_icon()          Ikon SVG garis (pin, gedung, buku, user, mata, link).
 *   tk_url_tambah()    URL halaman Tambah Tradisi.
 *   tk_url_kurasi()    URL halaman Dashboard Kurasi.
 *   tk_url_jelajahi()  URL panel pencarian di Beranda (#jelajahi).
 *   tk_url_tinjau()    URL tradisi untuk kurator (publik bila terbit, pratinjau bila belum).
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
 * @param string $name Nama ikon: 'pin', 'gedung', 'buku', 'user', 'mata', 'link';
 *                     untuk menu: 'rumah', 'cari', 'peta', 'kurasi', 'info',
 *                     'surat', 'tambah', 'masuk', 'keluar'.
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

        // Menu (lihat includes/core/menu.php).
        'rumah'  => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/>',
        'cari'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'peta'   => '<path d="M9 4 3 6v14l6-2 6 2 6-2V4l-6 2-6-2z"/><path d="M9 4v14M15 6v14"/>',
        'kurasi' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1"/><path d="m9 13 2 2 4-4"/>',
        'info'   => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'surat'  => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'tambah' => '<path d="M12 5v14M5 12h14"/>',
        'masuk'  => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/>',
        'keluar' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
    );
    if ( ! isset( $paths[ $name ] ) ) {
        return '';
    }
    $size = absint( $size );
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . $paths[ $name ] . '</svg>';
}

/**
 * URL halaman "Tambah Tradisi" (slug diatur TK_SLUG_TAMBAH di config.php).
 *
 * @return string
 */
function tk_url_tambah() {
    $page = get_page_by_path( TK_SLUG_TAMBAH );
    return $page ? get_permalink( $page ) : home_url( '/' . TK_SLUG_TAMBAH . '/' );
}

/**
 * URL halaman "Dashboard Kurasi" (slug diatur TK_SLUG_KURASI di config.php).
 *
 * @return string
 */
function tk_url_kurasi() {
    $page = get_page_by_path( TK_SLUG_KURASI );
    return $page ? get_permalink( $page ) : home_url( '/' . TK_SLUG_KURASI . '/' );
}

/**
 * URL panel pencarian di Beranda (anchor #jelajahi dari [tk_koleksi]).
 *
 * @return string
 */
function tk_url_jelajahi() {
    return home_url( '/#jelajahi' );
}

/**
 * URL halaman tradisi untuk kurator: halaman publik bila terbit,
 * pratinjau bila belum.
 *
 * @param int $post_id
 * @return string
 */
function tk_url_tinjau( $post_id ) {
    return 'publish' === get_post_status( $post_id )
        ? get_permalink( $post_id )
        : get_preview_post_link( $post_id );
}
