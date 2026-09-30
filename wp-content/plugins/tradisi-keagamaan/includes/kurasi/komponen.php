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
    // phpcs:disable WordPress.Security.NonceVerification -- hanya menampilkan pesan.
    $hasil = isset( $_GET['kurasi'] ) ? sanitize_key( $_GET['kurasi'] ) : '';

    // Aksi massal: "N koleksi diterbitkan. M dilewati ..."
    if ( 'massal' === $hasil ) {
        $n     = isset( $_GET['n'] ) ? absint( $_GET['n'] ) : 0;
        $lewat = isset( $_GET['lewat'] ) ? absint( $_GET['lewat'] ) : 0;
        $aksi  = isset( $_GET['aksi_massal'] ) ? sanitize_key( $_GET['aksi_massal'] ) : '';
        $kata  = array( 'terbitkan' => 'diterbitkan', 'revisi' => 'dikembalikan untuk revisi', 'antrean' => 'dikembalikan ke antrean', 'tolak' => 'ditolak (dipindah ke Trash)' );
        $teks  = sprintf( '%d koleksi %s.', $n, isset( $kata[ $aksi ] ) ? $kata[ $aksi ] : 'diproses' );
        if ( $lewat ) {
            $teks .= sprintf( ' %d dilewati karena aksi ini tidak berlaku untuk statusnya.', $lewat );
        }
        return sprintf( '<div class="tk-notice tk-notice--%s">%s</div>', $n ? 'sukses' : 'gagal', esc_html( $teks ) );
    }
    // phpcs:enable

    $pesan = array(
        'massal_kosong' => array( 'gagal', 'Pilih minimal satu koleksi dan satu aksi massal.' ),
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
 * Ringkasan usulan perubahan untuk kurator (Dashboard & Panel):
 * "Untuk versi terbit: <tautan> · Diubah: Judul, Kategori".
 *
 * @param int $usulan_id
 * @return string HTML (sudah di-escape).
 */
function tk_kurasi_ringkasan_usulan( $usulan_id ) {
    $asal = tk_usulan_asal( $usulan_id );
    $ubah = tk_usulan_perubahan( $usulan_id );
    return sprintf(
        'Untuk versi terbit: <a href="%s" target="_blank" rel="noopener">%s</a> · Diubah: %s',
        esc_url( get_permalink( $asal ) ),
        esc_html( get_the_title( $asal ) ),
        $ubah ? esc_html( implode( ', ', $ubah ) ) : '<em>tidak ada perbedaan</em>'
    );
}

/**
 * Nomor halaman (.tk-halaman) untuk daftar di Dashboard Kurasi.
 *
 * @param string $base    URL dengan %#% di tempat nomor halaman (boleh berakhiran #anchor).
 * @param int    $current
 * @param int    $total
 * @param string $label   aria-label navigasi.
 * @return string HTML, atau '' bila hanya satu halaman.
 */
function tk_kurasi_render_halaman( $base, $current, $total, $label ) {
    if ( $total < 2 ) {
        return '';
    }
    return '<nav class="tk-halaman" aria-label="' . esc_attr( $label ) . '">' . paginate_links( array(
        'base'      => $base,
        'format'    => '',
        'current'   => $current,
        'total'     => $total,
        'prev_text' => '‹ Sebelumnya',
        'next_text' => 'Berikutnya ›',
    ) ) . '</nav>';
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
