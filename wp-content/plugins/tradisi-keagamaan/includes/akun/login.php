<?php
/**
 * Tampilan halaman login & daftar (wp-login.php) bergaya WARISI.
 *
 *   - Logo WordPress diganti nama situs, mengarah ke Beranda.
 *   - Memakai token warna dari base.css + aturan khusus di login.css.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'login_headerurl', 'tk_login_logo_url' );

/** Logo di halaman login mengarah ke Beranda. */
function tk_login_logo_url() {
    return home_url( '/' );
}

add_filter( 'login_headertext', 'tk_login_logo_text' );

/** Teks logo = nama situs. */
function tk_login_logo_text() {
    return get_bloginfo( 'name' );
}

add_action( 'login_enqueue_scripts', 'tk_login_style' );

/**
 * Muat font, base.css (token warna), dan login.css di halaman login.
 */
function tk_login_style() {
    wp_enqueue_style( 'tk-fonts', TK_FONTS_URL, array(), null );
    $base = tk_enqueue_css( 'base', array( 'tk-fonts', 'login' ) );
    tk_enqueue_css( 'login', array( $base ) );
}
