<?php
/**
 * Penyesuaian menu navigasi.
 *
 * Masalah yang diperbaiki:
 *   Item menu Custom Link berisi anchor (misal "/#jelajahi") dianggap
 *   WordPress sama dengan halaman Beranda, sehingga ikut tampil aktif
 *   (latar krem) bersamaan dengan item "Beranda".
 *
 * Solusi:
 *   Hapus class "halaman aktif" dari semua Custom Link yang mengandung "#".
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'nav_menu_css_class', 'tk_menu_anchor_tidak_aktif', 10, 2 );

/**
 * @param string[] $classes Class CSS item menu.
 * @param WP_Post  $item    Item menu.
 * @return string[]
 */
function tk_menu_anchor_tidak_aktif( $classes, $item ) {
    if ( 'custom' !== $item->type || false === strpos( $item->url, '#' ) ) {
        return $classes;
    }
    return array_diff( $classes, array(
        'current-menu-item',
        'current_page_item',
        'current-menu-ancestor',
        'current-menu-parent',
    ) );
}
