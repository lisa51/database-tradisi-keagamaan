<?php
/**
 * Panel Kurator: kotak khusus kurator di halaman tradisi (termasuk pratinjau).
 *
 * Tampil di bagian atas templates/single-tradisi.php, HANYA untuk pengguna
 * dengan hak tk_kurasi (Kurator & Administrator). Pengunjung biasa tidak
 * melihat apa pun.
 *
 * Isi panel:
 *   1. Status publikasi saat ini.
 *   2. Orang-orang: pengirim (akun/tamu + email + instansi), kurator yang pernah
 *      memutuskan, penyunting (dari revisi WordPress), dan penyunting terakhir.
 *   3. Checklist kelengkapan (sama dengan di dashboard).
 *   4. Tautan: Edit di wp-admin, Bandingkan revisi, Dashboard Kurasi.
 *   5. Ubah status: tombol sesuai status saat ini (tk_aksi_diizinkan()),
 *      dengan kotak catatan. Aksi diproses tk_kurasi_handle(), lalu kembali
 *      ke halaman ini.
 *   6. Riwayat kurasi (tk_log_render()) & riwayat suntingan (revisi).
 *
 * Revisi WordPress aktif untuk post type "tradisi" (lihat includes/core/post-types.php),
 * sehingga setiap penyimpanan di editor tercatat beserta penyuntingnya.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Label & gaya tombol untuk setiap aksi kurasi.
 *
 * @return array[] aksi => array( label, class modifier )
 */
function tk_panel_tombol_aksi() {
    return array(
        'terbitkan' => array( 'Terbitkan', 'terbit' ),
        'revisi'    => array( 'Minta Revisi', 'revisi' ),
        'antrean'   => array( 'Kembalikan ke Antrean', 'revisi' ),
        'tolak'     => array( 'Tolak', 'tolak' ),
    );
}

/**
 * Daftar revisi (tanpa autosave), terbaru dulu.
 *
 * @param int $post_id
 * @return WP_Post[]
 */
function tk_panel_get_revisi( $post_id ) {
    $revisi = wp_get_post_revisions( $post_id, array( 'check_enabled' => false ) );
    return array_values( array_filter( $revisi, function ( $r ) {
        return ! wp_is_post_autosave( $r );
    } ) );
}

/**
 * Nama-nama kurator yang pernah memutuskan tradisi ini.
 *
 * @param int $post_id
 * @return string[]
 */
function tk_panel_nama_kurator( $post_id ) {
    $nama = array();
    foreach ( array_unique( array_map( 'intval', (array) get_post_meta( $post_id, '_tk_dikurasi_oleh', false ) ) ) as $uid ) {
        $user = get_userdata( $uid );
        if ( $user ) {
            $nama[] = $user->display_name;
        }
    }
    return $nama;
}

/**
 * Penyunting dari revisi: nama => jumlah suntingan.
 *
 * @param WP_Post[] $revisi
 * @return int[]
 */
function tk_panel_penyunting( $revisi ) {
    $hitung = array();
    foreach ( $revisi as $r ) {
        $nama            = get_the_author_meta( 'display_name', $r->post_author );
        $hitung[ $nama ] = isset( $hitung[ $nama ] ) ? $hitung[ $nama ] + 1 : 1;
    }
    return $hitung;
}

/**
 * HTML Panel Kurator. Kosong untuk selain kurator/admin.
 *
 * @param int $post_id
 * @return string
 */
