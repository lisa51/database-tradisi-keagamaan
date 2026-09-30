<?php
/**
 * Potongan HTML yang dipakai bersama Dashboard Kurasi dan Panel Kurator.
 *
 *   tk_kurasi_render_pesan()      pesan hasil aksi (?kurasi=...)
 *   tk_kurasi_form_buka()         pembuka form aksi (nonce + field tersembunyi)
 *   tk_kurasi_render_checklist()  checklist kelengkapan ✓/✗
 *   tk_log_render()               daftar riwayat kurasi
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Pesan hasil aksi terakhir (?kurasi=...).
 *
 * @return string
 */
function tk_kurasi_render_pesan() {
    $hasil = isset( $_GET['kurasi'] ) ? sanitize_key( $_GET['kurasi'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- hanya menampilkan pesan.

    $pesan = array(
        'terbitkan'     => array( 'sukses', 'Tradisi diterbitkan dan pengirim sudah diberi tahu.' ),
        'revisi'        => array( 'info', 'Kiriman dikembalikan untuk revisi. Pengirim sudah menerima catatan Anda.' ),
        'tolak'         => array( 'info', 'Kiriman ditolak dan dipindah ke Trash (bisa dipulihkan dalam 30 hari). Pengirim sudah menerima alasannya.' ),
        'antrean'       => array( 'info', 'Tradisi dikembalikan ke antrean kurasi (status Menunggu Kurasi).' ),
        'terapkan'      => array( 'sukses', 'Usulan perubahan diterapkan ke versi terbit, dan pengirim sudah diberi tahu.' ),
        'ubah'          => array( 'sukses', 'Perubahan tersimpan.' ),
        'catatan_kosong'=> array( 'gagal', 'Catatan wajib diisi untuk Minta Revisi, Tolak, atau Kembalikan ke Antrean.' ),
        'gagal'         => array( 'gagal', 'Aksi gagal atau tidak berlaku untuk status tradisi saat ini. Muat ulang halaman lalu coba lagi.' ),
    );

    if ( ! isset( $pesan[ $hasil ] ) ) {
        return '';
    }

    return sprintf(
        '<div class="tk-notice tk-notice--%s">%s</div>',
        esc_attr( $pesan[ $hasil ][0] ),
        esc_html( $pesan[ $hasil ][1] )
    );
}

/**
 * Awal form POST aksi kurasi (nonce + field tersembunyi).
 *
 * @param int  $post_id
 * @param bool $dari_panel true = setelah aksi kembali ke halaman tradisi.
 * @return string
 */
function tk_kurasi_form_buka( $post_id, $dari_panel = false ) {
    return '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">'
        . '<input type="hidden" name="action" value="tk_kurasi">'
        . '<input type="hidden" name="post_id" value="' . absint( $post_id ) . '">'
        . ( $dari_panel ? '<input type="hidden" name="kembali" value="panel">' : '' )
        . wp_nonce_field( 'tk_kurasi_' . $post_id, '_tk_nonce', true, false );
}

/**
 * Checklist kelengkapan sebagai deretan label ✓ / ✗.
 *
 * @param int $post_id
 * @return string
 */
function tk_kurasi_render_checklist( $post_id ) {
    $cek     = tk_kelengkapan( $post_id );
    $lengkap = count( array_filter( $cek ) );

    $html = sprintf(
        '<div class="tk-cek"><span class="tk-cek-skor">%d/%d lengkap</span>',
        $lengkap,
        count( $cek )
    );
    foreach ( $cek as $label => $ok ) {
        $html .= sprintf(
            '<span class="tk-cek-item tk-cek-item--%s">%s %s</span>',
            $ok ? 'ok' : 'kurang',
            $ok ? '✓' : '✗',
            esc_html( $label )
        );
    }
    return $html . '</div>';
}

/**
 * HTML daftar riwayat (dipakai dashboard & wp-admin).
 *
 * @param int $post_id
 * @return string
 */
function tk_log_render( $post_id ) {
    $log = tk_log_get( $post_id );
    if ( ! $log ) {
        return '<p class="tk-log-kosong">Belum ada riwayat.</p>';
    }

    $label = tk_log_label();
    $html  = '<ol class="tk-log">';
    foreach ( array_reverse( $log ) as $baris ) {
        $html .= sprintf(
            '<li class="tk-log--%1$s"><strong>%2$s</strong> oleh %3$s <span class="tk-log-waktu">%4$s</span>%5$s</li>',
            esc_attr( $baris['aksi'] ),
            esc_html( isset( $label[ $baris['aksi'] ] ) ? $label[ $baris['aksi'] ] : $baris['aksi'] ),
            esc_html( $baris['oleh'] ),
            esc_html( mysql2date( 'j M Y, H:i', $baris['waktu'] ) ),
            $baris['catatan'] ? '<blockquote>' . esc_html( $baris['catatan'] ) . '</blockquote>' : ''
        );
    }
    return $html . '</ol>';
}
