<?php
/**
 * Pembatasan akses untuk kontributor (pengguna tanpa hak edit_posts).
 *
 *   - wp-admin diarahkan ke Beranda (kecuali Profil, AJAX, admin-post.php).
 *   - Admin bar hitam disembunyikan.
 *   - Setelah login diarahkan ke halaman Tambah Tradisi.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Apakah pengguna ini hanya kontributor (tidak boleh ke wp-admin)?
 *
 * @return bool
 */
function tk_is_kontributor_saja() {
    return is_user_logged_in() && ! current_user_can( 'edit_posts' );
}

add_action( 'admin_init', 'tk_block_wp_admin' );

/**
 * Kontributor yang membuka wp-admin diarahkan ke Beranda.
 * Pengecualian: AJAX, admin-post.php, dan halaman Profil (untuk ganti password).
 */
function tk_block_wp_admin() {
    global $pagenow;

    if ( ! tk_is_kontributor_saja() || wp_doing_ajax() ) {
        return;
    }
    if ( in_array( $pagenow, array( 'profile.php', 'admin-post.php' ), true ) ) {
        return;
    }

    wp_safe_redirect( home_url( '/' ) );
    exit;
}

add_filter( 'show_admin_bar', 'tk_sembunyikan_admin_bar' );

/**
 * Sembunyikan admin bar hitam untuk kontributor.
 *
 * @param bool $show
 * @return bool
 */
function tk_sembunyikan_admin_bar( $show ) {
    return tk_is_kontributor_saja() ? false : $show;
}

add_filter( 'login_redirect', 'tk_login_redirect', 10, 3 );

/**
 * Setelah login, kontributor diarahkan ke halaman Tambah Tradisi
 * (kecuali ia sedang menuju halaman tertentu di situs, bukan wp-admin).
 *
 * @param string           $redirect_to URL tujuan.
 * @param string           $requested   URL yang diminta.
 * @param WP_User|WP_Error $user        Pengguna yang login.
 * @return string
 */
function tk_login_redirect( $redirect_to, $requested, $user ) {
    if ( ! $user instanceof WP_User || $user->has_cap( 'edit_posts' ) ) {
        return $redirect_to;
    }
    if ( $requested && false === strpos( $requested, '/wp-admin' ) ) {
        return $requested;
    }
    return tk_url_tambah();
}
