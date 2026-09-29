<?php
/**
 * Penyesuaian menu navigasi.
 *
 * 1. Link anchor tidak ditandai aktif
 *    Item Custom Link berisi anchor (misal "/#jelajahi") dianggap WordPress
 *    sama dengan Beranda, sehingga ikut tampil aktif. Class aktifnya dihapus.
 *
 * 2. Item khusus lewat CSS Class (Appearance → Menus → Screen Options →
 *    centang "CSS Classes", lalu isi kolom CSS Classes pada item menu):
 *
 *    tk-menu-cta      Tampil sebagai tombol oranye (gaya ada di assets/css/base.css).
 *    tk-menu-kurator  Hanya tampil untuk Kurator & Administrator.
 *                     Pakai untuk item "Dashboard Kurasi".
 *    tk-menu-akun     Otomatis berganti teks & link:
 *                     belum login → "Masuk" (ke halaman login),
 *                     sudah login → "Keluar" (logout, kembali ke Beranda).
 *                     Buat sebagai Custom Link dengan URL "#" dan teks bebas.
 *                     Ikon masuk/keluar dipasang otomatis.
 *
 * 3. Ikon menu lewat CSS Class tk-ikon-<nama>, mis. "tk-ikon-rumah".
 *    Nama ikon: rumah, cari, peta, kurasi, info, surat, tambah, masuk,
 *    keluar (juga pin, gedung, buku, user, mata, link). Daftar SVG ada di
 *    tk_icon() (includes/core/helpers.php). Gaya: .tk-menu-ikon di base.css.
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

add_filter( 'wp_nav_menu_objects', 'tk_menu_item_khusus' );

/**
 * Proses item menu dengan class tk-menu-kurator dan tk-menu-akun.
 *
 * @param WP_Post[] $items Item menu.
 * @return WP_Post[]
 */
function tk_menu_item_khusus( $items ) {
    foreach ( $items as $key => $item ) {
        $classes = (array) $item->classes;

        // Sembunyikan menu kurator dari selain kurator/admin.
        if ( in_array( 'tk-menu-kurator', $classes, true ) && ! current_user_can( 'tk_kurasi' ) ) {
            unset( $items[ $key ] );
            continue;
        }

        // Tombol Masuk / Keluar.
        if ( in_array( 'tk-menu-akun', $classes, true ) ) {
            if ( is_user_logged_in() ) {
                $item->title = 'Keluar';
                $item->url   = wp_logout_url( home_url( '/' ) );
                $item->classes[] = 'tk-ikon-keluar';
            } else {
                $item->title = 'Masuk';
                // Tujuan setelah login diatur tk_login_redirect() (includes/akun/akses.php).
                $item->url   = wp_login_url();
                $item->classes[] = 'tk-ikon-masuk';
            }
        }
    }

    return $items;
}

add_filter( 'nav_menu_item_title', 'tk_menu_ikon', 10, 2 );

/**
 * Tambahkan ikon SVG di depan judul item menu dengan class tk-ikon-<nama>.
 *
 * @param string  $title Judul item menu.
 * @param WP_Post $item  Item menu.
 * @return string
 */
function tk_menu_ikon( $title, $item ) {
    foreach ( (array) $item->classes as $class ) {
        if ( 0 === strpos( $class, 'tk-ikon-' ) ) {
            $svg = tk_icon( substr( $class, strlen( 'tk-ikon-' ) ), 16 );
            if ( $svg ) {
                return '<span class="tk-menu-ikon">' . $svg . '</span>' . $title;
            }
        }
    }
    return $title;
}
