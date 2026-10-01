<?php
/**
 * Shortcode [tk_kurasi]: Dashboard Kurasi.
 *
 * Pemakaian: taruh di halaman Dashboard Kurasi (slug TK_SLUG_KURASI).
 * Hanya untuk pengguna dengan hak tk_kurasi.
 *
 *   Kartu status     Menunggu Kurasi · Menunggu Revisi · Terpublikasi. Kartu adalah
 *                    filter (?tampil=pending|revisi|publish), angkanya mengikuti
 *                    filter jenis (?tipe=tradisi|budaya-material) & pencarian
 *                    (?kcari=, judul & isi). Halaman: ?khal=2.
 *   Aksi massal      centang koleksi → Terbitkan / Kembalikan ke Antrean / Minta
 *                    Revisi / Tolak (tk_kurasi_massal_handle(), includes/kurasi/aksi.php).
 *   Menunggu Revisi & Terpublikasi  baris ringkas: jenis, kategori, provinsi,
 *                    kelengkapan, status, tombol Lihat/Ubah.
 *   Antrean Kurasi   kiriman "pending", terlama di atas; checklist, lama menunggu,
 *                    tombol Pratinjau/Ubah/Edit di wp-admin/Terbitkan, panel Minta Revisi/Tolak, riwayat.
 *                    Usulan perubahan diberi label, tautan versi terbit, daftar
 *                    bagian yang diubah, dan tombol "Setujui & Terapkan".
 *   Riwayat Kurasi Saya  tradisi yang pernah diputuskan kurator ini, dengan tab
 *                    (?riwayat=terbitkan|revisi|tolak|antrean) dan halaman (?rhal=2).
 *
 * Aksi diproses di includes/kurasi/aksi.php; potongan HTML bersama di
 * includes/kurasi/komponen.php.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================================================
 * Dashboard
 * ========================================================================== */

add_shortcode( 'tk_kurasi', 'tk_kurasi_shortcode' );

/**
 * Render shortcode [tk_kurasi].
 *
 * @return string HTML.
 */
