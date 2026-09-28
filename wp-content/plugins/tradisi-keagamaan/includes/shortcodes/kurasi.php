<?php
/**
 * Shortcode [tk_kurasi]: Dashboard Kurasi di halaman depan.
 *
 * Pemakaian:
 *   Taruh [tk_kurasi] di halaman "Dashboard Kurasi" (slug: dashboard-kurasi).
 *
 * Siapa yang bisa melihat:
 *   Hanya pengguna dengan hak tk_kurasi (Kurator & Administrator).
 *   Menu "Dashboard Kurasi" juga disembunyikan untuk selain mereka
 *   (CSS class tk-menu-kurator, lihat includes/menu.php).
 *
 * Isi setiap kiriman di antrean:
 *   - Judul, pengirim (akun atau tamu + email), tanggal, lama menunggu.
 *   - Checklist kelengkapan (✓/✗) dari tk_kelengkapan().
 *   - Catatan revisi sebelumnya kalau ini kiriman ulang.
 *   - Tombol: Pratinjau · Edit · Terbitkan.
 *   - Panel "Minta Revisi / Tolak" dengan catatan WAJIB diisi:
 *       Minta Revisi → status draf + penanda revisi, pengirim dapat email
 *                      berisi catatan & link untuk memperbaiki.
 *       Tolak        → pindah ke Trash (bisa dipulihkan 30 hari),
 *                      pengirim dapat email berisi alasan.
 *   - Panel "Riwayat" (tk_log_render()).
 *
 * Keamanan:
 *   Semua aksi dikirim lewat POST ke admin-post.php dengan nonce per kiriman,
 *   lalu dicek ulang hak aksesnya di tk_kurasi_handle().
 *
 * Fungsi pendukung ada di includes/kurasi-alur.php.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Kiriman yang menunggu lebih dari sekian hari diberi tanda peringatan. */
define( 'TK_KURASI_HARI_PERINGATAN', 7 );

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

    return ob_get_clean();
}

/**
 * Pesan hasil aksi terakhir (?kurasi=...).
 *
 * @return string
 */