function tk_panel_kurator( $post_id ) {
    if ( ! current_user_can( 'tk_kurasi' ) ) {
        return '';
    }

    $post   = get_post( $post_id );
    $revisi = tk_panel_get_revisi( $post_id );
    list( $status_label, $status_mod ) = tk_status_kiriman( $post );

    $tamu        = tk_is_kiriman_tamu( $post_id );
    $kurator     = tk_panel_nama_kurator( $post_id );
    $penyunting  = tk_panel_penyunting( $revisi );
    $edit_akhir  = get_post_meta( $post_id, '_edit_last', true );
    $aksi        = tk_aksi_diizinkan( $post->post_status );
    $tombol      = tk_panel_tombol_aksi();

    ob_start();
    ?>
    <aside class="tk-panel-kurator" id="panel-kurator" aria-label="Panel Kurator">
      <div class="tk-panel-head">
        <span class="tk-label">Panel Kurator</span>
        <span class="tk-status tk-status--<?php echo esc_attr( $status_mod ); ?>"><?php echo esc_html( $status_label ); ?></span>
        <?php if ( is_preview() ) : ?><span class="tk-panel-pratinjau">Mode pratinjau: belum terlihat oleh publik</span><?php endif; ?>
      </div>

      <?php echo tk_kurasi_render_pesan(); // phpcs:ignore WordPress.Security.EscapeOutput -- sudah di-escape. ?>

      <?php /* --- Orang-orang --------------------------------------------- */ ?>
      <dl class="tk-panel-orang">
        <div>
          <dt>Pengirim</dt>
          <dd>
            <?php echo esc_html( tk_nama_pengirim( $post_id ) ); ?>
            <?php if ( $tamu ) : ?><span class="tk-tamu">Tamu</span><?php endif; ?>
            <br><a href="mailto:<?php echo esc_attr( tk_email_pengirim( $post_id ) ); ?>"><?php echo esc_html( tk_email_pengirim( $post_id ) ); ?></a>
            <?php $instansi = get_post_meta( $post_id, 'tk_tamu_instansi', true ); ?>
            <?php if ( $instansi ) : ?><br><?php echo esc_html( $instansi ); ?><?php endif; ?>
            <br><small>Dibuat <?php echo esc_html( get_the_date( 'j M Y, H:i', $post ) ); ?></small>
          </dd>
        </div>
        <div>
          <dt>Kurator</dt>
          <dd><?php echo $kurator ? esc_html( implode( ', ', $kurator ) ) : '<em>Belum ada keputusan</em>'; ?></dd>
        </div>
        <div>
          <dt>Penyunting</dt>
          <dd>
            <?php if ( $penyunting ) : ?>
              <?php
              $daftar = array();
              foreach ( $penyunting as $nama => $n ) {
                  $daftar[] = esc_html( $nama ) . ' <small>(' . absint( $n ) . '×)</small>';
              }
              echo implode( ', ', $daftar ); // phpcs:ignore WordPress.Security.EscapeOutput -- sudah di-escape di atas.
              ?>
            <?php else : ?>
              <em>Belum pernah disunting</em>
            <?php endif; ?>
          </dd>
        </div>
        <div>
          <dt>Terakhir diubah</dt>
          <dd>
            <?php echo esc_html( get_the_modified_date( 'j M Y, H:i', $post ) ); ?>
            <?php if ( $edit_akhir ) : ?><br>oleh <?php echo esc_html( get_the_author_meta( 'display_name', $edit_akhir ) ); ?><?php endif; ?>
          </dd>
        </div>
      </dl>

      <?php /* --- Kelengkapan & tautan ------------------------------------ */ ?>
      <?php echo tk_kurasi_render_checklist( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

      <div class="tk-panel-tautan">
        <a class="tk-btn-kecil" href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>">Edit di wp-admin</a>
        <?php if ( count( $revisi ) > 1 ) : ?>
          <a class="tk-btn-kecil" href="<?php echo esc_url( admin_url( 'revision.php?revision=' . $revisi[0]->ID ) ); ?>">Bandingkan revisi</a>
        <?php endif; ?>
        <a class="tk-btn-kecil" href="<?php echo esc_url( tk_url_kurasi() ); ?>">Dashboard Kurasi</a>
      </div>

      <?php /* --- Ubah status -------------------------------------------- */ ?>
      <?php if ( $aksi ) : ?>
        <details class="tk-kurasi-panel"<?php echo 'pending' === $post->post_status ? ' open' : ''; ?>>
          <summary>Ubah status publikasi</summary>
          <?php echo tk_kurasi_form_buka( $post_id, true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
            <label for="tk-panel-catatan">Catatan</label>
            <textarea id="tk-panel-catatan" name="catatan" rows="3"
                      placeholder="Wajib diisi untuk Minta Revisi, Kembalikan ke Antrean, dan Tolak. Catatan revisi/tolak dikirim ke pengirim."></textarea>
            <div class="tk-kurasi-panel-tombol">
              <?php foreach ( $aksi as $a ) : ?>
                <button type="submit" name="aksi" value="<?php echo esc_attr( $a ); ?>"
                        class="tk-btn-kecil tk-btn-kecil--<?php echo esc_attr( $tombol[ $a ][1] ); ?>"
                        <?php if ( 'tolak' === $a ) : ?>onclick="return confirm('Tolak tradisi ini dan pindahkan ke Trash? Pengirim akan menerima alasan Anda.');"<?php endif; ?>>
                  <?php echo esc_html( $tombol[ $a ][0] ); ?>
                </button>
              <?php endforeach; ?>
            </div>
          </form>
        </details>
      <?php endif; ?>

      <?php /* --- Riwayat ------------------------------------------------- */ ?>
      <details class="tk-kurasi-panel">
        <summary>Riwayat kurasi (<?php echo count( tk_log_get( $post_id ) ); ?>)</summary>
        <?php echo tk_log_render( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
      </details>

      <details class="tk-kurasi-panel">
        <summary>Riwayat suntingan (<?php echo count( $revisi ); ?>)</summary>
        <?php if ( ! $revisi ) : ?>
          <p class="tk-log-kosong">Belum ada revisi tersimpan.</p>
        <?php else : ?>
          <ol class="tk-log">
            <?php foreach ( $revisi as $r ) : ?>
              <li>
                <strong><?php echo esc_html( get_the_author_meta( 'display_name', $r->post_author ) ); ?></strong>
                <span class="tk-log-waktu"><?php echo esc_html( mysql2date( 'j M Y, H:i', $r->post_date ) ); ?></span>
                · <a href="<?php echo esc_url( admin_url( 'revision.php?revision=' . $r->ID ) ); ?>">lihat perubahan</a>
              </li>
            <?php endforeach; ?>
          </ol>
        <?php endif; ?>
      </details>
    </aside>
    <?php
    return ob_get_clean();
}