function tk_kurasi_shortcode() {
    if ( ! current_user_can( 'tk_kurasi' ) ) {
        return '<p class="tk-kosong">Halaman ini khusus untuk kurator WARISI.</p>';
    }

    $f      = tk_kurasi_get_filter();
    $status = tk_kurasi_status();
    $conf   = $status[ $f['tampil'] ];

    $q = new WP_Query( tk_kurasi_query_args( $f['tampil'], $f['tipe'], $f['cari'] ) + array(
        'posts_per_page' => 'pending' === $f['tampil'] ? 50 : TK_KURASI_PER_HALAMAN,
        'paged'          => $f['hal'],
        'orderby'        => 'pending' === $f['tampil'] ? 'date' : 'modified',
        'order'          => 'pending' === $f['tampil'] ? 'ASC' : 'DESC', // Antrean: terlama dulu.
    ) );
    update_post_thumbnail_cache( $q ); // Thumbnail semua baris dalam satu query.

    wp_enqueue_script( 'tk-kurasi', TK_URL . 'assets/js/kurasi.js', array(), filemtime( TK_PATH . 'assets/js/kurasi.js' ), true );

    ob_start();

    echo tk_kurasi_render_pesan();
    ?>
    <?php /* Kartu = filter status. Angka mengikuti filter jenis. */ ?>
    <nav class="tk-stats tk-stats--kurasi" aria-label="Filter status">
      <?php foreach ( $status as $kunci => $s ) :
          $jumlah = tk_kurasi_hitung( $kunci, $f['tipe'], $f['cari'] );
          $aktif  = $kunci === $f['tampil'];
          ?>
        <a class="tk-stat tk-stat--filter<?php echo $aktif ? ' is-aktif' : ''; ?>"
           href="<?php echo esc_url( tk_kurasi_url( array( 'tampil' => $kunci, 'tipe' => $f['tipe'], 'kcari' => $f['cari'] ) ) ); ?>"
           <?php echo $aktif ? 'aria-current="true"' : ''; ?>>
          <div class="tk-stat-teks">
            <span class="tk-stat-angka"><?php echo esc_html( number_format_i18n( $jumlah ) ); ?></span>
            <span class="tk-stat-label"><?php echo esc_html( $s['label'] ); ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </nav>

    <section class="tk-kurasi" id="daftar-kurasi">
      <div class="tk-koleksi-head">
        <h2><?php echo esc_html( $conf['judul'] ); ?></h2>
        <span class="tk-jumlah-kecil"><?php echo esc_html( number_format_i18n( $q->found_posts ) ); ?> koleksi<?php echo 'pending' === $f['tampil'] ? ' · terlama di atas' : ''; ?></span>
      </div>

      <div class="tk-kurasi-filter">
        <?php /* Filter jenis */ ?>
        <nav class="tk-tab" aria-label="Filter jenis">
          <?php foreach ( array( '' => 'Semua' ) + TK_JENIS as $slug => $label ) : ?>
            <a class="tk-tab-item<?php echo $slug === $f['tipe'] ? ' is-aktif' : ''; ?>"
               href="<?php echo esc_url( tk_kurasi_url( array( 'tampil' => $f['tampil'], 'tipe' => $slug, 'kcari' => $f['cari'] ) ) ); ?>">
              <?php echo esc_html( $label ); ?> <span><?php echo esc_html( number_format_i18n( tk_kurasi_hitung( $f['tampil'], $slug, $f['cari'] ) ) ); ?></span>
            </a>
          <?php endforeach; ?>
        </nav>

        <?php /* Pencarian: judul & isi, dalam status + jenis yang dipilih */ ?>
        <form class="tk-kurasi-cari" method="get" action="<?php echo esc_url( tk_url_kurasi() ); ?>#daftar-kurasi" role="search">
          <?php if ( 'pending' !== $f['tampil'] ) : ?><input type="hidden" name="tampil" value="<?php echo esc_attr( $f['tampil'] ); ?>"><?php endif; ?>
          <?php if ( $f['tipe'] ) : ?><input type="hidden" name="tipe" value="<?php echo esc_attr( $f['tipe'] ); ?>"><?php endif; ?>
          <input type="search" name="kcari" value="<?php echo esc_attr( $f['cari'] ); ?>" placeholder="Cari judul atau isi…" aria-label="Cari koleksi">
          <button type="submit" class="tk-btn-kecil">Cari</button>
          <?php if ( '' !== $f['cari'] ) : ?>
            <a class="tk-reset" href="<?php echo esc_url( tk_kurasi_url( array( 'tampil' => $f['tampil'], 'tipe' => $f['tipe'] ) ) ); ?>#daftar-kurasi">× Hapus pencarian</a>
          <?php endif; ?>
        </form>
      </div>

      <?php if ( ! $q->have_posts() ) : ?>
        <p class="tk-kosong"><?php echo esc_html( '' !== $f['cari'] ? 'Tidak ada koleksi yang cocok dengan "' . $f['cari'] . '".' : $conf['kosong'] ); ?></p>
      <?php else : ?>
        <?php echo tk_kurasi_render_massal( $f ); // phpcs:ignore WordPress.Security.EscapeOutput -- di-escape di dalam fungsi. ?>

        <?php if ( 'pending' === $f['tampil'] ) : ?>
          <ul class="tk-kurasi-daftar">
            <?php foreach ( $q->posts as $p ) { echo tk_kurasi_render_item( $p ); } ?>
          </ul>
        <?php else : ?>
          <ul class="tk-riwayat-daftar tk-riwayat-daftar--kurasi">
            <?php foreach ( $q->posts as $p ) { echo tk_kurasi_render_baris( $p ); } ?>
          </ul>
        <?php endif; ?>

        <?php echo tk_kurasi_render_halaman( // phpcs:ignore WordPress.Security.EscapeOutput -- keluaran paginate_links().
            add_query_arg( 'khal', '%#%', tk_kurasi_url( array( 'tampil' => $f['tampil'], 'tipe' => $f['tipe'], 'kcari' => $f['cari'] ) ) ) . '#daftar-kurasi',
            $f['hal'],
            $q->max_num_pages,
            'Halaman daftar kurasi'
        ); ?>
      <?php endif; ?>
    </section>
    <?php

    echo tk_kurasi_render_riwayat_saya();

    return ob_get_clean();
}

/**
 * Status yang bisa dipilih lewat kartu.
 *
 * @return array[] kunci => label, judul daftar, pesan kosong
 */
