<?php
/**
 * Tambahan di wp-admin: kotak "Pengirim & Riwayat Kurasi" di sidebar editor tradisi.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'add_meta_boxes_tradisi', 'tk_meta_box_riwayat' );

/** Daftarkan kotak Riwayat Kurasi di sidebar editor tradisi. */
function tk_meta_box_riwayat() {
    add_meta_box( 'tk-riwayat', 'Pengirim & Riwayat Kurasi', 'tk_meta_box_riwayat_isi', 'tradisi', 'side' );
}

/** @param WP_Post $post */
function tk_meta_box_riwayat_isi( $post ) {
    printf(
        '<p><strong>%s</strong>%s<br><a href="mailto:%3$s">%3$s</a></p>',
        esc_html( tk_nama_pengirim( $post->ID ) ),
        tk_is_kiriman_tamu( $post->ID ) ? ' (tamu)' : '',
        esc_attr( tk_email_pengirim( $post->ID ) )
    );
    $instansi = get_post_meta( $post->ID, 'tk_tamu_instansi', true );
    if ( $instansi ) {
        echo '<p>' . esc_html( $instansi ) . '</p>';
    }
    echo tk_log_render( $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput -- sudah di-escape di fungsi.
}
