<?php
/**
 * Ikon situs (favicon) WARISI di tab browser, bookmark, dan layar utama ponsel.
 *
 *   wp_head / login_head / admin_head  tk_ikon_situs_tag()   tag <link rel="icon">
 *   do_faviconico                      tk_ikon_situs_ico()   /favicon.ico → favicon-32.png
 *
 * Berkas di assets/img/: favicon.svg (sumber), favicon-32.png, favicon-192.png,
 * favicon-512.png, apple-touch-icon.png (180 px). Bila Site Icon diatur di
 * Settings → General (WordPress), ikon itu yang dipakai dan modul ini diam.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_head', 'tk_ikon_situs_tag', 2 );
add_action( 'login_head', 'tk_ikon_situs_tag' );
add_action( 'admin_head', 'tk_ikon_situs_tag' );

/**
 * Cetak tag ikon situs.
 */
function tk_ikon_situs_tag() {
    if ( has_site_icon() ) {
        return;
    }
    $berkas = array(
        array( 'icon', 'favicon.svg', 'image/svg+xml', '' ),
        array( 'icon', 'favicon-32.png', 'image/png', '32x32' ),
        array( 'icon', 'favicon-192.png', 'image/png', '192x192' ),
        array( 'apple-touch-icon', 'apple-touch-icon.png', '', '180x180' ),
    );
    foreach ( $berkas as list( $rel, $nama, $tipe, $ukuran ) ) {
        printf(
            '<link rel="%s" href="%s"%s%s>' . "\n",
            esc_attr( $rel ),
            esc_url( tk_ikon_situs_url( $nama ) ),
            $tipe ? ' type="' . esc_attr( $tipe ) . '"' : '',
            $ukuran ? ' sizes="' . esc_attr( $ukuran ) . '"' : ''
        );
    }
}

add_action( 'do_faviconico', 'tk_ikon_situs_ico' );

/**
 * /favicon.ico (diminta otomatis oleh browser & aplikasi lain): arahkan ke ikon
 * WARISI, bukan logo WordPress bawaan.
 */
function tk_ikon_situs_ico() {
    if ( has_site_icon() ) {
        return;
    }
    wp_safe_redirect( tk_ikon_situs_url( 'favicon-32.png' ) );
    exit;
}

/**
 * URL berkas ikon, dengan versi dari waktu ubah berkas agar cache browser diperbarui.
 *
 * @param string $nama Nama berkas di assets/img/.
 * @return string
 */
function tk_ikon_situs_url( $nama ) {
    $path = 'assets/img/' . $nama;
    return add_query_arg( 'ver', filemtime( TK_PATH . $path ), TK_URL . $path );
}
