<?php
/**
 * Shortcode [tk_form_tradisi]: tampilan form kirim tradisi.
 *
 * Pemakaian: taruh di halaman Tambah Tradisi (slug TK_SLUG_TAMBAH) DAN
 * halaman Ubah Tradisi (slug TK_SLUG_UBAH).
 *
 *   Tamu (tanpa login)  identitas + form; hanya tombol "Kirim untuk Dikurasi".
 *   Kontributor (akun)  form + "Simpan Draf"; di bawahnya daftar "Kiriman Saya".
 *   Mengubah            halaman Ubah: ?edit=ID (akun) atau ?edit=ID&token=... (tamu).
 *                       Tambah?edit=ID dialihkan ke halaman Ubah (tk_form_head()).
 *                       Tombol & pesan per keadaan: tk_form_mode_tombol().
 *                       Tanpa ?edit: petunjuk + "Kiriman Saya".
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

    // Halaman Ubah Tradisi tanpa koleksi yang boleh dibuka: tampilkan petunjuk, bukan form kosong.
    if ( ! $edit_id && TK_SLUG_UBAH === get_post_field( 'post_name', get_the_ID() ) ) {
        return tk_form_render_ubah_kosong( $tamu );
    }

    // Tombol & teks sesuai keadaan (lihat tk_form_mode_tombol()).
    $mode_tombol = tk_form_mode_tombol( $edit_id, $tamu );

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
    // Kurator yang menyunting langsung kembali ke halaman koleksinya.
    if ( 'langsung' === $mode_tombol ) {
        $kembali = add_query_arg( 'kurasi', 'ubah', tk_url_tinjau( $edit_id ) ) . '#panel-kurator';
    } elseif ( $tamu ) {
        $kembali = add_query_arg( 'terkirim', 'tamu', get_permalink() );
    } else {
        $kembali = add_query_arg( array_filter( array( 'tersimpan' => '%post_id%', 'diubah' => $edit_id ? 1 : 0 ) ), get_permalink() );
    }
    $tombol = tk_form_teks_tombol( $mode_tombol );

    wp_enqueue_script( 'tk-form', TK_URL . 'assets/js/form.js', array( 'acf-input' ), filemtime( TK_PATH . 'assets/js/form.js' ), true );

    ob_start();

    echo tk_form_render_pesan();

    if ( $edit_id ) {
        echo tk_form_render_info_edit( $edit_id, $tamu, $mode_tombol );
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
        'submit_value'       => $tombol['utama'][1],
        'html_submit_button' => tk_form_render_tombol( $tombol ),
        'html_after_fields'  => ( $tamu && ! $edit_id ) ? tk_turnstile_widget( 'tk-tradisi' ) : '', // Anti-bot, kiriman baru tamu.
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
 * Keadaan form, penentu tombol & pesan:
 *   tamu       kiriman tamu (baru / revisi lewat token)
 *   akun       kiriman baru atau draf/perlu revisi milik sendiri (juga usulan draf)
 *   menunggu   kiriman milik sendiri yang sedang menunggu kurasi
 *   langsung   kurator menyunting koleksi terbit/menunggu/milik orang lain
 *
 * @param int  $edit_id
 * @param bool $tamu
 * @return string
 */
function tk_form_mode_tombol( $edit_id, $tamu ) {
    if ( $tamu ) {
        return 'tamu';
    }
    if ( $edit_id && tk_form_ubah_langsung( $edit_id ) ) {
        return 'langsung';
    }
    if ( $edit_id && 'pending' === get_post_status( $edit_id ) ) {
        return 'menunggu';
    }
    return 'akun';
}

/**
 * Tombol per keadaan: 'draf' (opsional) dan 'utama', masing-masing
 * array( nilai tk_status, teks ).
 *
 * @param string $mode Hasil tk_form_mode_tombol().
 * @return array[]
 */
function tk_form_teks_tombol( $mode ) {
    switch ( $mode ) {
        case 'langsung':
            return array( 'utama' => array( 'tetap', 'Simpan Perubahan' ) );
        case 'menunggu':
            return array( 'utama' => array( 'pending', 'Simpan Perubahan' ) );
        case 'tamu':
            return array( 'utama' => array( 'pending', 'Kirim untuk Dikurasi' ) );
        default:
            return array(
                'draf'  => array( 'draft', 'Simpan Draf' ),
                'utama' => array( 'pending', 'Kirim untuk Dikurasi' ),
            );
    }
}

/**
 * Tombol di akhir form.
 *
 * Tombol memakai name="tk_status" sehingga nilainya ikut terkirim.
 * ACF memasukkan 'submit_value' ke %s lewat sprintf(), dan ACF 6.2+
 * menyaring HTML ini, jadi tidak memakai onclick atau <input> tersembunyi.
 *
 * @param array[] $tombol Hasil tk_form_teks_tombol().
 * @return string
 */
function tk_form_render_tombol( $tombol ) {
    return '<div class="tk-form-tombol">'
        . ( isset( $tombol['draf'] ) ? '<button type="submit" name="tk_status" value="' . esc_attr( $tombol['draf'][0] ) . '" class="tk-btn tk-btn-garis">' . esc_html( $tombol['draf'][1] ) . '</button>' : '' )
        . '<button type="submit" name="tk_status" value="' . esc_attr( $tombol['utama'][0] ) . '" class="tk-btn tk-btn-utama">%s</button>'
        . '</div>';
}