function tk_kurasi_status() {
    return array(
        'pending' => array( 'label' => 'Menunggu Kurasi', 'judul' => 'Antrean Kurasi', 'kosong' => 'Tidak ada kiriman yang menunggu. Semua sudah ditinjau.' ),
        'revisi'  => array( 'label' => 'Menunggu Revisi', 'judul' => 'Menunggu Revisi dari Pengirim', 'kosong' => 'Tidak ada kiriman yang sedang direvisi.' ),
        'publish' => array( 'label' => 'Terpublikasi', 'judul' => 'Koleksi Terpublikasi', 'kosong' => 'Belum ada koleksi terbit.' ),
    );
}

/**
 * Filter dari URL: ?tampil=pending|revisi|publish &tipe=<jenis> &kcari=<kata> &khal=N.
 * (Bukan "status"/"jenis"/"s": nama itu dipakai WordPress/taxonomy.)
 *
 * @return array
 */
function tk_kurasi_get_filter() {
    // phpcs:disable WordPress.Security.NonceVerification -- filter tampilan.
    $tampil = isset( $_GET['tampil'] ) ? sanitize_key( $_GET['tampil'] ) : 'pending';
    $tipe   = isset( $_GET['tipe'] ) ? sanitize_key( $_GET['tipe'] ) : '';
    $cari   = isset( $_GET['kcari'] ) ? sanitize_text_field( wp_unslash( $_GET['kcari'] ) ) : '';
    $hal    = isset( $_GET['khal'] ) ? max( 1, absint( $_GET['khal'] ) ) : 1;
    // phpcs:enable
    return array(
        'tampil' => isset( tk_kurasi_status()[ $tampil ] ) ? $tampil : 'pending',
        'tipe'   => isset( TK_JENIS[ $tipe ] ) ? $tipe : '',
        'cari'   => trim( $cari ),
        'hal'    => $hal,
    );
}

/**
 * URL dashboard dengan filter (nilai kosong dibuang, pesan lama dihapus).
 *
 * @param array $args
 * @return string
 */
function tk_kurasi_url( $args ) {
    if ( isset( $args['tampil'] ) && 'pending' === $args['tampil'] ) {
        unset( $args['tampil'] );
    }
    // add_query_arg() tidak meng-encode nilai; kata pencarian bisa berisi spasi/simbol.
    return add_query_arg( array_map( 'rawurlencode', array_filter( array_map( 'strval', $args ), 'strlen' ) ), tk_url_kurasi() );
}

/**
 * Argumen WP_Query untuk satu status + jenis.
 *
 * @param string $tampil
 * @param string $tipe
 * @param string $cari   Kata kunci (judul & isi), opsional.
 * @return array
 */
