<?php
/**
 * Shortcode [tk_hero]: bagian pembuka Beranda.
 *
 * Isi: label kecil, judul besar, paragraf, dua tombol, tiga poin,
 * dan slideshow kartu koleksi terbaru di sebelah kanan (assets/js/hero.js).
 *
 * Pemakaian:
 *   [tk_hero]
 *   [tk_hero jumlah="3"]
 *   [tk_hero judul="..." deskripsi="..." label="..."]
 *
 * Atribut (semuanya opsional):
 *   label      Teks kecil di atas judul.
 *   judul      Judul besar (H1).
 *   deskripsi  Paragraf di bawah judul.
 *   jumlah     Banyak slide (1–10, default 5): koleksi terbit terbaru.
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
        'label'     => 'Database Digital Khazanah Keagamaan Indonesia',
        'judul'     => 'Merawat Khazanah Keagamaan Nusantara',
        'deskripsi' => 'Telusuri tradisi dan benda budaya keagamaan dari berbagai daerah, atau bagikan yang Anda kenal.',
        'jumlah'    => 5,
    ), $atts, 'tk_hero' );

    // Teks tiga poin kecil di bawah tombol (warna titik: hijau, emas, oranye).
    $poin = array(
        'hijau'  => 'Format Standar Arsip',
        'emas'   => 'Kurasi Antar Wilayah',
        'oranye' => 'Aksesibilitas Terbuka',
    );

    $terbaru = tk_hero_get_terbaru( min( 10, max( 1, absint( $atts['jumlah'] ) ) ) );

    ob_start();
    ?>
    <section class="tk-hero">
      <div class="tk-hero-teks">
        <span class="tk-hero-label"><?php echo esc_html( $atts['label'] ); ?></span>
        <h1 class="tk-hero-judul"><?php echo esc_html( $atts['judul'] ); ?></h1>
        <p class="tk-hero-desk"><?php echo esc_html( $atts['deskripsi'] ); ?></p>

        <div class="tk-hero-tombol">
          <a class="tk-btn tk-btn-utama" href="<?php echo esc_url( tk_url_jelajahi() ); ?>">Jelajahi Khazanah</a>
          <a class="tk-btn tk-btn-garis" href="<?php echo esc_url( tk_url_tambah() ); ?>">+ Berkontribusi</a>
        </div>

        <ul class="tk-hero-poin">
          <?php foreach ( $poin as $warna => $teks ) : ?>
            <li><span class="tk-dot tk-dot-<?php echo esc_attr( $warna ); ?>"></span><?php echo esc_html( $teks ); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <?php if ( $terbaru ) { echo tk_hero_render_slider( $terbaru ); } ?>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Koleksi terbit terbaru untuk slideshow.
 *
 * Satu query: meta, term, dan foto semua slide dimuat sekaligus ke cache,
 * jadi kartu tidak menambah query per slide.
 *
 * @param int $jumlah Banyak koleksi.
 * @return int[] ID tradisi, terbaru lebih dulu.
 */
function tk_hero_get_terbaru( $jumlah ) {
    $q = new WP_Query( array(
        'post_type'           => 'tradisi',
        'post_status'         => 'publish',
        'posts_per_page'      => $jumlah,
        'orderby'             => 'date',
        'order'               => 'DESC',
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
    ) );
    update_post_thumbnail_cache( $q );
    return wp_list_pluck( $q->posts, 'ID' );
}

/**
 * HTML slideshow (sisi kanan hero): bingkai, jalur slide, dan navigasi.
 * Tanpa JS tetap bisa digeser (scroll-snap); hero.js menambah putar
 * otomatis, tombol ‹ ›, dan titik. Navigasi disembunyikan bila 1 slide.
 *
 * @param int[] $ids ID tradisi.
 * @return string HTML.
 */
function tk_hero_render_slider( $ids ) {
    $total = count( $ids );

    if ( $total > 1 ) {
        $js = 'assets/js/hero.js';
        wp_enqueue_script( 'tk-hero', TK_URL . $js, array(), filemtime( TK_PATH . $js ), true );
    }

    ob_start();
    ?>
    <div class="tk-hero-slider" data-tk-slider aria-roledescription="carousel" aria-label="Koleksi terbaru">
      <div class="tk-hero-slides" data-tk-slider-jalur>
        <?php foreach ( $ids as $i => $id ) : ?>
          <div class="tk-hero-slide" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr( ( $i + 1 ) . ' dari ' . $total ); ?>">
            <?php echo tk_hero_render_kartu( $id, 0 === $i ); ?>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ( $total > 1 ) : ?>
        <div class="tk-hero-nav" hidden data-tk-slider-nav>
          <button type="button" class="tk-hero-panah" data-tk-slider-arah="-1" aria-label="Slide sebelumnya">‹</button>
          <div class="tk-hero-titik">
            <?php for ( $i = 0; $i < $total; $i++ ) : ?>
              <button type="button" data-tk-slider-titik="<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr( 'Slide ' . ( $i + 1 ) ); ?>"></button>
            <?php endfor; ?>
          </div>
          <button type="button" class="tk-hero-panah" data-tk-slider-arah="1" aria-label="Slide berikutnya">›</button>
        </div>
      <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * HTML kartu koleksi dalam satu slide.
 *
 * @param int  $id      ID tradisi.
 * @param bool $pertama Slide pertama: foto dimuat langsung, sisanya lazy.
 * @return string HTML.
 */
function tk_hero_render_kartu( $id, $pertama ) {
    $wilayah = tk_term_names( $id, 'wilayah', ', ', 1 );
    $desk    = get_post_meta( $id, 'deskripsi_singkat', true );

    ob_start();
    ?>
    <a class="tk-hero-kartu" href="<?php echo esc_url( get_permalink( $id ) ); ?>">
      <div class="tk-hero-media tk-media-cover">
        <?php echo get_the_post_thumbnail( $id, 'large', array( 'loading' => $pertama ? 'eager' : 'lazy' ) ); ?>
        <div class="tk-hero-overlay">
          <?php if ( $wilayah ) : ?>
            <span class="tk-hero-lokasi"><?php echo esc_html( TK_JENIS[ tk_get_jenis( $id ) ] . ' ' . $wilayah ); ?></span>
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
