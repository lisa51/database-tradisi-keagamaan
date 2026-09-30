<?php
/**
 * Plugin Name: Database Tradisi Keagamaan (WARISI)
 * Description: Struktur data, kontribusi, kurasi, dan tampilan untuk WARISI (Warisan Religi Indonesia).
 * Version:     2.5.0
 * Author:      Tim WARISI
 * Text Domain: tradisi-keagamaan
 * Requires PHP: 7.4
 *
 * -----------------------------------------------------------------------------
 * FILE UTAMA (LOADER)
 * -----------------------------------------------------------------------------
 * File ini hanya mendefinisikan path plugin dan memuat modul. Semua logika ada
 * di folder includes/, dikelompokkan per domain:
 *
 *   config.php     Semua pengaturan yang bisa diubah (slug, batas, key ACF, versi).
 *   core/          Fondasi: data, field, aset, menu, penghitung pembaca.
 *   akun/          Peran pengguna, pembatasan akses, halaman login.
 *   kontribusi/    Field & pemrosesan form kirim tradisi.
 *   kurasi/        Aturan, riwayat, email, aksi, dan Panel Kurator.
 *   kontak/        Pemrosesan form Hubungi Kami.
 *   shortcodes/    Tampilan halaman depan: [tk_hero] [tk_stats] [tk_koleksi]
 *                  [tk_peta] [tk_form_tradisi] [tk_kurasi] [tk_kontak]
 *
 * Menambah modul: buat file di folder yang sesuai, lalu daftarkan di
 * $tk_modules di bawah. Panduan lengkap: README.md → "Panduan Pengembang".
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Cegah akses langsung ke file.
}

define( 'TK_VERSION', '2.5.0' );
define( 'TK_PATH', plugin_dir_path( __FILE__ ) ); // Path folder plugin (untuk require).
define( 'TK_URL', plugin_dir_url( __FILE__ ) );   // URL folder plugin (untuk CSS/JS).

/**
 * Daftar modul, dimuat sesuai urutan.
 * config.php dan core/helpers.php harus paling atas karena dipakai modul lain.
 */
$tk_modules = array(
    // Pengaturan.
    'includes/config.php',

    // Fondasi.
    'includes/core/helpers.php',       // Nama term, ikon SVG, URL halaman.
    'includes/core/post-types.php',    // Post type "tradisi", taxonomy, template single.
    'includes/core/acf-fields.php',    // Field ACF "Detail Tradisi".
    'includes/core/single-data.php',   // Data untuk templates/single-tradisi.php.
    'includes/core/view-counter.php',  // Penghitung pembaca.
    'includes/core/assets.php',        // Font, CSS per fitur, script peta.
    'includes/core/menu.php',          // Menu: anchor, item kurator, Masuk/Keluar.
    'includes/core/turnstile.php',     // Anti-bot Cloudflare Turnstile untuk form WARISI.

    // Akun.
    'includes/akun/peran.php',         // Peran Kontributor, Kontributor Tamu, Kurator.
    'includes/akun/akses.php',         // Batasi wp-admin & admin bar untuk kontributor.
    'includes/akun/login.php',         // Tampilan halaman login.

    // Kontribusi.
    'includes/kontribusi/fields.php',  // Field ACF khusus form depan.
    'includes/kontribusi/proses.php',  // Izin, validasi, dan penyimpanan form.

    // Kurasi.
    'includes/kurasi/data.php',        // Pengirim, status, kelengkapan, aturan aksi.
    'includes/kurasi/riwayat.php',     // Riwayat kurasi & pencatatan otomatis.
    'includes/kurasi/token.php',       // Link revisi untuk tamu.
    'includes/kurasi/email.php',       // Email ke kurator & pengirim.
    'includes/kurasi/aksi.php',        // Pemroses Terbitkan / Revisi / Antrean / Tolak.
    'includes/kurasi/komponen.php',    // HTML bersama dashboard & panel.
    'includes/kurasi/admin.php',       // Kotak riwayat di editor wp-admin.
    'includes/kurasi/panel-kurator.php', // Panel Kurator di halaman tradisi.

    // Kontak.
    'includes/kontak/proses.php',      // Validasi, anti-spam, dan email form Hubungi Kami.

    // Shortcode (tampilan).
    'includes/shortcodes/hero.php',          // [tk_hero]
    'includes/shortcodes/stats.php',         // [tk_stats]
    'includes/shortcodes/koleksi.php',       // [tk_koleksi]
    'includes/shortcodes/peta.php',          // [tk_peta]
    'includes/shortcodes/form-tradisi.php',  // [tk_form_tradisi]
    'includes/shortcodes/kurasi.php',        // [tk_kurasi]
    'includes/shortcodes/kontak.php',        // [tk_kontak]
);

foreach ( $tk_modules as $tk_module ) {
    require_once TK_PATH . $tk_module;
}
