<?php
/**
 * Cloudflare Turnstile (anti-bot) untuk form WARISI.
 *
 * Memakai plugin "Simple Cloudflare Turnstile" (Settings → Turnstile), yang
 * juga melindungi halaman login, daftar, dan lupa password. File ini hanya
 * menyambungkannya ke form buatan WARISI:
 *
 *   [tk_kontak]        semua pengunjung yang belum login
 *   [tk_form_tradisi]  kiriman BARU dari tamu (revisi tamu sudah dilindungi token)
 *
 * Bila plugin tidak aktif atau kunci belum diisi, semua fungsi di sini
 * dianggap lolos, sehingga form tetap berjalan (dengan honeypot & batas per IP).
 * Pengguna yang login dilewati bila opsi "Whitelist logged in users" aktif.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Turnstile siap dipakai: plugin aktif & kunci terisi.
 *
 * @return bool
 */
function tk_turnstile_aktif() {
    return function_exists( 'cfturnstile_field_show' )
        && function_exists( 'cfturnstile_check' )
        && get_option( 'cfturnstile_key' )
        && get_option( 'cfturnstile_secret' );
}

/**
 * HTML widget Turnstile.
 *
 * @param string $nama Nama form (dikirim ke Cloudflare sebagai "action", untuk statistik).
 * @return string HTML, atau kosong bila tidak aktif.
 */
function tk_turnstile_widget( $nama ) {
    if ( ! tk_turnstile_aktif() ) {
        return '';
    }
    ob_start();
    cfturnstile_field_show( '', '', $nama, '-' . $nama );
    $html = trim( ob_get_clean() );

    return $html ? '<div class="tk-turnstile">' . $html . '</div>' : '';
}

/**
 * Periksa jawaban Turnstile dari form yang dikirim.
 *
 * @param string $nama Nama form, sama dengan tk_turnstile_widget().
 * @return bool
 */
function tk_turnstile_lolos( $nama ) {
    if ( ! tk_turnstile_aktif() ) {
        return true;
    }
    $hasil = cfturnstile_check( '', $nama );
    return ! empty( $hasil['success'] );
}
