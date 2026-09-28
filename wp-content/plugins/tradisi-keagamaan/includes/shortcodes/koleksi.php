<?php
/**
 * Shortcode [tk_koleksi]: panel pencarian + grid card tradisi.
 *
 * Pemakaian:
 *   [tk_koleksi]
 *   [tk_koleksi per_halaman="12"]
 *
 * Atribut:
 *   per_halaman  Jumlah card per halaman. Default 9.
 *
 * Parameter URL yang dibaca (dikirim oleh form pencarian):
 *   ?cari=pasola        Cari kata di judul/isi tradisi.
 *   ?provinsi=bali      Filter berdasarkan slug term "wilayah".
 *   ?hal=2              Nomor halaman (pagination).
 *
 * Panel pencarian punya id="jelajahi", sehingga menu "/#jelajahi"
 * langsung menggulir ke sini.
 *
 * Struktur fungsi:
 *   tk_koleksi_shortcode()     Fungsi utama, merangkai semua bagian.
 *   tk_koleksi_get_filter()    Baca & bersihkan input dari URL.
 *   tk_koleksi_query()         Jalankan WP_Query sesuai filter.
 *   tk_koleksi_render_form()   HTML panel "Jelajahi Tradisi Lokal".
 *   tk_koleksi_render_kartu()  HTML satu card tradisi.
 *   tk_koleksi_render_paging() HTML nomor halaman.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'tk_koleksi', 'tk_koleksi_shortcode' );

/**
 * Render shortcode [tk_koleksi].
 *
 * @param array $atts Atribut shortcode.
 * @return string HTML.
 */
function tk_koleksi_shortcode( $atts ) {
    $atts   = shortcode_atts( array( 'per_halaman' => 9 ), $atts, 'tk_koleksi' );
    $filter = tk_koleksi_get_filter();
    $q      = tk_koleksi_query( $filter, absint( $atts['per_halaman'] ) );

    ob_start();

    echo tk_koleksi_render_form( $filter, $q->found_posts );
    ?>
    <section class="tk-koleksi">
      <div class="tk-koleksi-head">
        <h2>Koleksi Tradisi Lokal</h2>
        <span class="tk-jumlah-kecil"><?php echo esc_html( $q->post_count ); ?> item tampil</span>
      </div>

      <?php if ( $q->have_posts() ) : ?>
        <div class="tk-grid">
          <?php
          while ( $q->have_posts() ) {
              $q->the_post();
              echo tk_koleksi_render_kartu( get_the_ID() );
          }
          ?>
        </div>
        <?php echo tk_koleksi_render_paging( $filter, $q->max_num_pages ); ?>
      <?php else : ?>
        <p class="tk-kosong">Tidak ada tradisi yang cocok dengan pencarian Anda.</p>
      <?php endif; ?>
    </section>
    <?php

    wp_reset_postdata();
    return ob_get_clean();
}

/**
 * Baca input pencarian dari URL dan bersihkan (sanitasi).
 *
 * @return array{cari:string, provinsi:string, hal:int}
 */
