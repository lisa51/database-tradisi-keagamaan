<?php
/**
 * Shortcode [tk_peta]: peta interaktif sebaran tradisi (Leaflet + OpenStreetMap).
 *
 * Pemakaian:
 *   [tk_peta]
 *   [tk_peta tinggi="600"]
 *
 * Atribut:
 *   tinggi  Tinggi peta dalam piksel. Default 520.
 *
 * Tampilan:
 *   Kiri  : peta dengan pin untuk setiap tradisi yang punya koordinat.
 *   Kanan : panel detail. Klik pin → panel menampilkan foto, judul,
 *           lokasi, deskripsi, dan tombol "Lihat Detail".
 *
 * Tradisi hanya muncul di peta kalau field "latitude" dan "longitude"
 * sudah diisi (lihat includes/core/acf-fields.php).
 *
 * Cara kerja teknis:
 *   PHP mengumpulkan data titik → dikirim sebagai JSON di atribut
 *   data-titik → assets/js/peta.js membaca JSON itu dan menggambar peta.
 *   Satu script yang sama dipakai juga untuk peta kecil di halaman single.
 *
 * CATATAN DEPLOYMENT: gambar peta (tile) diambil dari server OpenStreetMap,
 * jadi pengunjung perlu akses internet. Lihat README bagian "Peta".
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'tk_peta', 'tk_peta_shortcode' );

/**
 * Render shortcode [tk_peta].
 *
 * @param array $atts Atribut shortcode.
 * @return string HTML.
 */
function tk_peta_shortcode( $atts ) {
    $atts  = shortcode_atts( array( 'tinggi' => 520 ), $atts, 'tk_peta' );
    $titik = tk_peta_get_titik();

    return tk_peta_render( $titik, array(
        'tinggi' => absint( $atts['tinggi'] ),
        'panel'  => true,
    ) );
}

/**
 * Muat CSS & JS peta (dipanggil otomatis oleh tk_peta_render()).
 */
function tk_peta_enqueue() {
    wp_enqueue_style( 'tk-leaflet' );
    wp_enqueue_script( 'tk-peta' );
}

/**
 * Ambil koordinat valid sebuah tradisi.
 *
 * @param int $id ID tradisi.
 * @return float[]|null array( lat, lng ), atau null kalau kosong/tidak valid.
 */
function tk_peta_get_koordinat( $id ) {
    $lat = get_post_meta( $id, 'latitude', true );
    $lng = get_post_meta( $id, 'longitude', true );

    if ( ! is_numeric( $lat ) || ! is_numeric( $lng ) ) {
        return null;
    }

    $lat = (float) $lat;
    $lng = (float) $lng;

    if ( abs( $lat ) > 90 || abs( $lng ) > 180 ) {
        return null;
    }

    return array( $lat, $lng );
}

/**
 * Kumpulkan data titik peta.
 *
 * @param int[]|null $ids ID tradisi tertentu. null = semua tradisi terbit.
 * @return array[] Daftar titik: lat, lng, judul, url, lokasi, kategori, desk, gambar.
 */
function tk_peta_get_titik( $ids = null ) {
    if ( null === $ids ) {
        $ids = get_posts( array(
            'post_type'      => 'tradisi',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ) );
    }

    $titik = array();
    foreach ( $ids as $id ) {
        $koordinat = tk_peta_get_koordinat( $id );
        if ( ! $koordinat ) {
            continue; // Belum punya koordinat → tidak tampil di peta.
        }

        $lokasi = array_filter( array(
            get_post_meta( $id, 'asal_daerah', true ),
            tk_term_names( $id, 'wilayah' ),
        ) );

        $titik[] = array(
            'lat'      => $koordinat[0],
            'lng'      => $koordinat[1],
            // Judul di-decode karena JS menampilkannya sebagai teks biasa
            // (get_the_title() mengubah tanda seperti "–" menjadi &#8211;).
            'judul'    => html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ),
            'url'      => get_permalink( $id ),
            'lokasi'   => implode( ', ', $lokasi ),
            'kategori' => tk_term_names( $id, 'kategori-tradisi' ),
            'desk'     => wp_trim_words( (string) get_post_meta( $id, 'deskripsi_singkat', true ), 22 ),
            'gambar'   => (string) get_the_post_thumbnail_url( $id, 'medium_large' ),
        );
    }

    return $titik;
}

/**
 * HTML wadah peta. Dipakai [tk_peta] dan halaman single.
 *
 * @param array[] $titik Hasil tk_peta_get_titik().
 * @param array   $opsi  {
 *     @type int  $tinggi Tinggi peta (px). Default 520.
 *     @type bool $panel  Tampilkan panel detail di samping. Default true.
 * }
 * @return string HTML.
 */
function tk_peta_render( $titik, $opsi = array() ) {
    $opsi = wp_parse_args( $opsi, array( 'tinggi' => 520, 'panel' => true ) );

    if ( ! $titik ) {
        return '<p class="tk-kosong">Belum ada tradisi dengan koordinat peta.</p>';
    }

    tk_peta_enqueue();

    ob_start();
    ?>
    <div class="tk-peta-wrap<?php echo $opsi['panel'] ? ' tk-peta-wrap--panel' : ''; ?>">
      <div class="tk-peta"
           style="height: <?php echo absint( $opsi['tinggi'] ); ?>px"
           data-titik="<?php echo esc_attr( wp_json_encode( $titik ) ); ?>"
           role="region" aria-label="Peta sebaran tradisi"></div>

      <?php if ( $opsi['panel'] ) : ?>
        <aside class="tk-peta-panel" aria-live="polite">
          <span class="tk-label">Peta Digital</span>
          <h3 class="tk-peta-panel-judul"><?php echo esc_html( number_format_i18n( count( $titik ) ) ); ?> tradisi di peta</h3>
          <p class="tk-peta-panel-desk">Klik salah satu pin untuk melihat detail tradisi.</p>
        </aside>
      <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
