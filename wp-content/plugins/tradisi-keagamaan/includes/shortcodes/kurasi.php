<?php
/**
 * Shortcode [tk_kurasi]: Dashboard Kurasi di halaman depan.
 *
 * Pemakaian:
 *   Taruh [tk_kurasi] di halaman "Dashboard Kurasi" (slug: dashboard-kurasi).
 *
 * Siapa yang bisa melihat:
 *   Hanya pengguna dengan hak tk_kurasi (peran Kurator & Administrator).
 *   Pengunjung lain melihat pesan "tidak berhak". Menu "Dashboard Kurasi"
 *   juga otomatis disembunyikan untuk mereka (lihat includes/menu.php).
 *
 * Isi dashboard:
 *   - Ringkasan jumlah: menunggu kurasi & terpublikasi.
 *   - Daftar tradisi berstatus "pending", masing-masing dengan tombol:
 *       Pratinjau  → lihat tampilan artikel sebelum terbit (tab baru).
 *       Edit       → buka editor wp-admin untuk memperbaiki isi.
 *       Terbitkan  → status menjadi "publish", pengirim dapat email.
 *       Tolak      → dipindah ke Trash (bisa dipulihkan 30 hari).
 *
 * Keamanan:
 *   Tombol Terbitkan/Tolak dikirim ke admin-post.php dengan nonce per
 *   tradisi per aksi, lalu dicek ulang hak aksesnya di tk_kurasi_handle().
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
    $jumlah = wp_count_posts( 'tradisi' );

    ob_start();

    echo tk_kurasi_render_pesan();
    ?>
    <div class="tk-stats tk-stats--dua">
      <div class="tk-stat">
        <div class="tk-stat-teks">
          <span class="tk-stat-angka"><?php echo esc_html( number_format_i18n( $jumlah->pending ) ); ?></span>
          <span class="tk-stat-label">Menunggu Kurasi</span>
        </div>
      </div>
      <div class="tk-stat">
        <div class="tk-stat-teks">
          <span class="tk-stat-angka"><?php echo esc_html( number_format_i18n( $jumlah->publish ) ); ?></span>
          <span class="tk-stat-label">Terpublikasi</span>
        </div>
      </div>
    </div>

    <section class="tk-kurasi">
      <div class="tk-koleksi-head"><h2>Antrean Kurasi</h2></div>

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
 * Pesan hasil aksi terakhir (?kurasi=terbit / tolak / gagal).
 *
 * @return string HTML.
 */