function tk_kurasi_render_pesan() {
    $hasil = isset( $_GET['kurasi'] ) ? sanitize_key( $_GET['kurasi'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- hanya menampilkan pesan.

    $pesan = array(
        'terbitkan'     => array( 'sukses', 'Tradisi diterbitkan dan pengirim sudah diberi tahu.' ),
        'revisi'        => array( 'info', 'Kiriman dikembalikan untuk revisi. Pengirim sudah menerima catatan Anda.' ),
        'tolak'         => array( 'info', 'Kiriman ditolak dan dipindah ke Trash (bisa dipulihkan dalam 30 hari). Pengirim sudah menerima alasannya.' ),
        'catatan_kosong'=> array( 'gagal', 'Catatan wajib diisi untuk Minta Revisi atau Tolak.' ),
        'gagal'         => array( 'gagal', 'Aksi gagal. Muat ulang halaman lalu coba lagi.' ),
    );

    if ( ! isset( $pesan[ $hasil ] ) ) {
        return '';
    }

    return sprintf(
        '<div class="tk-notice tk-notice--%s">%s</div>',
        esc_attr( $pesan[ $hasil ][0] ),
        esc_html( $pesan[ $hasil ][1] )
    );
}

/**
 * Awal form POST aksi kurasi (nonce + field tersembunyi).
 *
 * @param int $post_id
 * @return string
 */
function tk_kurasi_form_buka( $post_id ) {
    return '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">'
        . '<input type="hidden" name="action" value="tk_kurasi">'
        . '<input type="hidden" name="post_id" value="' . absint( $post_id ) . '">'
        . wp_nonce_field( 'tk_kurasi_' . $post_id, '_tk_nonce', true, false );
}

/**
 * Checklist kelengkapan sebagai deretan label ✓ / ✗.
 *
 * @param int $post_id
 * @return string
 */
function tk_kurasi_render_checklist( $post_id ) {
    $cek     = tk_kelengkapan( $post_id );
    $lengkap = count( array_filter( $cek ) );

    $html = sprintf(
        '<div class="tk-cek"><span class="tk-cek-skor">%d/%d lengkap</span>',
        $lengkap,
        count( $cek )
    );
    foreach ( $cek as $label => $ok ) {
        $html .= sprintf(
            '<span class="tk-cek-item tk-cek-item--%s">%s %s</span>',
            $ok ? 'ok' : 'kurang',
            $ok ? '✓' : '✗',
            esc_html( $label )
        );
    }
    return $html . '</div>';
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
    $hari    = (int) floor( ( time() - get_post_time( 'U', true, $p ) ) / DAY_IN_SECONDS );
    $lama    = 0 === $hari ? 'hari ini' : $hari . ' hari';
    $info    = array_filter( array( tk_term_names( $id, 'wilayah' ), tk_term_names( $id, 'kategori-tradisi' ) ) );
    $log     = tk_log_get( $id );
    $ulang   = $log && 'kirim_ulang' === end( $log )['aksi'];
    $catatan = (string) get_post_meta( $id, '_tk_catatan', true );

    ob_start();
    ?>
    <li class="tk-kurasi-item">
      <div class="tk-kurasi-thumb"><?php echo get_the_post_thumbnail( $id, 'thumbnail' ); ?></div>

      <div class="tk-kurasi-isi">
        <h3 class="tk-kurasi-judul">
          <?php echo esc_html( get_the_title( $id ) ); ?>
          <?php if ( $ulang ) : ?><span class="tk-status tk-status--revisi">Kiriman ulang</span><?php endif; ?>
        </h3>
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
        <a class="tk-btn-kecil" href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>">Edit</a>
        <?php echo tk_kurasi_form_buka( $id ); ?>
          <button type="submit" name="aksi" value="terbitkan" class="tk-btn-kecil tk-btn-kecil--terbit">Terbitkan</button>
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
 * Pemroses aksi (admin-post.php, action=tk_kurasi)
 * ========================================================================== */

add_action( 'admin_post_tk_kurasi', 'tk_kurasi_handle' );

/**
 * Jalankan aksi Terbitkan / Minta Revisi / Tolak.
 */
function tk_kurasi_handle() {
    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    $aksi    = isset( $_POST['aksi'] ) ? sanitize_key( $_POST['aksi'] ) : '';
    $catatan = isset( $_POST['catatan'] ) ? sanitize_textarea_field( wp_unslash( $_POST['catatan'] ) ) : '';

    check_admin_referer( 'tk_kurasi_' . $post_id, '_tk_nonce' );

    if ( ! current_user_can( 'tk_kurasi' ) || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_die( 'Anda tidak berhak melakukan kurasi.', 403 );
    }
    if ( 'tradisi' !== get_post_type( $post_id ) || 'pending' !== get_post_status( $post_id ) ) {
        tk_kurasi_redirect( 'gagal' );
    }
    if ( in_array( $aksi, array( 'revisi', 'tolak' ), true ) && '' === trim( $catatan ) ) {
        tk_kurasi_redirect( 'catatan_kosong' );
    }

    $GLOBALS['tk_aksi_dashboard'] = true; // Agar tidak dicatat dua kali oleh hook transisi status.

    switch ( $aksi ) {
        case 'terbitkan':
            wp_publish_post( $post_id );
            delete_post_meta( $post_id, '_tk_catatan' );
            tk_log_tambah( $post_id, 'terbitkan', $catatan );
            tk_email_ke_pengirim( $post_id, 'terbitkan' );
            break;

        case 'revisi':
            wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
            update_post_meta( $post_id, '_tk_perlu_revisi', 1 );
            update_post_meta( $post_id, '_tk_catatan', $catatan );
            tk_log_tambah( $post_id, 'revisi', $catatan );
            tk_email_ke_pengirim( $post_id, 'revisi', $catatan ); // Berisi link revisi (token untuk tamu).
            break;

        case 'tolak':
            update_post_meta( $post_id, '_tk_catatan', $catatan );
            tk_log_tambah( $post_id, 'tolak', $catatan );
            tk_email_ke_pengirim( $post_id, 'tolak', $catatan );
            wp_trash_post( $post_id );
            break;

        default:
            tk_kurasi_redirect( 'gagal' );
    }

    tk_kurasi_redirect( $aksi );
}

/**
 * Kembali ke Dashboard Kurasi dengan kode pesan, lalu berhenti.
 *
 * @param string $hasil
 */
function tk_kurasi_redirect( $hasil ) {
    wp_safe_redirect( add_query_arg( 'kurasi', $hasil, tk_url_kurasi() ) );
    exit;
}