function tk_koleksi_get_filter() {
    $get = wp_unslash( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification -- form GET publik, hanya untuk filter.

    return array(
        'cari'     => isset( $get['cari'] ) ? sanitize_text_field( $get['cari'] ) : '',
        'provinsi' => isset( $get['provinsi'] ) ? sanitize_title( $get['provinsi'] ) : '',
        'hal'      => isset( $get['hal'] ) ? max( 1, absint( $get['hal'] ) ) : 1,
    );
}

/**
 * Jalankan query tradisi sesuai filter.
 *
 * @param array $filter      Hasil tk_koleksi_get_filter().
 * @param int   $per_halaman Jumlah card per halaman.
 * @return WP_Query
 */
function tk_koleksi_query( $filter, $per_halaman ) {
    $args = array(
        'post_type'      => 'tradisi',
        'post_status'    => 'publish',
        'posts_per_page' => $per_halaman,
        'paged'          => $filter['hal'],
    );

    if ( '' !== $filter['cari'] ) {
        $args['s'] = $filter['cari'];
    }

    if ( '' !== $filter['provinsi'] ) {
        $args['tax_query'] = array( array(
            'taxonomy' => 'wilayah',
            'field'    => 'slug',
            'terms'    => $filter['provinsi'],
        ) );
    }

    return new WP_Query( $args );
}

/**
 * HTML panel "Jelajahi Tradisi Lokal" (kolom cari + dropdown provinsi).
 *
 * @param array $filter Filter aktif (untuk mengisi ulang form).
 * @param int   $total  Total tradisi yang cocok.
 * @return string HTML.
 */
function tk_koleksi_render_form( $filter, $total ) {
    $url_dasar   = get_permalink();
    $daftar_prov = get_terms( array(
        'taxonomy'   => 'wilayah',
        'hide_empty' => true,
        'parent'     => 0, // Hanya level teratas (provinsi).
    ) );
    $ada_filter  = '' !== $filter['cari'] || '' !== $filter['provinsi'];

    ob_start();
    ?>
    <section class="tk-jelajah" id="jelajahi">
      <div class="tk-jelajah-head">
        <div>
          <span class="tk-label">Pencarian Arsip</span>
          <h2 class="tk-jelajah-judul">Jelajahi Tradisi Lokal</h2>
        </div>
        <span class="tk-jumlah">Menampilkan <?php echo esc_html( number_format_i18n( $total ) ); ?> tradisi</span>
      </div>

      <form class="tk-cari" method="get" action="<?php echo esc_url( $url_dasar ); ?>#jelajahi">
        <input type="search" name="cari" placeholder="Cari nama tradisi..." value="<?php echo esc_attr( $filter['cari'] ); ?>">

        <select name="provinsi">
          <option value="">Pilih Provinsi</option>
          <?php if ( ! is_wp_error( $daftar_prov ) ) : ?>
            <?php foreach ( $daftar_prov as $term ) : ?>
              <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $filter['provinsi'], $term->slug ); ?>>
                <?php echo esc_html( $term->name ); ?>
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>

        <button type="submit">Cari</button>

        <?php if ( $ada_filter ) : ?>
          <a class="tk-reset" href="<?php echo esc_url( $url_dasar ); ?>#jelajahi">× Hapus pencarian</a>
        <?php endif; ?>
      </form>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * HTML satu card tradisi.
 *
 * Isi card:
 *   Gambar + badge kategori (kiri atas) + badge "Terpublikasi" (kanan atas),
 *   judul, lokasi 2 baris (asal_daerah, wilayah), deskripsi singkat,
 *   2 kata kunci pertama, dan link "Lihat Detail".
 *
 * @param int $id ID tradisi.
 * @return string HTML.
 */
function tk_koleksi_render_kartu( $id ) {
    $link     = get_permalink( $id );
    $kategori = tk_term_names( $id, 'kategori-tradisi' );
    $wilayah  = tk_term_names( $id, 'wilayah' );
    $tags     = tk_term_names( $id, 'post_tag', '   # ', 2 );
    $asal     = get_post_meta( $id, 'asal_daerah', true );
    $desk     = get_post_meta( $id, 'deskripsi_singkat', true );

    ob_start();
    ?>
    <article class="tk-kartu">
      <a class="tk-kartu-media" href="<?php echo esc_url( $link ); ?>">
        <?php echo get_the_post_thumbnail( $id, 'medium_large', array( 'loading' => 'lazy' ) ); ?>
        <?php if ( $kategori ) : ?>
          <span class="tk-badge-kat"><?php echo esc_html( $kategori ); ?></span>
        <?php endif; ?>
        <span class="tk-badge-status">Terpublikasi</span>
      </a>

      <div class="tk-kartu-isi">
        <h3 class="tk-kartu-judul"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( get_the_title( $id ) ); ?></a></h3>

        <?php if ( $asal ) : ?>
          <p class="tk-lok"><?php echo tk_icon( 'pin' ); ?><span><?php echo esc_html( $asal ); ?></span></p>
        <?php endif; ?>
        <?php if ( $wilayah ) : ?>
          <p class="tk-lok tk-lok-2"><?php echo tk_icon( 'gedung' ); ?><span><?php echo esc_html( $wilayah ); ?></span></p>
        <?php endif; ?>

        <?php if ( $desk ) : ?>
          <p class="tk-kartu-desk"><?php echo esc_html( wp_trim_words( $desk, 25 ) ); ?></p>
        <?php endif; ?>

        <div class="tk-kartu-kaki">
          <span class="tk-tag"><?php echo $tags ? esc_html( '# ' . $tags ) : ''; ?></span>
          <a class="tk-detail" href="<?php echo esc_url( $link ); ?>">Lihat Detail →</a>
        </div>
      </div>
    </article>
    <?php
    return ob_get_clean();
}

/**
 * HTML nomor halaman. Filter aktif (cari, provinsi) ikut terbawa.
 *
 * @param array $filter Filter aktif.
 * @param int   $total  Jumlah halaman.
 * @return string HTML, atau string kosong kalau hanya 1 halaman.
 */
function tk_koleksi_render_paging( $filter, $total ) {
    if ( $total < 2 ) {
        return '';
    }

    $links = paginate_links( array(
        'base'      => add_query_arg( 'hal', '%#%', get_permalink() ),
        'format'    => '',
        'current'   => $filter['hal'],
        'total'     => $total,
        'add_args'  => array_filter( array(
            'cari'     => $filter['cari'],
            'provinsi' => $filter['provinsi'],
        ) ),
        'prev_text' => '‹ Sebelumnya',
        'next_text' => 'Berikutnya ›',
    ) );

    return '<nav class="tk-halaman" aria-label="Halaman koleksi">' . $links . '</nav>';
}
