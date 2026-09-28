<?php
/**
 * Token revisi untuk kontributor tamu.
 *
 * Saat kurator meminta revisi pada kiriman tamu, dibuat token rahasia baru
 * (meta _tk_token). Link ?edit=ID&token=... hanya berlaku selama kiriman
 * berstatus draf, dan tidak berlaku lagi setelah dikirim ulang.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Buat token baru (menggantikan yang lama) dan kembalikan nilainya.
 *
 * @param int $post_id
 * @return string
 */
function tk_token_buat( $post_id ) {
    $token = wp_generate_password( 32, false );
    update_post_meta( $post_id, '_tk_token', $token );
    return $token;
}

/**
 * Apakah token cocok dan tradisi masih menunggu revisi (status draf)?
 *
 * @param int    $post_id
 * @param string $token
 * @return bool
 */
function tk_token_cocok( $post_id, $token ) {
    $simpan = (string) get_post_meta( $post_id, '_tk_token', true );

    return '' !== $simpan
        && '' !== $token
        && hash_equals( $simpan, $token )
        && 'draft' === get_post_status( $post_id );
}

/**
 * URL untuk melanjutkan/merevisi kiriman.
 * Akun → form?edit=ID. Tamu → form?edit=ID&token=... (token baru dibuat).
 *
 * @param int $post_id
 * @return string
 */
function tk_url_revisi( $post_id ) {
    $args = array( 'edit' => $post_id );
    if ( tk_is_kiriman_tamu( $post_id ) ) {
        $args['token'] = tk_token_buat( $post_id );
    }
    return add_query_arg( $args, tk_url_tambah() );
}
