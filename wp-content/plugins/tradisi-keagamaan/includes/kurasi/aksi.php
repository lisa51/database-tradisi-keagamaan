<?php
/**
 * Pemroses aksi kurasi (POST ke admin-post.php):
 *   action=tk_kurasi         satu koleksi (tombol Dashboard Kurasi & Panel Kurator)
 *   action=tk_kurasi_massal  banyak koleksi sekaligus (aksi massal Dashboard Kurasi)
 * Keduanya memakai tk_kurasi_jalankan().
 * Aksi yang sah bergantung pada status saat ini (tk_aksi_diizinkan()):
 *   terbitkan · revisi · antrean · tolak
 * Untuk usulan perubahan, "terbitkan" menerapkan isi usulan ke versi terbit
 * (tk_usulan_terapkan(), includes/kontribusi/usulan.php).
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

    $hasil = tk_kurasi_jalankan( $post_id, $aksi, $catatan );

    if ( 'terapkan' === $hasil ) {
        $post_id = (int) $GLOBALS['tk_usulan_asal_terakhir']; // Usulan sudah dihapus; kembali ke versi terbit.
    }
    if ( 'tolak' === $hasil ) {
        $panel = false; // Tradisi di Trash tidak bisa dibuka; kembali ke dashboard.
    }
    tk_kurasi_redirect( $hasil ? $hasil : 'gagal', $panel ? $post_id : 0 );
}

/**
 * Jalankan satu aksi kurasi pada satu koleksi. Dipakai tombol per koleksi
 * (tk_kurasi_handle()) dan aksi massal (tk_kurasi_massal_handle()).
 * Izin, status, dan catatan wajib dicek oleh pemanggil.
 *
 * @param int    $post_id
 * @param string $aksi    terbitkan | revisi | antrean | tolak
 * @param string $catatan
 * @return string|false Kode hasil (aksi, atau 'terapkan' untuk usulan), false bila gagal.
 */
function tk_kurasi_jalankan( $post_id, $aksi, $catatan = '' ) {
    $GLOBALS['tk_aksi_dashboard'] = true; // Agar tidak dicatat dua kali oleh hook transisi status.

    // Usulan perubahan: "Terbitkan" = terapkan ke versi terbit, lalu usulan dihapus.
    if ( 'terbitkan' === $aksi && tk_usulan_asal( $post_id ) ) {
        tk_email_ke_pengirim( $post_id, 'terapkan' ); // Sebelum usulan dihapus.
        $asal = tk_usulan_terapkan( $post_id );
        $GLOBALS['tk_usulan_asal_terakhir'] = $asal;
        return $asal ? 'terapkan' : false;
    }

    switch ( $aksi ) {
        case 'terbitkan':
            // Draf tanpa slug (mis. hasil impor): buat slug dari judul, karena
            // wp_publish_post() tidak membuatnya dan URL-nya akan rusak.
            if ( '' === get_post_field( 'post_name', $post_id ) ) {
                wp_update_post( array(
                    'ID'        => $post_id,
                    'post_name' => wp_unique_post_slug( sanitize_title( html_entity_decode( get_the_title( $post_id ) ) ), $post_id, 'publish', 'tradisi', 0 ),
                ) );
            }
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
            break;

        default:
            return false;
    }

    return $aksi;
}

add_action( 'admin_post_tk_kurasi_massal', 'tk_kurasi_massal_handle' );

/**
 * Aksi massal dari Dashboard Kurasi.
 *
 * Field POST: post_ids[], aksi, catatan, _tk_nonce, kembali (URL dashboard + filter).
 * Koleksi yang aksinya tidak berlaku untuk statusnya (mis. Terbitkan pada yang
 * sudah terbit) dilewati dan dihitung.
 */
function tk_kurasi_massal_handle() {
    check_admin_referer( 'tk_kurasi_massal', '_tk_nonce' );

    if ( ! current_user_can( 'tk_kurasi' ) ) {
        wp_die( 'Anda tidak berhak melakukan kurasi.', 403 );
    }

    $ids     = isset( $_POST['post_ids'] ) ? array_filter( array_map( 'absint', (array) $_POST['post_ids'] ) ) : array();
    $aksi    = isset( $_POST['aksi'] ) ? sanitize_key( $_POST['aksi'] ) : '';
    $catatan = isset( $_POST['catatan'] ) ? sanitize_textarea_field( wp_unslash( $_POST['catatan'] ) ) : '';
    $kembali = isset( $_POST['kembali'] ) ? wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['kembali'] ) ), tk_url_kurasi() ) : tk_url_kurasi();

    if ( ! $ids || ! in_array( $aksi, array( 'terbitkan', 'revisi', 'antrean', 'tolak' ), true ) ) {
        wp_safe_redirect( add_query_arg( 'kurasi', 'massal_kosong', $kembali ) );
        exit;
    }
    if ( in_array( $aksi, tk_aksi_wajib_catatan(), true ) && '' === trim( $catatan ) ) {
        wp_safe_redirect( add_query_arg( 'kurasi', 'catatan_kosong', $kembali ) );
        exit;
    }

    $ok    = 0;
    $lewat = 0;
    foreach ( $ids as $id ) {
        if ( 'tradisi' === get_post_type( $id ) && current_user_can( 'edit_post', $id )
            && in_array( $aksi, tk_aksi_diizinkan( get_post_status( $id ) ), true )
            && tk_kurasi_jalankan( $id, $aksi, $catatan ) ) {
            $ok++;
        } else {
            $lewat++;
        }
    }

    wp_safe_redirect( add_query_arg( array( 'kurasi' => 'massal', 'n' => $ok, 'lewat' => $lewat, 'aksi_massal' => $aksi ), $kembali ) );
    exit;
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
