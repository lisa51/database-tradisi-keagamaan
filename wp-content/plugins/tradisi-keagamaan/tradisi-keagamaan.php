<?php
/**
 * Plugin Name: Database Tradisi Keagamaan (WARISI)
 * Description: Struktur data, shortcode, dan tampilan untuk WARISI (Warisan Religi Indonesia).
 * Version:     1.5.0
 * Author:      Tim WARISI
 * Text Domain: tradisi-keagamaan
 *
 * -----------------------------------------------------------------------------
 * FILE UTAMA (LOADER)
 * -----------------------------------------------------------------------------
 * File ini sengaja dibuat pendek. Tugasnya hanya:
 *   1. Mendefinisikan konstanta path/URL plugin.
 *   2. Memuat modul-modul di folder /includes.
 *
 * Semua logika ada di /includes. Untuk menambah fitur baru:
 *   - Buat file baru di /includes (atau /includes/shortcodes untuk shortcode).
 *   - Tambahkan nama file-nya ke array $tk_modules di bawah.
 *
 * Lihat README.md untuk peta lengkap file dan cara pakai shortcode.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Cegah akses langsung ke file.
}

define( 'TK_VERSION', '1.5.0' );
define( 'TK_PATH', plugin_dir_path( __FILE__ ) ); // Path folder plugin (untuk require).
define( 'TK_URL', plugin_dir_url( __FILE__ ) );   // URL folder plugin (untuk CSS/gambar).

/**
 * Daftar modul yang dimuat, sesuai urutan.
 * helpers.php harus paling atas karena dipakai modul lain.
 */
$tk_modules = array(
    'includes/helpers.php',            // Fungsi bantu (ikon, nama term, URL).
    'includes/post-types.php',         // CPT "tradisi", taxonomy, tags, template single.
    'includes/acf-fields.php',         // Field ACF "Detail Tradisi".
    'includes/view-counter.php',       // Penghitung pembaca.
    'includes/assets.php',             // Font Google + file CSS plugin.
    'includes/roles.php',              // Peran Kontributor & Kurator, akses wp-admin, login.
    'includes/menu.php',               // Penyesuaian menu navigasi.
    'includes/single.php',             // Data untuk templates/single-tradisi.php.
    'includes/kurasi-alur.php',        // Riwayat, checklist, pengirim, email kurasi.
    'includes/shortcodes/hero.php',    // [tk_hero]
    'includes/shortcodes/stats.php',   // [tk_stats]
    'includes/shortcodes/koleksi.php', // [tk_koleksi]
    'includes/shortcodes/peta.php',    // [tk_peta] + peta kecil di single
    'includes/shortcodes/form-tradisi.php', // [tk_form_tradisi]
    'includes/shortcodes/kurasi.php',  // [tk_kurasi]
);

foreach ( $tk_modules as $tk_module ) {
    require_once TK_PATH . $tk_module;
}
