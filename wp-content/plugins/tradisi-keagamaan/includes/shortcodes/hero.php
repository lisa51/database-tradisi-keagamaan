<?php
/**
 * Shortcode [tk_hero]: bagian pembuka Beranda.
 *
 * Isi: label kecil, judul besar, paragraf, dua tombol, tiga poin,
 * dan kartu tradisi unggulan di sebelah kanan.
 *
 * Pemakaian:
 *   [tk_hero]
 *   [tk_hero id="62"]
 *   [tk_hero judul="..." deskripsi="..." label="..."]
 *
 * Atribut (semuanya opsional):
 *   label      Teks kecil di atas judul.
 *   judul      Judul besar (H1).
 *   deskripsi  Paragraf di bawah judul.
 *   id         ID tradisi untuk kartu unggulan. Kalau kosong/tidak valid,
 *              dipilih otomatis: pembaca terbanyak, lalu yang terbaru.
 *
 * Gunakan tanda kutip lurus (") dan tulis di block "Shortcode",
 * bukan block Paragraph.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'tk_hero', 'tk_hero_shortcode' );

/**
 * Render shortcode [tk_hero].
 *
 * @param array $atts Atribut shortcode.
 * @return string HTML.
 */
function tk_hero_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'label'     => 'Database Digital Tradisi Keagamaan Indonesia',
        'judul'     => 'Mengenal, Mendokumentasikan, dan Merawat Tradisi Keagamaan Indonesia',
        'deskripsi' => 'WARISI adalah ruang digital untuk mendokumentasikan kekayaan tradisi keagamaan yang tumbuh dan berkembang di berbagai daerah Indonesia.',
        'id'        => 0,
    ), $atts, 'tk_hero' );

    // Teks tiga poin kecil di bawah tombol (warna titik: hijau, emas, oranye).
    $poin = array(
        'hijau'  => 'Format Standar Arsip',
        'emas'   => 'Kurasi Antar Wilayah',
        'oranye' => 'Aksesibilitas Terbuka',
    );

    $unggulan = tk_hero_get_unggulan( absint( $atts['id'] ) );

    ob_start();
    ?>
    <section class="tk-hero">
      <div class="tk-hero-teks">
        <span class="tk-hero-label"><?php echo esc_html( $atts['label'] ); ?></span>
        <h1 class="tk-hero-judul"><?php echo esc_html( $atts['judul'] ); ?></h1>
        <p class="tk-hero-desk"><?php echo esc_html( $atts['deskripsi'] ); ?></p>

        <div class="tk-hero-tombol">
          <a class="tk-btn tk-btn-utama" href="<?php echo esc_url( tk_url_jelajahi() ); ?>">Jelajahi Tradisi</a>
          <a class="tk-btn tk-btn-garis" href="<?php echo esc_url( tk_url_tambah() ); ?>">+ Tambah Tradisi</a>
        </div>

        <ul class="tk-hero-poin">
          <?php foreach ( $poin as $warna => $teks ) : ?>
            <li><span class="tk-dot tk-dot-<?php echo esc_attr( $warna ); ?>"></span><?php echo esc_html( $teks ); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <?php if ( $unggulan ) { echo tk_hero_render_kartu( $unggulan->ID ); } ?>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Pilih tradisi unggulan.
 *
 * Urutan: ID dari atribut → pembaca terbanyak → tradisi terbaru.
 *
 * @param int $id ID dari atribut shortcode (0 = otomatis).
 * @return WP_Post|null
 */
function tk_hero_get_unggulan( $id ) {
    if ( $id && 'tradisi' === get_post_type( $id ) && 'publish' === get_post_status( $id ) ) {
        return get_post( $id );
    }

    $dasar = array(
        'post_type'      => 'tradisi',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
    );

    $hasil = get_posts( $dasar + array(
        'meta_key' => TK_VIEW_META,
        'orderby'  => 'meta_value_num',
        'order'    => 'DESC',
    ) );

    if ( ! $hasil ) {
        $hasil = get_posts( $dasar );
    }

    return $hasil ? $hasil[0] : null;
}

/**
 * HTML kartu tradisi unggulan (sisi kanan hero).
 *
 * @param int $id ID tradisi.
 * @return string HTML.
 */
function tk_hero_render_kartu( $id ) {
    $wilayah = tk_term_names( $id, 'wilayah', ', ', 1 );
    $desk    = get_post_meta( $id, 'deskripsi_singkat', true );

    ob_start();
    ?>
    <a class="tk-hero-kartu" href="<?php echo esc_url( get_permalink( $id ) ); ?>">
      <div class="tk-hero-media">
        <?php echo get_the_post_thumbnail( $id, 'large' ); ?>
        <div class="tk-hero-overlay">
          <?php if ( $wilayah ) : ?>
            <span class="tk-hero-lokasi">Tradisi <?php echo esc_html( $wilayah ); ?></span>
          <?php endif; ?>
          <strong class="tk-hero-kartu-judul"><?php echo esc_html( get_the_title( $id ) ); ?></strong>
          <?php if ( $desk ) : ?>
            <span class="tk-hero-kartu-desk"><?php echo esc_html( wp_trim_words( $desk, 14 ) ); ?></span>
          <?php endif; ?>
        </div>
      </div>
      <div class="tk-hero-kartu-kaki">
        <span>✓ Koleksi Terverifikasi Kurator</span>
        <span><?php echo esc_html( number_format_i18n( tk_get_view_count( $id ) ) ); ?> pembaca</span>
      </div>
    </a>
    <?php
    return ob_get_clean();
}