/**
 * Halaman Ubah Tradisi dibuka tanpa koleksi (atau koleksi yang tidak boleh diubah).
 *
 * @param bool $tamu
 * @return string
 */
function tk_form_render_ubah_kosong( $tamu ) {
    if ( $tamu ) {
        return '<p class="tk-kosong">Untuk mengubah kiriman, buka link revisi yang kami kirim ke email Anda, atau <a href="'
            . esc_url( wp_login_url( get_permalink() ) ) . '">masuk</a> bila Anda punya akun.</p>';
    }

    // phpcs:ignore WordPress.Security.NonceVerification -- hanya memilih pesan.
    $html = isset( $_GET['edit'] )
        ? '<div class="tk-notice tk-notice--gagal">Koleksi ini tidak bisa Anda ubah dari sini. Pilih salah satu kiriman Anda di bawah.</div>'
        : '<div class="tk-notice tk-notice--info">Pilih koleksi yang ingin diubah dari daftar <strong>Kiriman Saya</strong> di bawah'
            . ( current_user_can( 'tk_kurasi' ) ? ', atau buka halaman koleksi mana pun lalu klik <strong>Ubah di form</strong> pada Panel Kurator' : '' )
            . '.</div>';

    $daftar = tk_form_render_kiriman_saya();
    return $html . ( $daftar ? $daftar : '<p class="tk-kosong">Anda belum punya kiriman. <a href="' . esc_url( tk_url_tambah() ) . '">Tambah tradisi</a></p>' );
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
            esc_url( tk_url_ubah( $id ) )
        );
    }

    $usulan = tk_usulan_asal( $id );
    if ( $usulan ) {
        return '<div class="tk-notice tk-notice--sukses"><strong>Usulan perubahan terkirim.</strong> Halaman "' . esc_html( get_the_title( $usulan ) )
            . '" di situs tetap menampilkan versi lama sampai kurator menyetujui usulan Anda.</div>';
    }

    // phpcs:ignore WordPress.Security.NonceVerification -- hanya menampilkan pesan.
    if ( ! empty( $_GET['diubah'] ) && 'pending' === $post->post_status ) {
        return '<div class="tk-notice tk-notice--sukses"><strong>Perubahan tersimpan.</strong> Kiriman Anda masih menunggu kurasi.</div>';
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
 * Info di atas form saat mengubah: draf, revisi, kiriman yang menunggu,
 * usulan perubahan, atau suntingan langsung kurator; plus catatan kurator.
 *
 * @param int    $edit_id
 * @param bool   $tamu
 * @param string $mode    Hasil tk_form_mode_tombol().
 * @return string
 */
function tk_form_render_info_edit( $edit_id, $tamu, $mode = 'akun' ) {
    $revisi  = (bool) get_post_meta( $edit_id, '_tk_perlu_revisi', true );
    $catatan = (string) get_post_meta( $edit_id, '_tk_catatan', true );
    $asal    = tk_usulan_asal( $edit_id );
    $judul   = '<strong>' . esc_html( get_the_title( $edit_id ) ) . '</strong>';

    if ( $asal ) {
        $teks = sprintf(
            'Anda mengusulkan perubahan untuk %s yang sudah terbit (<a href="%s" target="_blank" rel="noopener">lihat versi terbit</a>). Halaman di situs tidak berubah sampai kurator menyetujui usulan ini.',
            $judul,
            esc_url( get_permalink( $asal ) )
        );
    } elseif ( 'langsung' === $mode ) {
        list( $status ) = tk_status_kiriman( get_post( $edit_id ) );
        $teks = sprintf( 'Anda menyunting %s sebagai kurator (status: %s). Perubahan langsung berlaku; status tidak berubah.', $judul, esc_html( $status ) );
    } elseif ( 'menunggu' === $mode ) {
        $teks = sprintf( 'Mengubah %s yang sedang menunggu kurasi. Perubahan langsung terlihat oleh kurator.', $judul );
    } else {
        $teks = ( $revisi ? 'Merevisi kiriman ' : 'Melanjutkan draf ' ) . $judul . '.';
    }

    $html = sprintf(
        '<div class="tk-notice tk-notice--info">%s%s</div>',
        $teks,
        $tamu ? '' : sprintf( ' <a href="%s">Buat kiriman baru</a>', esc_url( tk_url_tambah() ) )
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
            $usulan  = tk_usulan_asal( $p->ID );
            $tombol  = array( 'draft' => 'revisi' === $mod ? 'Revisi' : 'Lanjutkan', 'pending' => 'Ubah', 'publish' => 'Ubah' );
            ?>
          <li>
            <span class="tk-kiriman-judul">
              <?php if ( $usulan ) : ?><small class="tk-kiriman-usulan">Usulan perubahan</small><?php endif; ?>
              <?php if ( 'publish' === $p->post_status ) : ?>
                <a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a>
              <?php else : ?>
                <?php echo esc_html( get_the_title( $p ) ); ?>
              <?php endif; ?>
            </span>
            <span class="tk-kiriman-tgl"><?php echo esc_html( get_the_date( '', $p ) ); ?></span>
            <span class="tk-status tk-status--<?php echo esc_attr( $mod ); ?>"><?php echo esc_html( $label ); ?></span>
            <?php if ( isset( $tombol[ $p->post_status ] ) ) : ?>
              <a class="tk-btn-kecil" href="<?php echo esc_url( tk_url_ubah( $p->ID ) ); ?>"><?php echo esc_html( $tombol[ $p->post_status ] ); ?></a>
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
