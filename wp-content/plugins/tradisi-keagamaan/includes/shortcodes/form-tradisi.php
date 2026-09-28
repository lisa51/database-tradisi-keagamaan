<?php
/**
 * Shortcode [tk_form_tradisi]: tampilan form kirim tradisi.
 *
 * Pemakaian: taruh di halaman Tambah Tradisi (slug TK_SLUG_TAMBAH).
 *
 *   Tamu (tanpa login)  identitas + form; hanya tombol "Kirim untuk Dikurasi".
 *   Kontributor (akun)  form + "Simpan Draf"; di bawahnya daftar "Kiriman Saya".
 *   Melanjutkan / revisi  ?edit=ID (akun) atau ?edit=ID&token=... (tamu).
 *
 * File ini hanya berisi TAMPILAN. Field ACF ada di includes/kontribusi/fields.php,
 * logika simpan & izin di includes/kontribusi/proses.php, tombol draf di
 * assets/js/form.js.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================================================
 * Shortcode
 * ========================================================================== */

add_shortcode( 'tk_form_tradisi', 'tk_form_shortcode' );

/**
 * Render shortcode [tk_form_tradisi].
 *
 * @return string HTML.
 */
function tk_form_shortcode() {
    if ( ! function_exists( 'acf_form' ) ) {
        return '<p class="tk-kosong">Form belum tersedia: plugin ACF belum aktif.</p>';
    }

    $k = tk_form_konteks();

    if ( 'tanpa_izin' === $k['mode'] ) {
        return '<p class="tk-kosong">Akun Anda belum memiliki izin untuk mengirim tradisi. Hubungi admin WARISI.</p>';
    }

    $tamu    = ( 'tamu' === $k['mode'] );
    $edit_id = $k['edit_id'];

    // Grup field: identitas hanya untuk kiriman tamu yang baru.
    $grup = array( TK_FORM_GROUP, TK_DETAIL_GROUP );
    if ( $tamu && ! $edit_id ) {
        array_unshift( $grup, TK_TAMU_GROUP );
    }

    // Kiriman baru dari tamu dicatat atas nama akun sistem "Kontributor Tamu".
    $new_post = array(
        'post_type'   => 'tradisi',
        'post_status' => 'pending', // Diubah ke draft oleh tk_form_after_save() bila perlu.
    );
    if ( $tamu ) {
        $new_post['post_author'] = tk_get_user_tamu();
    }

    // Tujuan setelah simpan. Untuk tamu yang merevisi, token tidak dibawa lagi.
    $kembali = $tamu
        ? add_query_arg( 'terkirim', 'tamu', get_permalink() )
        : add_query_arg( 'tersimpan', '%post_id%', get_permalink() );

    wp_enqueue_script( 'tk-form', TK_URL . 'assets/js/form.js', array( 'acf-input' ), filemtime( TK_PATH . 'assets/js/form.js' ), true );

    ob_start();

    echo tk_form_render_pesan();

    if ( $edit_id ) {
        echo tk_form_render_info_edit( $edit_id, $tamu );
    } elseif ( $tamu ) {
        echo tk_form_render_info_tamu();
    }

    echo '<div class="tk-form">';
    acf_form( array(
        'id'                 => 'tk-form-tradisi',
        'post_id'            => $edit_id ? $edit_id : 'new_post',
        'new_post'           => $new_post,
        'field_groups'       => $grup,
        'post_title'         => true,
        'post_content'       => true,
        'uploader'           => 'basic',
        'honeypot'           => true,
        'return'             => $kembali,
        'submit_value'       => 'Kirim untuk Dikurasi',
        'html_submit_button' => tk_form_render_tombol( ! $tamu ),
        'updated_message'    => false, // Pesan ditangani tk_form_render_pesan().
    ) );
    echo '</div>';

    if ( ! $tamu ) {
        echo tk_form_render_kiriman_saya();
    }

    return ob_get_clean();
}

/* =============================================================================
 * Bagian-bagian HTML
 * ========================================================================== */

/**
 * Tombol di akhir form.
 *
 * Tombol memakai name="tk_status" sehingga nilainya ikut terkirim.
 * ACF memasukkan 'submit_value' ke %s lewat sprintf(), dan ACF 6.2+
 * menyaring HTML ini, jadi tidak memakai onclick atau <input> tersembunyi.
 *
 * @param bool $dengan_draf Tampilkan tombol "Simpan Draf" (khusus akun).
 * @return string
 */
function tk_form_render_tombol( $dengan_draf ) {
    return '<div class="tk-form-tombol">'
        . ( $dengan_draf ? '<button type="submit" name="tk_status" value="draft" class="tk-btn tk-btn-garis">Simpan Draf</button>' : '' )
        . '<button type="submit" name="tk_status" value="pending" class="tk-btn tk-btn-utama">%s</button>'
        . '</div>';
}

/**
 * Pesan setelah form disimpan.
 *   ?terkirim=tamu   → terima kasih untuk tamu.
 *   ?tersimpan=ID    → draf tersimpan / terkirim (akun, hanya untuk pemiliknya).
 *
 * @return string
 */
