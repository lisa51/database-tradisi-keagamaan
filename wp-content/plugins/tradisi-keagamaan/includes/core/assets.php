<?php
/**
 * Memuat font, CSS, dan mendaftarkan script peta.
 *
 * CSS dipecah per fitur di assets/css/ dan dimuat berurutan:
 *
 *   base.css        token warna & font, dasar, header & menu, komponen bersama
 *   beranda.css     [tk_hero], [tk_stats]
 *   koleksi.css     [tk_koleksi] panel Jelajahi, grid & card (juga "Tradisi Terkait")
 *   single.css      halaman detail tradisi
 *   peta.css        [tk_peta]
 *   kontribusi.css  [tk_form_tradisi], "Kiriman Saya"
 *   kurasi.css      [tk_kurasi], Panel Kurator
 *   (login.css dimuat di halaman login, lihat includes/akun/login.php)
 *
 * Untuk menambah file CSS baru: buat file di assets/css/, lalu tambahkan
 * namanya ke tk_css_files(). Versi file memakai waktu ubah (filemtime), jadi
 * browser otomatis mengambil versi terbaru setiap file disimpan.
 *
 * URUTAN MUAT PENTING: GeneratePress menyisipkan CSS Customizer (warna, font,
 * padding menu) sebagai CSS inline pada 'generate-style'. CSS WARISI dimuat
 * SETELAHNYA (prioritas 20 + dependensi 'generate-style'), supaya aturan umum
 * seperti body, a, h1, dan menu tidak kalah.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Daftar file CSS halaman depan, sesuai urutan muat.
 *
 * @return string[] Nama file di assets/css/ (tanpa .css).
 */
function tk_css_files() {
    return array( 'base', 'beranda', 'koleksi', 'single', 'peta', 'kontribusi', 'kurasi' );
}

/**
 * Daftarkan & muat satu file CSS plugin.
 *
 * @param string   $nama Nama file tanpa .css.
 * @param string[] $deps Handle yang harus dimuat lebih dulu.
 * @return string Handle CSS ('tk-' . $nama).
 */
function tk_enqueue_css( $nama, $deps = array() ) {
    $file   = 'assets/css/' . $nama . '.css';
    $handle = 'tk-' . $nama;

    wp_enqueue_style( $handle, TK_URL . $file, $deps, filemtime( TK_PATH . $file ) );
    return $handle;
}

add_action( 'wp_enqueue_scripts', 'tk_enqueue_assets', 20 );

/**
 * Muat font + semua CSS halaman depan, dan daftarkan script peta.
 */
function tk_enqueue_assets() {
    wp_enqueue_style( 'tk-fonts', TK_FONTS_URL, array(), null );

    // CSS pertama menunggu font & CSS tema; berikutnya menunggu base.
    $deps = array( 'tk-fonts' );
    if ( wp_style_is( 'generate-style', 'registered' ) ) {
        $deps[] = 'generate-style';
    }

    $base = null;
    foreach ( tk_css_files() as $nama ) {
        $handle = tk_enqueue_css( $nama, $base ? array( $base ) : $deps );
        $base   = $base ? $base : $handle;
    }

    tk_register_peta_assets();
}

/**
 * Daftarkan (belum memuat) Leaflet + assets/js/peta.js.
 * Dimuat hanya saat peta dipakai, lewat tk_peta_enqueue().
 *
 * Sumber Leaflet: salinan lokal di assets/vendor/leaflet/ bila ada
 * (disarankan untuk server internal), selain itu CDN jsDelivr.
 * Salinan lokal: unduh dari https://leafletjs.com/download.html, salin isi
 * folder "dist" ke assets/vendor/leaflet/.
 */
function tk_register_peta_assets() {
    $lokal = 'assets/vendor/leaflet/';
    $base  = file_exists( TK_PATH . $lokal . 'leaflet.js' )
        ? TK_URL . $lokal
        : 'https://cdn.jsdelivr.net/npm/leaflet@' . TK_LEAFLET_VERSI . '/dist/';

    wp_register_style( 'tk-leaflet', $base . 'leaflet.css', array(), TK_LEAFLET_VERSI );
    wp_register_script( 'tk-leaflet', $base . 'leaflet.js', array(), TK_LEAFLET_VERSI, true );

    $js = 'assets/js/peta.js';
    wp_register_script( 'tk-peta', TK_URL . $js, array( 'tk-leaflet' ), filemtime( TK_PATH . $js ), true );
}
