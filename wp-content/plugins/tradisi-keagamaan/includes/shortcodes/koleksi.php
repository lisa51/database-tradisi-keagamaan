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
 *   ?tipe=budaya-material  Filter berdasarkan slug term "jenis".
 *   ?provinsi=bali      Filter berdasarkan slug term "wilayah" (level teratas).
 *   ?kategori=kematian  Filter berdasarkan slug term "kategori-tradisi".
 *   ?hal=2              Nomor halaman (pagination).
 *
 * Panel pencarian punya id="jelajahi", sehingga menu "/#jelajahi"
 * langsung menggulir ke sini. Grid card punya id="koleksi"; link nomor
 * halaman membawa "#koleksi" agar halaman baru langsung tergulir ke grid.
 *
 * Struktur fungsi:
 *   tk_koleksi_shortcode()     Fungsi utama, merangkai semua bagian.
 *   tk_koleksi_filter_taksonomi() Daftar dropdown filter (provinsi, kategori).
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

    // Ambil sebelum loop: setelah the_post(), get_permalink() menunjuk ke tradisi terakhir.
    $url_dasar = get_permalink();

    ob_start();

    echo tk_koleksi_render_form( $filter, $q->found_posts, $url_dasar );
    ?>
    <section class="tk-koleksi" id="koleksi">
      <div class="tk-koleksi-head">
        <h2>Koleksi Warisan Religi</h2>
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
        <?php echo tk_koleksi_render_paging( $filter, $q->max_num_pages, $url_dasar ); ?>
      <?php else : ?>
        <p class="tk-kosong">Tidak ada koleksi yang cocok dengan pencarian Anda.</p>
      <?php endif; ?>
    </section>
    <?php

    wp_reset_postdata();
    return ob_get_clean();
}

/**
 * Dropdown filter berbasis taxonomy yang tampil di panel Jelajahi.
 *
 * Untuk menambah filter baru (misalnya agama), cukup tambahkan satu baris:
 *   'pilih_agama' => array( 'taxonomy' => 'agama', 'label' => 'Semua Agama', 'hanya_induk' => false ),
 * Kolom form & CSS menyesuaikan otomatis.
 *
 * Kunci array = nama parameter di URL.
 *   PENTING: jangan pakai nama yang sama dengan nama taxonomy (agama, wilayah,
 *   kategori-tradisi, jenis) atau parameter bawaan WordPress (s, p, cat, tag, page,
 *   paged, name, author, year, m). Nama itu dibaca WordPress sendiri dan
 *   membuat Beranda berubah menjadi halaman arsip.
 *   taxonomy     Nama taxonomy.
 *   label        Teks pilihan kosong (tanpa filter).
 *   hanya_induk  true = hanya term level teratas (mis. provinsi, bukan kabupaten).
 *   per_jenis    true = pilihan dikelompokkan per jenis (<optgroup>); bila filter
 *                "tipe" aktif, hanya kategori jenis itu. Khusus kategori-tradisi.
 *
 * @return array[]
 */
function tk_koleksi_filter_taksonomi() {
    return array(
        'tipe'     => array( 'taxonomy' => 'jenis', 'label' => 'Semua Jenis', 'hanya_induk' => false ),
        'provinsi' => array( 'taxonomy' => 'wilayah', 'label' => 'Semua Provinsi', 'hanya_induk' => true ),
        'kategori' => array( 'taxonomy' => 'kategori-tradisi', 'label' => 'Semua Kategori', 'hanya_induk' => false, 'per_jenis' => true ),
    );
}

/**
 * Baca input pencarian dari URL dan bersihkan (sanitasi).
 *
 * @return array Kunci: cari, hal, dan setiap kunci dari tk_koleksi_filter_taksonomi().
 */
