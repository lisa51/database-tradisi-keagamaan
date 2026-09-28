<?php
/**
 * Penghitung pembaca (view counter) untuk halaman single tradisi.
 *
 * Cara kerja:
 *   Setiap kali halaman /tradisi/... dibuka, nilai post meta TK_VIEW_META ('tk_view_count', lihat config.php)
 *   bertambah 1. Nilai ini ditampilkan sebagai "Pembaca" dan dipakai [tk_hero]
 *   untuk memilih tradisi unggulan (pembaca terbanyak).
 *
 * Catatan:
 *   - Setiap refresh ikut terhitung (tidak dibedakan per pengunjung).
 *   - Pratinjau dan kunjungan kurator/admin tidak dihitung.
 *   - Kalau nanti dipasang plugin cache halaman, hitungan bisa tidak
 *     bertambah karena PHP tidak dijalankan untuk halaman yang di-cache.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_head', 'tk_track_view_count' );

/**
 * Tambah 1 hitungan pembaca saat halaman single tradisi dibuka.
 */
function tk_track_view_count() {
    if ( ! is_singular( 'tradisi' ) || is_admin() || is_preview() ) {
        return;
    }
    if ( current_user_can( 'edit_posts' ) ) {
        return; // Kunjungan kurator/admin saat meninjau tidak dihitung sebagai pembaca.
    }
    $post_id = get_the_ID();
    update_post_meta( $post_id, TK_VIEW_META, tk_get_view_count( $post_id ) + 1 );
}

/**
 * Ambil jumlah pembaca sebuah tradisi. Dipakai di template dan shortcode.
 *
 * @param int $post_id ID tradisi.
 * @return int
 */
function tk_get_view_count( $post_id ) {
    return (int) get_post_meta( $post_id, TK_VIEW_META, true );
}
