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

/*
 * PENTING, URUTAN MUAT:
 * GeneratePress menyisipkan CSS dari Customizer (warna, font, padding menu,
 * latar body) sebagai CSS inline pada handle 'generate-style'. Kalau warisi.css
 * dimuat SEBELUM CSS itu, aturan umum seperti body, a, h1, dan menu kalah.
 *
 * Karena itu:
 *   - hook memakai prioritas 20 (setelah tema selesai mendaftarkan CSS-nya), dan
 *   - warisi.css dijadikan "bergantung" pada 'generate-style', sehingga
 *     WordPress selalu mencetaknya SETELAH CSS GeneratePress.
 */
add_action( 'wp_enqueue_scripts', 'tk_enqueue_assets', 20 );

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

    // Muat setelah CSS tema (kalau tema GeneratePress aktif).
    $deps = array( 'tk-fonts' );
    if ( wp_style_is( 'generate-style', 'registered' ) ) {
        $deps[] = 'generate-style';
    }

    $css_file = 'assets/css/warisi.css';
    wp_enqueue_style(
        'tk-warisi',
        TK_URL . $css_file,
        $deps,
        filemtime( TK_PATH . $css_file )
    );

    tk_register_peta_assets();
}

/**
 * Daftarkan (belum memuat) Leaflet + script peta.
 *
 * File ini baru benar-benar dimuat saat peta dipakai, lewat tk_peta_enqueue()
 * di includes/shortcodes/peta.php. Halaman tanpa peta tidak ikut berat.
 *
 * Sumber Leaflet:
 *   - Kalau folder assets/vendor/leaflet/ berisi leaflet.js & leaflet.css,
 *     pakai salinan lokal (disarankan untuk server internal).
 *   - Kalau tidak ada, ambil dari CDN jsDelivr.
 *
 * Cara menyiapkan salinan lokal: unduh leaflet dari https://leafletjs.com/download.html,
 * lalu salin isi folder "dist" ke assets/vendor/leaflet/.
 */
function tk_register_peta_assets() {
    $versi = '1.9.4';
    $lokal = 'assets/vendor/leaflet/';

    if ( file_exists( TK_PATH . $lokal . 'leaflet.js' ) ) {
        $base = TK_URL . $lokal;
    } else {
        $base = 'https://cdn.jsdelivr.net/npm/leaflet@' . $versi . '/dist/';
    }

    wp_register_style( 'tk-leaflet', $base . 'leaflet.css', array(), $versi );
    wp_register_script( 'tk-leaflet', $base . 'leaflet.js', array(), $versi, true );

    $js_file = 'assets/js/peta.js';
    wp_register_script(
        'tk-peta',
        TK_URL . $js_file,
        array( 'tk-leaflet' ),
        filemtime( TK_PATH . $js_file ),
        true
    );
}