function tk_kurasi_query_args( $tampil, $tipe, $cari = '' ) {
    $args = array( 'post_type' => 'tradisi', 'post_status' => 'revisi' === $tampil ? 'draft' : $tampil );
    if ( '' !== $cari ) {
        $args['s'] = $cari;
    }
    if ( 'revisi' === $tampil ) {
        $args['meta_key']   = '_tk_perlu_revisi'; // phpcs:ignore WordPress.DB.SlowDBQuery
        $args['meta_value'] = '1';                // phpcs:ignore WordPress.DB.SlowDBQuery
    }
    if ( $tipe ) {
        $args['tax_query'] = array( array( 'taxonomy' => 'jenis', 'field' => 'slug', 'terms' => $tipe ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
    }
    return $args;
}

/**
 * Jumlah koleksi untuk satu status + jenis.
 *
 * @param string $tampil
 * @param string $tipe
 * @param string $cari
 * @return int
 */
function tk_kurasi_hitung( $tampil, $tipe, $cari = '' ) {
    static $memo = array(); // Kartu & tab jenis meminta kombinasi yang sama.
    $kunci = "$tampil|$tipe|$cari";
    if ( ! isset( $memo[ $kunci ] ) ) {
        $q = new WP_Query( tk_kurasi_query_args( $tampil, $tipe, $cari ) + array(
            'posts_per_page'         => 1,
            'fields'                 => 'ids',
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ) );
        $memo[ $kunci ] = (int) $q->found_posts;
    }
    return $memo[ $kunci ];
}

/**
 * Bilah aksi massal. Checkbox di setiap koleksi terhubung ke form ini lewat
 * atribut form="tk-massal" (form tidak bisa bersarang di form tombol per koleksi).
 * Pilih semua & konfirmasi: assets/js/kurasi.js.
 *
 * @param array $f Filter aktif (untuk kembali ke tampilan yang sama).
 * @return string
 */
function tk_kurasi_render_massal( $f ) {
    $aksi = array(
        'terbitkan' => 'Terbitkan / Setujui & Terapkan',
        'antrean'   => 'Kembalikan ke Antrean',
        'revisi'    => 'Minta Revisi',
        'tolak'     => 'Tolak (pindah ke Trash)',
    );
    $kembali = tk_kurasi_url( array( 'tampil' => $f['tampil'], 'tipe' => $f['tipe'], 'kcari' => $f['cari'], 'khal' => $f['hal'] > 1 ? $f['hal'] : 0 ) );

    ob_start();
    ?>
    <form id="tk-massal" class="tk-massal" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
          data-wajib-catatan="<?php echo esc_attr( implode( ',', tk_aksi_wajib_catatan() ) ); ?>">
      <input type="hidden" name="action" value="tk_kurasi_massal">
      <input type="hidden" name="kembali" value="<?php echo esc_url( $kembali ); ?>">
      <?php wp_nonce_field( 'tk_kurasi_massal', '_tk_nonce' ); ?>

      <label class="tk-massal-semua"><input type="checkbox" data-tk-pilih-semua> Pilih semua</label>
      <span class="tk-massal-hitung" data-tk-hitung>0 dipilih</span>
      <select name="aksi" aria-label="Aksi massal">
        <option value="">Aksi massal…</option>
        <?php foreach ( $aksi as $k => $label ) : ?>
          <option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></option>
        <?php endforeach; ?>
      </select>
      <textarea name="catatan" rows="1" placeholder="Catatan (wajib untuk Kembalikan ke Antrean, Minta Revisi, Tolak; dikirim ke pengirim)"></textarea>
      <button type="submit" class="tk-btn-kecil tk-btn-kecil--terbit">Terapkan</button>
    </form>
    <?php
    return ob_get_clean();
}

/**
 * Checkbox pilih untuk aksi massal.
 *
 * @param int $id
 * @return string
 */
function tk_kurasi_checkbox( $id ) {
    return sprintf(
        '<label class="tk-pilih"><input type="checkbox" form="tk-massal" name="post_ids[]" value="%1$d" aria-label="Pilih %2$s"></label>',
        absint( $id ),
        esc_attr( get_the_title( $id ) )
    );
}

/**
 * Satu baris ringkas (Menunggu Revisi / Terpublikasi).
 *
 * @param WP_Post $p
 * @return string
 */
function tk_kurasi_render_baris( $p ) {
    $id    = $p->ID;
    $cek   = tk_kelengkapan( $id );
    $info  = array_filter( array( TK_JENIS[ tk_get_jenis( $id ) ], tk_term_names( $id, 'kategori-tradisi' ), tk_term_names( $id, 'wilayah' ), tk_nama_pengirim( $id ) ) );
    list( $status_label, $status_mod ) = tk_status_kiriman( $p );

    ob_start();
    ?>
    <li>
      <?php echo tk_kurasi_checkbox( $id ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
      <div class="tk-riwayat-thumb"><?php echo get_the_post_thumbnail( $p, 'thumbnail' ); ?></div>
      <div class="tk-riwayat-isi">
        <strong class="tk-riwayat-judul"><a href="<?php echo esc_url( tk_url_tinjau( $id ) ); ?>#panel-kurator"><?php echo esc_html( get_the_title( $p ) ); ?></a></strong>
        <span class="tk-riwayat-meta"><?php echo esc_html( implode( ' · ', $info ) ); ?> · diubah <?php echo esc_html( get_the_modified_date( 'j M Y', $p ) ); ?></span>
      </div>
      <span class="tk-cek-skor" title="Kelengkapan"><?php echo esc_html( count( array_filter( $cek ) ) . '/' . count( $cek ) ); ?></span>
      <span class="tk-status tk-status--<?php echo esc_attr( $status_mod ); ?>"><?php echo esc_html( $status_label ); ?></span>
      <div class="tk-riwayat-aksi">
        <?php echo tk_kurasi_tombol_lihat( $p ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
        <a class="tk-btn-kecil" href="<?php echo esc_url( tk_url_ubah( $id ) ); ?>">Ubah</a>
      </div>
    </li>
    <?php
    return ob_get_clean();
}

/**
 * HTML satu kiriman di antrean.
 *
 * @param WP_Post $p Tradisi berstatus pending.
 * @return string
 */
function tk_kurasi_render_item( $p ) {
    $id      = $p->ID;
    $tamu    = tk_is_kiriman_tamu( $id );
    // Waktu lokal: draf yang kemudian dikirim tidak punya post_date_gmt (0000-00-00).
    $hari    = (int) floor( ( current_time( 'timestamp' ) - get_post_time( 'U', false, $p ) ) / DAY_IN_SECONDS );
    $lama    = 0 === $hari ? 'hari ini' : $hari . ' hari';
    $info    = array_filter( array( tk_term_names( $id, 'wilayah' ), tk_term_names( $id, 'kategori-tradisi' ) ) );
    $log     = tk_log_get( $id );
    $ulang   = $log && 'kirim_ulang' === end( $log )['aksi'];
    $catatan = (string) get_post_meta( $id, '_tk_catatan', true );
    $asal    = tk_usulan_asal( $id );
    $terbit  = tk_panel_tombol_aksi( $id )['terbitkan'];

    ob_start();
    ?>
    <li class="tk-kurasi-item">
      <?php echo tk_kurasi_checkbox( $id ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
      <div class="tk-kurasi-thumb"><?php echo get_the_post_thumbnail( $id, 'thumbnail' ); ?></div>

      <div class="tk-kurasi-isi">
        <h3 class="tk-kurasi-judul">
          <a href="<?php echo esc_url( tk_url_tinjau( $id ) ); ?>#panel-kurator"><?php echo esc_html( get_the_title( $id ) ); ?></a>
          <?php if ( $asal ) : ?><span class="tk-status tk-status--usulan">Usulan perubahan</span><?php endif; ?>
          <?php if ( $ulang ) : ?><span class="tk-status tk-status--revisi">Kiriman ulang</span><?php endif; ?>
        </h3>
        <?php if ( $asal ) : ?>
          <p class="tk-kurasi-usulan"><?php echo tk_kurasi_ringkasan_usulan( $id ); // phpcs:ignore WordPress.Security.EscapeOutput -- di-escape di dalam fungsi. ?></p>
        <?php endif; ?>
        <p class="tk-kurasi-meta">
          <?php echo esc_html( tk_nama_pengirim( $id ) ); ?>
          <?php if ( $tamu ) : ?>
            <span class="tk-tamu">Tamu</span>
            <a href="mailto:<?php echo esc_attr( tk_email_pengirim( $id ) ); ?>"><?php echo esc_html( tk_email_pengirim( $id ) ); ?></a>
          <?php endif; ?>
          · <span class="tk-umur<?php echo $hari > TK_KURASI_HARI_PERINGATAN ? ' tk-umur--lama' : ''; ?>">menunggu <?php echo esc_html( $lama ); ?></span>
          <?php if ( $info ) : ?> · <?php echo esc_html( implode( ' · ', $info ) ); ?><?php endif; ?>
        </p>
        <?php echo tk_kurasi_render_checklist( $id ); ?>
        <?php if ( $ulang && $catatan ) : ?>
          <p class="tk-kurasi-catatan-lama"><strong>Catatan revisi sebelumnya:</strong> <?php echo esc_html( $catatan ); ?></p>
        <?php endif; ?>
      </div>

      <div class="tk-kurasi-aksi">
        <a class="tk-btn-kecil" href="<?php echo esc_url( get_preview_post_link( $id ) ); ?>" target="_blank" rel="noopener">Pratinjau</a>
        <a class="tk-btn-kecil" href="<?php echo esc_url( tk_url_ubah( $id ) ); ?>">Ubah</a>
        <a class="tk-btn-kecil" href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>">Edit di wp-admin</a>
        <?php echo tk_kurasi_form_buka( $id ); ?>
          <button type="submit" name="aksi" value="terbitkan" class="tk-btn-kecil tk-btn-kecil--terbit"<?php echo tk_panel_konfirmasi( $terbit ); // phpcs:ignore WordPress.Security.EscapeOutput -- esc_js di dalam fungsi. ?>>
            <?php echo esc_html( $terbit[0] ); ?>
          </button>
        </form>
      </div>

      <details class="tk-kurasi-panel">
        <summary>Minta Revisi / Tolak</summary>
        <?php echo tk_kurasi_form_buka( $id ); ?>
          <label for="tk-catatan-<?php echo absint( $id ); ?>">Catatan untuk pengirim <span class="acf-required">*</span></label>
          <textarea id="tk-catatan-<?php echo absint( $id ); ?>" name="catatan" rows="4" required
                    placeholder="Contoh: Mohon tambahkan sumber referensi dan perjelas tata cara upacara pada paragraf kedua."></textarea>
          <div class="tk-kurasi-panel-tombol">
            <button type="submit" name="aksi" value="revisi" class="tk-btn-kecil tk-btn-kecil--revisi">Minta Revisi</button>
            <button type="submit" name="aksi" value="tolak" class="tk-btn-kecil tk-btn-kecil--tolak"
                    onclick="return confirm('Tolak kiriman ini? Pengirim akan menerima alasan Anda.');">Tolak</button>
          </div>
        </form>
      </details>

      <details class="tk-kurasi-panel">
        <summary>Riwayat (<?php echo count( $log ); ?>)</summary>
        <?php echo tk_log_render( $id ); // phpcs:ignore WordPress.Security.EscapeOutput -- sudah di-escape. ?>
      </details>
    </li>
    <?php
    return ob_get_clean();
}

/* =============================================================================
 * Riwayat Kurasi Saya
 * ========================================================================== */

/**
 * Daftar tradisi yang pernah diputuskan kurator yang sedang login.
 *
 * @return string HTML.
 */
function tk_kurasi_render_riwayat_saya() {
    $user_id = get_current_user_id();

    // phpcs:disable WordPress.Security.NonceVerification -- hanya filter tampilan.
    $filter = isset( $_GET['riwayat'] ) ? sanitize_key( $_GET['riwayat'] ) : '';
    $hal    = isset( $_GET['rhal'] ) ? max( 1, absint( $_GET['rhal'] ) ) : 1;
    // phpcs:enable
    if ( ! in_array( $filter, tk_log_aksi_kurator(), true ) ) {
        $filter = '';
    }

    $ids = get_posts( array(
        'post_type'      => 'tradisi',
        'post_status'    => array( 'publish', 'pending', 'draft', 'trash' ),
        'posts_per_page' => 300,
        'fields'         => 'ids',
        'orderby'        => 'modified',
        'order'          => 'DESC',
        'meta_query'     => array(
            array(
                'key'   => '_tk_dikurasi_oleh',
                'value' => $user_id,
                'type'  => 'NUMERIC',
            ),
        ),
    ) );

    // Pasangkan setiap tradisi dengan keputusan terakhir kurator ini, lalu saring.
    $baris = array();
    $hitung = array_fill_keys( tk_log_aksi_kurator(), 0 );
    foreach ( $ids as $id ) {
        $terakhir = tk_log_terakhir_oleh( $id, $user_id );
        if ( ! $terakhir ) {
            continue;
        }
        $hitung[ $terakhir['aksi'] ]++;
        if ( '' === $filter || $filter === $terakhir['aksi'] ) {
            $baris[] = array( 'id' => $id, 'log' => $terakhir );
        }
    }

    // Urutkan dari keputusan terbaru.
    usort( $baris, function ( $a, $b ) {
        return strcmp( $b['log']['waktu'], $a['log']['waktu'] );
    } );

    $total_hal = max( 1, (int) ceil( count( $baris ) / TK_RIWAYAT_PER_HALAMAN ) );
    $hal       = min( $hal, $total_hal );
    $tampil    = array_slice( $baris, ( $hal - 1 ) * TK_RIWAYAT_PER_HALAMAN, TK_RIWAYAT_PER_HALAMAN );

    $label_log = tk_log_label();
    $tab       = array(
        ''          => array( 'Semua', array_sum( $hitung ) ),
        'terbitkan' => array( 'Diterbitkan', $hitung['terbitkan'] ),
        'revisi'    => array( 'Diminta Revisi', $hitung['revisi'] ),
        'tolak'     => array( 'Ditolak', $hitung['tolak'] ),
    );
    $url_dasar = remove_query_arg( array( 'riwayat', 'rhal', 'kurasi' ), tk_url_kurasi() );

    ob_start();
    ?>
    <section class="tk-riwayat-saya" id="riwayat-saya">
      <div class="tk-koleksi-head"><h2>Riwayat Kurasi Saya</h2></div>

      <nav class="tk-tab" aria-label="Saring riwayat">
        <?php foreach ( $tab as $kunci => $data ) : ?>
          <a class="tk-tab-item<?php echo $kunci === $filter ? ' is-aktif' : ''; ?>"
             href="<?php echo esc_url( ( $kunci ? add_query_arg( 'riwayat', $kunci, $url_dasar ) : $url_dasar ) . '#riwayat-saya' ); ?>">
            <?php echo esc_html( $data[0] ); ?> <span><?php echo esc_html( number_format_i18n( $data[1] ) ); ?></span>
          </a>
        <?php endforeach; ?>
      </nav>

      <?php if ( ! $tampil ) : ?>
        <p class="tk-kosong">Belum ada koleksi yang Anda kurasi<?php echo $filter ? ' dengan keputusan ini' : ''; ?>.</p>
      <?php else : ?>
        <ul class="tk-riwayat-daftar">
          <?php foreach ( $tampil as $r ) :
              $post = get_post( $r['id'] );
              list( $status_label, $status_mod ) = tk_status_kiriman( $post );
              ?>
            <li>
              <div class="tk-riwayat-thumb"><?php echo get_the_post_thumbnail( $post, 'thumbnail' ); ?></div>
              <div class="tk-riwayat-isi">
                <strong class="tk-riwayat-judul">
                  <?php if ( 'trash' === $post->post_status ) : ?>
                    <?php echo esc_html( get_the_title( $post ) ); ?>
                  <?php else : ?>
                    <a href="<?php echo esc_url( tk_url_tinjau( $post->ID ) ); ?>#panel-kurator"><?php echo esc_html( get_the_title( $post ) ); ?></a>
                  <?php endif; ?>
                </strong>
                <span class="tk-riwayat-meta">
                  Anda: <?php echo esc_html( isset( $label_log[ $r['log']['aksi'] ] ) ? $label_log[ $r['log']['aksi'] ] : $r['log']['aksi'] ); ?>
                  · <?php echo esc_html( mysql2date( 'j M Y', $r['log']['waktu'] ) ); ?>
                  · <?php echo esc_html( tk_nama_pengirim( $post->ID ) ); ?>
                </span>
              </div>
              <span class="tk-status tk-status--<?php echo esc_attr( $status_mod ); ?>"><?php echo esc_html( $status_label ); ?></span>
              <div class="tk-riwayat-aksi"><?php echo tk_kurasi_tombol_lihat( $post ); ?></div>

              <details class="tk-kurasi-panel">
                <summary>Riwayat lengkap</summary>
                <?php echo tk_log_render( $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput -- sudah di-escape. ?>
              </details>
            </li>
          <?php endforeach; ?>
        </ul>

        <?php echo tk_kurasi_render_halaman( // phpcs:ignore WordPress.Security.EscapeOutput -- keluaran paginate_links().
            add_query_arg( 'rhal', '%#%', $filter ? add_query_arg( 'riwayat', $filter, $url_dasar ) : $url_dasar ) . '#riwayat-saya',
            $hal,
            $total_hal,
            'Halaman riwayat'
        ); ?>
      <?php endif; ?>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Tombol untuk melihat kembali sebuah tradisi, sesuai statusnya sekarang.
 *
 * @param WP_Post $post
 * @return string HTML.
 */
function tk_kurasi_tombol_lihat( $post ) {
    switch ( $post->post_status ) {
        case 'publish':
            return sprintf( '<a class="tk-btn-kecil" href="%s" target="_blank" rel="noopener">Lihat</a>', esc_url( get_permalink( $post ) ) );

        case 'trash':
            // Tradisi di Trash tidak bisa dipratinjau; pulihkan dulu (kembali ke Draf).
            if ( ! current_user_can( 'delete_post', $post->ID ) ) {
                return '';
            }
            $url = wp_nonce_url( admin_url( 'post.php?post=' . $post->ID . '&action=untrash' ), 'untrash-post_' . $post->ID );
            return sprintf(
                '<a class="tk-btn-kecil" href="%s" onclick="return confirm(\'Pulihkan koleksi ini dari Trash? Statusnya akan menjadi Draf.\');">Pulihkan</a>',
                esc_url( $url )
            );

        default: // pending, draft
            return sprintf( '<a class="tk-btn-kecil" href="%s" target="_blank" rel="noopener">Pratinjau</a>', esc_url( get_preview_post_link( $post ) ) );
    }
}