function tk_koleksi_get_filter() {
    $get = wp_unslash( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification -- form GET publik, hanya untuk filter.

    $filter = array(
        'cari' => isset( $get['cari'] ) ? sanitize_text_field( $get['cari'] ) : '',
        'hal'  => isset( $get['hal'] ) ? max( 1, absint( $get['hal'] ) ) : 1,
    );
    foreach ( array_keys( tk_koleksi_filter_taksonomi() ) as $kunci ) {
        $filter[ $kunci ] = isset( $get[ $kunci ] ) ? sanitize_title( $get[ $kunci ] ) : '';
    }
    return $filter;
}

/**
 * Nilai filter yang sedang aktif (tanpa nomor halaman), untuk dibawa ke
 * link pagination dan untuk mengecek apakah ada filter.
 *
 * @param array $filter
 * @return string[]
 */
function tk_koleksi_filter_aktif( $filter ) {
    unset( $filter['hal'] );
    return array_filter( $filter, 'strlen' );
}

/**
 * Jalankan query tradisi sesuai filter.
 * Beberapa filter taxonomy digabung dengan AND (harus cocok semua).
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

    $tax_query = array( 'relation' => 'AND' );
    foreach ( tk_koleksi_filter_taksonomi() as $kunci => $conf ) {
        if ( '' !== $filter[ $kunci ] ) {
            $tax_query[] = array(
                'taxonomy' => $conf['taxonomy'],
                'field'    => 'slug',
                'terms'    => $filter[ $kunci ],
            );
        }
    }
    if ( count( $tax_query ) > 1 ) {
        $args['tax_query'] = $tax_query;
    }

    return new WP_Query( $args );
}

/**
 * HTML panel "Jelajahi Tradisi Lokal": kolom cari + dropdown filter + tombol.
 *
 * @param array  $filter    Filter aktif (untuk mengisi ulang form).
 * @param int    $total     Total tradisi yang cocok.
 * @param string $url_dasar URL halaman yang memuat shortcode.
 * @return string HTML.
 */
function tk_koleksi_render_form( $filter, $total, $url_dasar ) {
    $dropdown  = tk_koleksi_filter_taksonomi();
    $ada_filter = (bool) tk_koleksi_filter_aktif( $filter );

    ob_start();
    ?>
    <section class="tk-jelajah" id="jelajahi">
      <div class="tk-jelajah-head">
        <div>
          <span class="tk-label">Pencarian Arsip</span>
          <h2 class="tk-jelajah-judul">Jelajahi Warisan Religi</h2>
        </div>
        <span class="tk-jumlah">Menampilkan <?php echo esc_html( number_format_i18n( $total ) ); ?> koleksi</span>
      </div>

      <form class="tk-cari" method="get" action="<?php echo esc_url( $url_dasar ); ?>#jelajahi"
            style="--tk-jumlah-filter: <?php echo count( $dropdown ); ?>">
        <input type="search" name="cari" placeholder="Cari tradisi atau benda..." aria-label="Cari tradisi atau budaya material" value="<?php echo esc_attr( $filter['cari'] ); ?>">

        <?php foreach ( $dropdown as $kunci => $conf ) :
            $terms = get_terms( array(
                'taxonomy'   => $conf['taxonomy'],
                'hide_empty' => true, // Hanya term yang punya tradisi terbit.
                'parent'     => $conf['hanya_induk'] ? 0 : '',
            ) );
            $terms = is_wp_error( $terms ) ? array() : $terms;

            // Kelompok pilihan: label => term. Tanpa per_jenis = satu kelompok tanpa label.
            $kelompok = array( '' => $terms );
            if ( ! empty( $conf['per_jenis'] ) ) {
                $kelompok = array();
                foreach ( TK_JENIS as $slug_jenis => $label_jenis ) {
                    if ( '' !== $filter['tipe'] && $filter['tipe'] !== $slug_jenis ) {
                        continue;
                    }
                    $ids = tk_kategori_ids_jenis( $slug_jenis );
                    $isi = array_filter( $terms, function ( $t ) use ( $ids ) { return in_array( $t->term_id, $ids, true ); } );
                    if ( $isi ) {
                        $kelompok[ $label_jenis ] = $isi;
                    }
                }
            }
            ?>
          <select name="<?php echo esc_attr( $kunci ); ?>" aria-label="<?php echo esc_attr( $conf['label'] ); ?>">
            <option value=""><?php echo esc_html( $conf['label'] ); ?></option>
            <?php foreach ( $kelompok as $label_kelompok => $isi ) : ?>
              <?php if ( $label_kelompok ) : ?><optgroup label="<?php echo esc_attr( $label_kelompok ); ?>"><?php endif; ?>
              <?php foreach ( $isi as $term ) : ?>
                <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $filter[ $kunci ], $term->slug ); ?>>
                  <?php echo esc_html( $term->name ); ?>
                </option>
              <?php endforeach; ?>
              <?php if ( $label_kelompok ) : ?></optgroup><?php endif; ?>
            <?php endforeach; ?>
          </select>
        <?php endforeach; ?>

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
 *   Gambar + badge kategori (kiri atas) + badge jenis (kanan atas),
 *   judul, lokasi 2 baris (asal_daerah, wilayah), deskripsi singkat,
 *   2 kata kunci pertama, dan link "Lihat Detail".
 *
 * @param int $id ID tradisi.
 * @return string HTML.
 */
function tk_koleksi_render_kartu( $id ) {
    $link     = get_permalink( $id );
    $jenis    = tk_get_jenis( $id );
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
        <span class="tk-badge-jenis tk-pill--<?php echo esc_attr( $jenis ); ?>"><?php echo esc_html( TK_JENIS[ $jenis ] ); ?></span>
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
 * HTML nomor halaman. Semua filter aktif (cari, provinsi, kategori) ikut terbawa.
 *
 * @param array  $filter    Filter aktif.
 * @param int    $total     Jumlah halaman.
 * @param string $url_dasar URL halaman yang memuat shortcode.
 * @return string HTML, atau string kosong kalau hanya 1 halaman.
 */
function tk_koleksi_render_paging( $filter, $total, $url_dasar ) {
    if ( $total < 2 ) {
        return '';
    }

    $links = paginate_links( array(
        'base'         => add_query_arg( 'hal', '%#%', $url_dasar ),
        'format'       => '',
        'current'      => $filter['hal'],
        'total'        => $total,
        'add_args'     => tk_koleksi_filter_aktif( $filter ),
        'add_fragment' => '#koleksi', // Setelah pindah halaman, langsung gulir ke grid.
        'prev_text'    => '‹ Sebelumnya',
        'next_text'    => 'Berikutnya ›',
    ) );

    return '<nav class="tk-halaman" aria-label="Halaman koleksi">' . $links . '</nav>';
}
