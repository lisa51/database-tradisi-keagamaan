<?php
/**
 * Peran pengguna, hak akses, dan pengaturan login.
 *
 * PERAN
 *   Kontributor (kontributor)
 *     - Bisa login dan mengirim tradisi lewat form di halaman "Tambah Tradisi".
 *     - Tidak bisa masuk wp-admin (kecuali halaman Profil) dan tidak melihat admin bar.
 *     - Kiriman masuk dengan status "pending" (menunggu kurasi).
 *
 *   Kurator (kurator)
 *     - Bisa membuka halaman "Dashboard Kurasi", menerbitkan atau menolak kiriman.
 *     - Bisa mengedit tradisi dan mengelola term (wilayah, kategori, dsb.) di wp-admin.
 *     - Tidak bisa mengubah pengaturan situs, plugin, tema, atau pengguna.
 *
 *   Administrator
 *     - Otomatis mendapat hak kurasi dan kirim.
 *
 * HAK AKSES KHUSUS (capability)
 *   tk_kirim   Boleh memakai form Tambah Tradisi.
 *   tk_kurasi  Boleh memakai Dashboard Kurasi.
 *
 * PENGATURAN YANG HARUS DIISI MANUAL (Settings → General):
 *   - Membership: centang "Anyone can register".
 *   - New User Default Role: pilih "Kontributor".
 *
 * Peran dibuat ulang otomatis setiap TK_ROLES_VERSION dinaikkan, jadi kalau
 * daftar hak akses di bawah diubah, naikkan juga angka versinya.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Naikkan angka ini setiap kali daftar hak akses di bawah diubah. */
define( 'TK_ROLES_VERSION', 1 );

add_action( 'init', 'tk_setup_roles' );

/**
 * Buat/perbarui peran Kontributor & Kurator (hanya saat versi berubah).
 */
function tk_setup_roles() {
    if ( (int) get_option( 'tk_roles_version' ) === TK_ROLES_VERSION ) {
        return;
    }

    $peran = array(
        'kontributor' => array(
            'nama' => 'Kontributor',
            'caps' => array( 'read', 'tk_kirim' ),
        ),
        'kurator'     => array(
            'nama' => 'Kurator',
            'caps' => array(
                'read', 'tk_kirim', 'tk_kurasi', 'upload_files', 'manage_categories',
                'edit_posts', 'edit_others_posts', 'edit_published_posts', 'publish_posts',
                'delete_posts', 'delete_others_posts', 'delete_published_posts',
            ),
        ),
    );

    foreach ( $peran as $slug => $data ) {
        remove_role( $slug ); // Hapus dulu supaya daftar hak akses selalu sesuai kode.
        add_role( $slug, $data['nama'], array_fill_keys( $data['caps'], true ) );
    }

    $admin = get_role( 'administrator' );
    if ( $admin ) {
        $admin->add_cap( 'tk_kirim' );
        $admin->add_cap( 'tk_kurasi' );
    }

    update_option( 'tk_roles_version', TK_ROLES_VERSION );
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

/* -----------------------------------------------------------------------------
 * Tampilan halaman login & daftar (wp-login.php) mengikuti gaya WARISI.
 * -------------------------------------------------------------------------- */

add_filter( 'login_headerurl', 'tk_login_logo_url' );
add_filter( 'login_headertext', 'tk_login_logo_text' );
add_action( 'login_enqueue_scripts', 'tk_login_style' );

/** Logo di halaman login mengarah ke Beranda. */
function tk_login_logo_url() {
    return home_url( '/' );
}

/** Teks logo = nama situs. */
function tk_login_logo_text() {
    return get_bloginfo( 'name' );
}

/** CSS halaman login: latar krem, nama situs sebagai logo, tombol oranye. */
function tk_login_style() {
    wp_enqueue_style(
        'tk-fonts',
        'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=DM+Serif+Display&display=swap',
        array(),
        null
    );

    $css = '
      body.login { background: #fbf7f1; font-family: "DM Sans", system-ui, sans-serif; }
      .login h1 a {
        width: auto; height: auto; margin: 0 0 16px;
        background: none; text-indent: 0; overflow: visible;
        font-family: "DM Serif Display", Georgia, serif; font-size: 32px; color: #2b2622;
      }
      .login form { border: 1px solid #eadfd3; border-radius: 12px; box-shadow: none; }
      .login .button-primary {
        background: #c4552d; border-color: #c4552d; border-radius: 8px; box-shadow: none;
      }
      .login .button-primary:hover, .login .button-primary:focus { background: #a8431f; border-color: #a8431f; }
      .login #nav a, .login #backtoblog a { color: #a8431f; }
      .login input[type=text]:focus, .login input[type=password]:focus, .login input[type=email]:focus {
        border-color: #c4552d; box-shadow: 0 0 0 1px #c4552d;
      }';

    wp_add_inline_style( 'login', $css );
}
