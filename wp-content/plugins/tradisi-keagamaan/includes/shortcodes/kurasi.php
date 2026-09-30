<?php
/**
 * Shortcode [tk_kurasi]: Dashboard Kurasi.
 *
 * Pemakaian: taruh di halaman Dashboard Kurasi (slug TK_SLUG_KURASI).
 * Hanya untuk pengguna dengan hak tk_kurasi.
 *
 *   Ringkasan        jumlah menunggu kurasi, menunggu revisi, terpublikasi.
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

    $pending = get_posts( array(
        'post_type'      => 'tradisi',
        'post_status'    => 'pending',
        'posts_per_page' => 50,
        'orderby'        => 'date',
        'order'          => 'ASC', // Kiriman terlama ditinjau lebih dulu.
    ) );

    $jumlah_revisi = count( get_posts( array(
        'post_type'      => 'tradisi',
        'post_status'    => 'draft',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_key'       => '_tk_perlu_revisi',
        'meta_value'     => '1',
    ) ) );
    $jumlah = wp_count_posts( 'tradisi' );

    $ringkasan = array(
        'Menunggu Kurasi'   => $jumlah->pending,
        'Menunggu Revisi'   => $jumlah_revisi,
        'Terpublikasi'      => $jumlah->publish,
    );

    ob_start();

    echo tk_kurasi_render_pesan();
    ?>
    <div class="tk-stats">
      <?php foreach ( $ringkasan as $label => $angka ) : ?>
        <div class="tk-stat">
          <div class="tk-stat-teks">
            <span class="tk-stat-angka"><?php echo esc_html( number_format_i18n( $angka ) ); ?></span>
            <span class="tk-stat-label"><?php echo esc_html( $label ); ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <section class="tk-kurasi">
      <div class="tk-koleksi-head">
        <h2>Antrean Kurasi</h2>
        <span class="tk-jumlah-kecil">Terlama di atas</span>
      </div>

      <?php if ( ! $pending ) : ?>
        <p class="tk-kosong">Tidak ada kiriman yang menunggu. Semua sudah ditinjau.</p>
      <?php else : ?>
        <ul class="tk-kurasi-daftar">
          <?php foreach ( $pending as $p ) { echo tk_kurasi_render_item( $p ); } ?>
        </ul>
      <?php endif; ?>
    </section>
    <?php

    echo tk_kurasi_render_riwayat_saya();

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

    ob_start();
    ?>
    <li class="tk-kurasi-item">
      <div class="tk-kurasi-thumb"><?php echo get_the_post_thumbnail( $id, 'thumbnail' ); ?></div>

      <div class="tk-kurasi-isi">
        <h3 class="tk-kurasi-judul">
          <a href="<?php echo esc_url( tk_url_tinjau( $id ) ); ?>#panel-kurator"><?php echo esc_html( get_the_title( $id ) ); ?></a>
          <?php if ( $asal ) : ?><span class="tk-status tk-status--usulan">Usulan perubahan</span><?php endif; ?>
          <?php if ( $ulang ) : ?><span class="tk-status tk-status--revisi">Kiriman ulang</span><?php endif; ?>
        </h3>
        <?php if ( $asal ) : ?>
          <?php $ubah = tk_usulan_perubahan( $id ); ?>
          <p class="tk-kurasi-usulan">
            Untuk versi terbit: <a href="<?php echo esc_url( get_permalink( $asal ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( get_the_title( $asal ) ); ?></a>
            · Diubah: <?php echo $ubah ? esc_html( implode( ', ', $ubah ) ) : '<em>tidak ada perbedaan</em>'; ?>
          </p>
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
          <button type="submit" name="aksi" value="terbitkan" class="tk-btn-kecil tk-btn-kecil--terbit"
                  <?php if ( $asal ) : ?>onclick="return confirm('Terapkan usulan ini ke versi terbit? Isi halaman yang terbit akan diganti.');"<?php endif; ?>>
            <?php echo $asal ? 'Setujui &amp; Terapkan' : 'Terbitkan'; ?>
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
        <p class="tk-kosong">Belum ada tradisi yang Anda kurasi<?php echo $filter ? ' dengan keputusan ini' : ''; ?>.</p>
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

        <?php if ( $total_hal > 1 ) : ?>
          <nav class="tk-halaman" aria-label="Halaman riwayat">
            <?php echo paginate_links( array(
                'base'      => add_query_arg( 'rhal', '%#%', $filter ? add_query_arg( 'riwayat', $filter, $url_dasar ) : $url_dasar ) . '#riwayat-saya',
                'format'    => '',
                'current'   => $hal,
                'total'     => $total_hal,
                'prev_text' => '‹ Sebelumnya',
                'next_text' => 'Berikutnya ›',
            ) ); ?>
          </nav>
        <?php endif; ?>
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
                '<a class="tk-btn-kecil" href="%s" onclick="return confirm(\'Pulihkan tradisi ini dari Trash? Statusnya akan menjadi Draf.\');">Pulihkan</a>',
                esc_url( $url )
            );

        default: // pending, draft
            return sprintf( '<a class="tk-btn-kecil" href="%s" target="_blank" rel="noopener">Pratinjau</a>', esc_url( get_preview_post_link( $post ) ) );
    }
}
