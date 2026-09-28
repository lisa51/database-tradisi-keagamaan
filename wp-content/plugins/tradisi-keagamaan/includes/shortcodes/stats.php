<?php
/**
 * Shortcode [tk_stats]: tiga kotak angka ringkasan di Beranda.
 *
 * Pemakaian:
 *   [tk_stats]
 *
 * Sumber angka (hanya tradisi berstatus "publish"):
 *   Tradisi         Jumlah post "tradisi".
 *   Provinsi        Jumlah term "wilayah" level teratas yang dipakai.
 *   Kabupaten/Kota  Jumlah nilai unik field "asal_daerah".
 *
 * CATATAN: kalau struktur "wilayah" diubah menjadi 2 level
 * (pulau → provinsi), fungsi tk_stats_count_provinsi() perlu disesuaikan.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'tk_stats', 'tk_stats_shortcode' );

/**
 * Render shortcode [tk_stats].
 *
 * @return string HTML.
 */
function tk_stats_shortcode() {
    $items = array(
        'Tradisi'        => (int) wp_count_posts( 'tradisi' )->publish,
        'Provinsi'       => tk_stats_count_provinsi(),
        'Kabupaten/Kota' => tk_stats_count_kabupaten(),
    );

    $html = '<div class="tk-stats">';
    foreach ( $items as $label => $angka ) {
        $html .= sprintf(
            '<div class="tk-stat"><span class="tk-stat-angka">%s</span><span class="tk-stat-label">%s</span></div>',
            esc_html( number_format_i18n( $angka ) ),
            esc_html( $label )
        );
    }
    return $html . '</div>';
}

/**
 * Jumlah provinsi = term "wilayah" level teratas yang punya tradisi.
 *
 * @return int
 */
function tk_stats_count_provinsi() {
    $jumlah = wp_count_terms( array(
        'taxonomy'   => 'wilayah',
        'hide_empty' => true,
        'parent'     => 0,
    ) );
    return is_wp_error( $jumlah ) ? 0 : (int) $jumlah;
}

/**
 * Jumlah kabupaten/kota = nilai unik "asal_daerah" pada tradisi terbit.
 *
 * @return int
 */
function tk_stats_count_kabupaten() {
    global $wpdb;

    return (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(DISTINCT TRIM(pm.meta_value))
         FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = %s
           AND pm.meta_value <> ''
           AND p.post_type = %s
           AND p.post_status = 'publish'",
        'asal_daerah',
        'tradisi'
    ) );
}
