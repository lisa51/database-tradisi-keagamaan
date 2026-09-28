<?php
/**
 * Peran pengguna & akun sistem "Kontributor Tamu".
 *
 *   Kontributor        read, tk_kirim. Kirim & simpan draf lewat form depan.
 *   Kontributor Tamu   tanpa hak apa pun. Dipakai SATU akun sistem ("kontributor-tamu")
 *                      yang menjadi penulis semua kiriman pengunjung tanpa akun.
 *                      Nama & email asli tamu disimpan di post meta.
 *   Kurator            tk_kurasi + hak edit/terbit/hapus post & kelola term.
 *                      Tidak bisa mengubah pengaturan, plugin, tema, atau pengguna.
 *   Administrator      ditambah tk_kirim & tk_kurasi.
 *
 * Hak akses khusus:
 *   tk_kirim   boleh memakai form Tambah Tradisi (dengan akun).
 *   tk_kurasi  boleh memakai Dashboard & Panel Kurator.
 *
 * Peran dibuat ulang otomatis bila TK_ROLES_VERSION (config.php) dinaikkan.
 *
 * Pengaturan manual (Settings → General): centang "Anyone can register" dan
 * pilih "New User Default Role: Kontributor".
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
        'kontributor_tamu' => array(
            'nama' => 'Kontributor Tamu',
            'caps' => array(), // Tanpa hak apa pun; hanya penanda penulis.
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

    tk_get_user_tamu(); // Pastikan akun sistem "Kontributor Tamu" ada.

    update_option( 'tk_roles_version', TK_ROLES_VERSION );
}

/**
 * ID akun sistem "Kontributor Tamu" (dibuat otomatis bila belum ada).
 * Akun ini diberi password acak dan tidak dimaksudkan untuk login.
 *
 * @return int ID pengguna, atau 0 bila gagal dibuat.
 */
function tk_get_user_tamu() {
    $id = (int) get_option( 'tk_user_tamu' );
    if ( $id && get_userdata( $id ) ) {
        return $id;
    }

    $id = wp_insert_user( array(
        'user_login'   => 'kontributor-tamu',
        'user_pass'    => wp_generate_password( 32, true, true ),
        'display_name' => 'Kontributor Tamu',
        'role'         => 'kontributor_tamu',
    ) );

    if ( is_wp_error( $id ) ) {
        $user = get_user_by( 'login', 'kontributor-tamu' ); // Mungkin sudah ada dari sebelumnya.
        $id   = $user ? $user->ID : 0;
    }

    update_option( 'tk_user_tamu', (int) $id );
    return (int) $id;
}