function tk_form_render_pesan() {
    // phpcs:disable WordPress.Security.NonceVerification -- hanya menampilkan pesan.
    if ( isset( $_GET['terkirim'] ) && 'tamu' === $_GET['terkirim'] ) {
        return '<div class="tk-notice tk-notice--sukses"><strong>Terima kasih!</strong> Kiriman Anda sudah kami terima dan akan ditinjau kurator. Kabar selanjutnya kami kirim ke email Anda.</div>';
    }

    $id = isset( $_GET['tersimpan'] ) ? absint( $_GET['tersimpan'] ) : 0;
    // phpcs:enable
    $post = $id ? get_post( $id ) : null;

    if ( ! $post || ! is_user_logged_in() || (int) $post->post_author !== get_current_user_id() ) {
        return '';
    }

    if ( 'draft' === $post->post_status ) {
        return sprintf(
            '<div class="tk-notice tk-notice--info"><strong>Draf tersimpan.</strong> Anda bisa melanjutkannya kapan saja dari "Kiriman Saya". <a href="%s">Lanjutkan sekarang</a></div>',
            esc_url( add_query_arg( 'edit', $id, get_permalink() ) )
        );
    }

    return '<div class="tk-notice tk-notice--sukses"><strong>Terima kasih!</strong> Tradisi Anda sudah terkirim dan sedang menunggu kurasi. Statusnya bisa dipantau di bagian "Kiriman Saya".</div>';
}

/**
 * Penjelasan di atas form untuk tamu.
 *
 * @return string
 */
function tk_form_render_info_tamu() {
    $daftar = get_option( 'users_can_register' )
        ? sprintf( ' atau <a href="%s">daftar akun</a>', esc_url( wp_registration_url() ) )
        : '';

    return sprintf(
        '<div class="tk-notice tk-notice--info">Anda mengirim sebagai <strong>tamu</strong>: cukup isi nama dan email. Ingin menyimpan draf dan memantau status kiriman? <a href="%s">Masuk</a>%s.</div>',
        esc_url( wp_login_url( get_permalink() ) ),
        $daftar
    );
}

/**
 * Info saat melanjutkan draf / merevisi, termasuk catatan kurator.
 *
 * @param int  $edit_id
 * @param bool $tamu
 * @return string
 */
function tk_form_render_info_edit( $edit_id, $tamu ) {
    $revisi  = (bool) get_post_meta( $edit_id, '_tk_perlu_revisi', true );
    $catatan = (string) get_post_meta( $edit_id, '_tk_catatan', true );

    $html = sprintf(
        '<div class="tk-notice tk-notice--info">%s <strong>%s</strong>.%s</div>',
        $revisi ? 'Merevisi kiriman' : 'Melanjutkan draf',
        esc_html( get_the_title( $edit_id ) ),
        $tamu ? '' : sprintf( ' <a href="%s">Buat kiriman baru</a>', esc_url( get_permalink() ) )
    );

    if ( $revisi && $catatan ) {
        $html .= '<div class="tk-catatan-kurator"><span class="tk-label">Catatan kurator</span><p>'
            . nl2br( esc_html( $catatan ) ) . '</p></div>';
    }

    return $html;
}

/**
 * Daftar "Kiriman Saya" (khusus pengguna login).
 *
 * @return string
 */
function tk_form_render_kiriman_saya() {
    $kiriman = get_posts( array(
        'post_type'      => 'tradisi',
        'post_status'    => array( 'pending', 'publish', 'draft', 'trash' ),
        'author'         => get_current_user_id(),
        'posts_per_page' => 20,
    ) );

    if ( ! $kiriman ) {
        return '';
    }

    ob_start();
    ?>
    <section class="tk-kiriman">
      <div class="tk-koleksi-head"><h2>Kiriman Saya</h2></div>
      <ul class="tk-kiriman-daftar">
        <?php foreach ( $kiriman as $p ) :
            list( $label, $mod ) = tk_status_kiriman( $p );
            $catatan = in_array( $mod, array( 'revisi', 'trash' ), true ) ? (string) get_post_meta( $p->ID, '_tk_catatan', true ) : '';
            ?>
          <li>
            <span class="tk-kiriman-judul">
              <?php if ( 'publish' === $p->post_status ) : ?>
                <a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a>
              <?php else : ?>
                <?php echo esc_html( get_the_title( $p ) ); ?>
              <?php endif; ?>
            </span>
            <span class="tk-kiriman-tgl"><?php echo esc_html( get_the_date( '', $p ) ); ?></span>
            <span class="tk-status tk-status--<?php echo esc_attr( $mod ); ?>"><?php echo esc_html( $label ); ?></span>
            <?php if ( 'draft' === $p->post_status ) : ?>
              <a class="tk-btn-kecil" href="<?php echo esc_url( add_query_arg( 'edit', $p->ID, get_permalink() ) ); ?>"><?php echo 'revisi' === $mod ? 'Revisi' : 'Lanjutkan'; ?></a>
            <?php else : ?>
              <span></span>
            <?php endif; ?>

            <?php if ( $catatan ) : ?>
              <div class="tk-kiriman-catatan">
                <strong><?php echo 'trash' === $mod ? 'Alasan kurator:' : 'Catatan kurator:'; ?></strong>
                <?php echo nl2br( esc_html( $catatan ) ); ?>
              </div>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php
    return ob_get_clean();
}
