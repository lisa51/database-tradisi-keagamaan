<?php
/**
 * Shortcode [tk_stats]: tiga kotak angka ringkasan di Beranda.
 *
 * Pemakaian:
 *   [tk_stats]
 *
 * Tampilan tiap kotak (sesuai draf):
 *   ┌─────────────────────────────┐
 *   │ 24                    [ikon] │
 *   │ Tradisi Terdokumentasi       │
 *   │ keterangan kecil             │
 *   └─────────────────────────────┘
 *
 * Sumber angka (hanya tradisi berstatus "publish"):
 *   Tradisi         Jumlah post "tradisi".
 *   Provinsi        Jumlah term "wilayah" level teratas yang dipakai.
 *   Kabupaten/Kota  Jumlah nilai unik field "asal_daerah".
 *
 * Untuk mengubah label, keterangan, ikon, atau warna ikon, cukup ubah
 * array $items di tk_stats_shortcode(). Warna ikon yang tersedia:
 * 'oranye', 'abu', 'emas' (lihat .tk-stat-ikon--* di warisi.css).
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
        array(
            'angka' => (int) wp_count_posts( 'tradisi' )->publish,
            'label' => 'Tradisi Terdokumentasi',
            'ket'   => 'Tercatat dalam arsip WARISI',
            'ikon'  => 'buku',
            'warna' => 'oranye',
        ),
        array(
            'angka' => tk_stats_count_provinsi(),
            'label' => 'Provinsi',
            'ket'   => 'Persebaran tradisi di Indonesia',
            'ikon'  => 'pin',
            'warna' => 'abu',
        ),
        array(
            'angka' => tk_stats_count_kabupaten(),
            'label' => 'Kabupaten/Kota',
            'ket'   => 'Wilayah asal tradisi',
            'ikon'  => 'gedung',
            'warna' => 'emas',
        ),
    );

    $html = '<div class="tk-stats">';
    foreach ( $items as $item ) {
        $html .= sprintf(
            '<div class="tk-stat">'
                . '<div class="tk-stat-teks">'
                    . '<span class="tk-stat-angka">%1$s</span>'
                    . '<span class="tk-stat-label">%2$s</span>'
                    . '<span class="tk-stat-ket">%3$s</span>'
                . '</div>'
                . '<span class="tk-stat-ikon tk-stat-ikon--%4$s">%5$s</span>'
            . '</div>',
            esc_html( number_format_i18n( $item['angka'] ) ),
            esc_html( $item['label'] ),
            esc_html( $item['ket'] ),
            esc_attr( $item['warna'] ),
            tk_icon( $item['ikon'], 22 )
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
