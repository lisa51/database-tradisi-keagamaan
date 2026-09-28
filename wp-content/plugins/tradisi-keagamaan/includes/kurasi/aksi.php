<?php
/**
 * Pemroses aksi kurasi: admin-post.php?action=tk_kurasi (POST).
 *
 * Dipakai tombol di Dashboard Kurasi dan Panel Kurator.
 * Aksi yang sah bergantung pada status saat ini (tk_aksi_diizinkan()):
 *   terbitkan · revisi · antrean · tolak
 *
 * Keamanan: nonce per tradisi, cek hak tk_kurasi + edit_post, cek aksi
 * sah untuk status saat ini, dan catatan wajib untuk revisi/antrean/tolak.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_post_tk_kurasi', 'tk_kurasi_handle' );

/**
 * Jalankan aksi kurasi dari Dashboard atau Panel Kurator.
 *
 * Aksi yang boleh dilakukan bergantung pada status saat ini
 * (lihat tk_aksi_diizinkan() di includes/kurasi/data.php).
 *
 * Field POST:
 *   post_id, aksi, catatan, _tk_nonce
 *   kembali = 'panel' → setelah aksi, kembali ke halaman tradisi itu
 *             (kosong → kembali ke Dashboard Kurasi).
 */
function tk_kurasi_handle() {
    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    $aksi    = isset( $_POST['aksi'] ) ? sanitize_key( $_POST['aksi'] ) : '';
    $catatan = isset( $_POST['catatan'] ) ? sanitize_textarea_field( wp_unslash( $_POST['catatan'] ) ) : '';
    $panel   = isset( $_POST['kembali'] ) && 'panel' === $_POST['kembali'];

    check_admin_referer( 'tk_kurasi_' . $post_id, '_tk_nonce' );

    if ( ! current_user_can( 'tk_kurasi' ) || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_die( 'Anda tidak berhak melakukan kurasi.', 403 );
    }
    if ( 'tradisi' !== get_post_type( $post_id ) || ! in_array( $aksi, tk_aksi_diizinkan( get_post_status( $post_id ) ), true ) ) {
        tk_kurasi_redirect( 'gagal', $panel ? $post_id : 0 );
    }
    if ( in_array( $aksi, tk_aksi_wajib_catatan(), true ) && '' === trim( $catatan ) ) {
        tk_kurasi_redirect( 'catatan_kosong', $panel ? $post_id : 0 );
    }

    $GLOBALS['tk_aksi_dashboard'] = true; // Agar tidak dicatat dua kali oleh hook transisi status.

    switch ( $aksi ) {
        case 'terbitkan':
            wp_publish_post( $post_id );
            delete_post_meta( $post_id, '_tk_catatan' );
            delete_post_meta( $post_id, '_tk_perlu_revisi' );
            tk_log_tambah( $post_id, 'terbitkan', $catatan );
            tk_email_ke_pengirim( $post_id, 'terbitkan' );
            break;

        case 'revisi':
            wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
            update_post_meta( $post_id, '_tk_perlu_revisi', 1 );
            update_post_meta( $post_id, '_tk_catatan', $catatan );
            tk_log_tambah( $post_id, 'revisi', $catatan );
            tk_email_ke_pengirim( $post_id, 'revisi', $catatan ); // Berisi link revisi (token untuk tamu).
            break;

        case 'antrean':
            // Tarik dari publikasi / keluarkan dari draf → kembali menunggu kurasi.
            wp_update_post( array( 'ID' => $post_id, 'post_status' => 'pending' ) );
            delete_post_meta( $post_id, '_tk_perlu_revisi' );
            tk_log_tambah( $post_id, 'antrean', $catatan );
            break;

        case 'tolak':
            update_post_meta( $post_id, '_tk_catatan', $catatan );
            tk_log_tambah( $post_id, 'tolak', $catatan );
            tk_email_ke_pengirim( $post_id, 'tolak', $catatan );
            wp_trash_post( $post_id );
            $panel = false; // Tradisi di Trash tidak bisa dibuka; kembali ke dashboard.
            break;
    }

    tk_kurasi_redirect( $aksi, $panel ? $post_id : 0 );
}

/**
 * Kembali ke Dashboard Kurasi (atau ke halaman tradisi) dengan kode pesan.
 *
 * @param string $hasil
 * @param int    $post_id Kalau diisi, kembali ke halaman tradisi ini.
 */
function tk_kurasi_redirect( $hasil, $post_id = 0 ) {
    $tujuan = $post_id ? tk_url_tinjau( $post_id ) : tk_url_kurasi();
    wp_safe_redirect( add_query_arg( 'kurasi', $hasil, $tujuan ) . ( $post_id ? '#panel-kurator' : '' ) );
    exit;
}
