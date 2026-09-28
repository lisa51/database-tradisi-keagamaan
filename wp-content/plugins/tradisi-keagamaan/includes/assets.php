<?php
/**
 * Memuat font dan CSS plugin di halaman depan.
 *
 * - Font  : DM Serif Display (judul) + DM Sans (teks) dari Google Fonts.
 * - CSS   : assets/css/warisi.css (seluruh tampilan WARISI).
 *
 * CSS dimuat dari file (bukan Appearance → Customize → Additional CSS) agar
 * ikut ter-track di Git dan ikut ter-deploy ke server.
 *
 * Versi CSS memakai waktu terakhir file diubah (filemtime), jadi browser
 * otomatis mengambil versi baru setiap file disimpan, tanpa Ctrl+F5.
 *
 * CATATAN DEPLOYMENT: kalau server internal tidak bisa mengakses internet,
 * font Google tidak akan termuat. Solusinya: unduh file font, simpan di
 * assets/fonts, lalu ganti enqueue font di bawah dengan CSS @font-face lokal.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_enqueue_scripts', 'tk_enqueue_assets' );

/**
 * Daftarkan font dan CSS plugin.
 */
function tk_enqueue_assets() {
    wp_enqueue_style(
        'tk-fonts',
        'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=DM+Serif+Display&display=swap',
        array(),
        null
    );

    $css_file = 'assets/css/warisi.css';
    wp_enqueue_style(
        'tk-warisi',
        TK_URL . $css_file,
        array( 'tk-fonts' ),
        filemtime( TK_PATH . $css_file )
    );
}