function tk_kurasi_render_pesan() {
    $hasil = isset( $_GET['kurasi'] ) ? sanitize_key( $_GET['kurasi'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- hanya menampilkan pesan.

    $pesan = array(
        'terbit' => array( 'sukses', 'Tradisi berhasil diterbitkan dan pengirim sudah diberi tahu.' ),
        'tolak'  => array( 'info', 'Tradisi ditolak dan dipindah ke Trash (bisa dipulihkan dalam 30 hari).' ),
        'gagal'  => array( 'gagal', 'Aksi gagal. Muat ulang halaman lalu coba lagi.' ),
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
 * URL aksi kurasi (Terbitkan/Tolak) yang sudah diberi nonce.
 *
 * @param int    $post_id ID tradisi.
 * @param string $aksi    'terbitkan' atau 'tolak'.
 * @return string
 */
function tk_kurasi_url_aksi( $post_id, $aksi ) {
    $url = add_query_arg( array(
        'action'  => 'tk_kurasi',
        'aksi'    => $aksi,
        'post_id' => $post_id,
    ), admin_url( 'admin-post.php' ) );

    return wp_nonce_url( $url, 'tk_kurasi_' . $aksi . '_' . $post_id );
}

/**
 * HTML satu baris antrean kurasi.
 *
 * @param WP_Post $p Tradisi berstatus pending.
 * @return string HTML.
 */
function tk_kurasi_render_item( $p ) {
    $id       = $p->ID;
    $pengirim = get_the_author_meta( 'display_name', $p->post_author );
    $info     = array_filter( array(
        tk_term_names( $id, 'wilayah' ),
        tk_term_names( $id, 'kategori-tradisi' ),
    ) );

    ob_start();
    ?>
    <li class="tk-kurasi-item">
      <div class="tk-kurasi-thumb">
        <?php echo get_the_post_thumbnail( $id, 'thumbnail' ); ?>
      </div>

      <div class="tk-kurasi-isi">
        <h3 class="tk-kurasi-judul"><?php echo esc_html( get_the_title( $id ) ); ?></h3>
        <p class="tk-kurasi-meta">
          Dikirim <?php echo esc_html( $pengirim ); ?> ·
          <?php echo esc_html( get_the_date( '', $p ) ); ?>
          <?php if ( $info ) : ?> · <?php echo esc_html( implode( ' · ', $info ) ); ?><?php endif; ?>
        </p>
      </div>

      <div class="tk-kurasi-aksi">
        <a class="tk-btn-kecil" href="<?php echo esc_url( get_preview_post_link( $id ) ); ?>" target="_blank" rel="noopener">Pratinjau</a>
        <a class="tk-btn-kecil" href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>">Edit</a>
        <a class="tk-btn-kecil tk-btn-kecil--terbit" href="<?php echo esc_url( tk_kurasi_url_aksi( $id, 'terbitkan' ) ); ?>">Terbitkan</a>
        <a class="tk-btn-kecil tk-btn-kecil--tolak"
           href="<?php echo esc_url( tk_kurasi_url_aksi( $id, 'tolak' ) ); ?>"
           onclick="return confirm('Tolak dan pindahkan tradisi ini ke Trash?');">Tolak</a>
      </div>
    </li>
    <?php
    return ob_get_clean();
}

/* =============================================================================
 * Pemroses aksi Terbitkan / Tolak (admin-post.php?action=tk_kurasi)
 * ========================================================================== */

add_action( 'admin_post_tk_kurasi', 'tk_kurasi_handle' );

/**
 * Jalankan aksi kurasi, lalu kembali ke Dashboard Kurasi dengan pesan hasil.
 */
function tk_kurasi_handle() {
    $aksi    = isset( $_GET['aksi'] ) ? sanitize_key( $_GET['aksi'] ) : '';
    $post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

    // Pemeriksaan: nonce, hak akses, dan jenis post.
    check_admin_referer( 'tk_kurasi_' . $aksi . '_' . $post_id );

    if ( ! current_user_can( 'tk_kurasi' ) || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_die( 'Anda tidak berhak melakukan kurasi.', 403 );
    }
    if ( 'tradisi' !== get_post_type( $post_id ) || 'pending' !== get_post_status( $post_id ) ) {
        tk_kurasi_redirect( 'gagal' );
    }

    if ( 'terbitkan' === $aksi ) {
        wp_publish_post( $post_id );
        tk_kurasi_notify_penulis( $post_id );
        tk_kurasi_redirect( 'terbit' );
    }

    if ( 'tolak' === $aksi ) {
        wp_trash_post( $post_id );
        tk_kurasi_redirect( 'tolak' );
    }

    tk_kurasi_redirect( 'gagal' );
}

/**
 * Kembali ke Dashboard Kurasi dengan kode pesan, lalu hentikan eksekusi.
 *
 * @param string $hasil 'terbit', 'tolak', atau 'gagal'.
 */
function tk_kurasi_redirect( $hasil ) {
    wp_safe_redirect( add_query_arg( 'kurasi', $hasil, tk_url_kurasi() ) );
    exit;
}

/**
 * Email ke pengirim bahwa tradisinya sudah terbit.
 *
 * @param int $post_id ID tradisi.
 */
function tk_kurasi_notify_penulis( $post_id ) {
    $penulis = get_userdata( get_post_field( 'post_author', $post_id ) );
    if ( ! $penulis || ! $penulis->user_email ) {
        return;
    }

    wp_mail(
        $penulis->user_email,
        sprintf( '[%s] Tradisi Anda sudah terbit: %s', get_bloginfo( 'name' ), get_the_title( $post_id ) ),
        sprintf(
            "Halo %s,\n\nTerima kasih atas kontribusi Anda. Tradisi \"%s\" sudah ditinjau kurator dan kini terbit di:\n%s\n",
            $penulis->display_name,
            get_the_title( $post_id ),
            get_permalink( $post_id )
        )
    );
}
