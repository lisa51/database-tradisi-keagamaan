<?php
/**
 * Shortcode [tk_form_tradisi]: form kirim tradisi dari halaman depan.
 *
 * Pemakaian:
 *   Taruh [tk_form_tradisi] di halaman "Tambah Tradisi" (slug: tambah-tradisi).
 *
 * DUA JENIS PENGIRIM
 *
 *   Kontributor Tamu (tanpa login)
 *     - Wajib mengisi Nama, Email, dan pernyataan persetujuan.
 *     - Hanya tombol "Kirim untuk Dikurasi" (tidak ada draf).
 *     - Penulis di database = akun sistem "Kontributor Tamu"; nama & email
 *       asli disimpan di meta tk_tamu_nama / tk_tamu_email.
 *     - Kalau kurator meminta revisi, tamu mendapat email berisi link
 *       rahasia (?edit=ID&token=...) untuk memperbaiki tanpa login.
 *     - Dibatasi TK_TAMU_BATAS_PER_JAM kiriman per jam per alamat IP.
 *
 *   Kontributor (punya akun, hak tk_kirim)
 *     - Tombol "Simpan Draf" dan "Kirim untuk Dikurasi".
 *     - Melihat daftar "Kiriman Saya" + status + catatan kurator, dan bisa
 *       melanjutkan draf / merevisi lewat link "Lanjutkan" (?edit=ID).
 *
 * SETELAH DIKIRIM ("Kirim untuk Dikurasi")
 *   Status → pending, riwayat dicatat, kurator menerima email, dan untuk
 *   kiriman baru pengirim menerima email konfirmasi.
 *   Foto utama → featured image, kata kunci → Tags.
 *
 * ISI FORM (grup ACF)
 *   - Identitas Pengirim   (khusus tamu, didefinisikan di file ini)
 *   - Formulir Kontributor (foto utama, agama, wilayah, kategori, kata kunci)
 *   - Detail Tradisi       (includes/acf-fields.php)
 *   Grup yang didefinisikan di file ini tidak tampil di editor wp-admin.
 *
 * KEAMANAN
 *   - Kiriman dicek ulang saat disimpan (tk_form_guard), bukan hanya saat form tampil.
 *   - Status tidak pernah langsung "publish".
 *   - ACF menambahkan nonce & honeypot anti-spam.
 *   - Upload memakai uploader "basic" (tanpa akses Media Library).
 *
 * Fungsi pendukung (riwayat, email, token, status) ada di includes/kurasi-alur.php.
 * Butuh plugin ACF aktif.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Key field group (dipakai beberapa fungsi di bawah). */
define( 'TK_FORM_GROUP', 'group_tk_form_kontributor' );
define( 'TK_TAMU_GROUP', 'group_tk_form_tamu' );
define( 'TK_DETAIL_GROUP', 'group_6aa0f20d00bc9' ); // Lihat includes/acf-fields.php.

/** Batas kiriman tamu per jam per alamat IP (anti-spam). */
define( 'TK_TAMU_BATAS_PER_JAM', 5 );

/* =============================================================================
 * 1. Field group khusus form
 * ========================================================================== */

add_action( 'acf/include_fields', 'tk_form_register_fields' );

/**
 * Grup "Identitas Pengirim" (tamu) dan "Formulir Kontributor".
 *
 * Lokasinya sengaja diarahkan ke post type yang tidak ada ("tk_form_only"),
 * sehingga tidak muncul di editor wp-admin. acf_form() tetap menampilkannya
 * karena grup dipanggil langsung lewat 'field_groups'.
 */
function tk_form_register_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    $lokasi_form = array(
        array(
            array(
                'param'    => 'post_type',
                'operator' => '==',
                'value'    => 'tk_form_only', // Sengaja tidak ada: khusus form depan.
            ),
        ),
    );

    // --- Identitas Pengirim (hanya untuk tamu) -----------------------------
    acf_add_local_field_group( array(
        'key'      => TK_TAMU_GROUP,
        'title'    => 'Identitas Pengirim',
        'fields'   => array(
            array(
                'key'      => 'field_tk_tamu_nama',
                'label'    => 'Nama Lengkap',
                'name'     => 'tk_tamu_nama',
                'type'     => 'text',
                'required' => 1,
                'wrapper'  => array( 'width' => '50' ),
            ),
            array(
                'key'          => 'field_tk_tamu_email',
                'label'        => 'Email',
                'name'         => 'tk_tamu_email',
                'type'         => 'email',
                'required'     => 1,
                'instructions' => 'Untuk kabar hasil kurasi. Tidak ditampilkan ke publik.',
                'wrapper'      => array( 'width' => '50' ),
            ),
            array(
                'key'          => 'field_tk_tamu_instansi',
                'label'        => 'Instansi / Komunitas',
                'name'         => 'tk_tamu_instansi',
                'type'         => 'text',
                'instructions' => 'Opsional. Contoh: Sanggar Budaya Situraja, Universitas Padjadjaran.',
            ),
            array(
                'key'      => 'field_tk_tamu_setuju',
                'label'    => 'Pernyataan',
                'name'     => 'tk_tamu_setuju',
                'type'     => 'true_false',
                'required' => 1,
                'message'  => 'Saya menyatakan informasi yang saya kirim benar, dan bersedia kiriman ini ditinjau serta diterbitkan oleh kurator WARISI.',
            ),
        ),
        'location' => $lokasi_form,
        'active'   => true,
    ) );

    // --- Formulir Kontributor ---------------------------------------------
    $taxonomy_field = array(
        'type'          => 'taxonomy',
        'add_term'      => 0,
        'save_terms'    => 1,
        'load_terms'    => 1,
        'return_format' => 'id',
    );

    acf_add_local_field_group( array(
        'key'      => TK_FORM_GROUP,
        'title'    => 'Formulir Kontributor',
        'fields'   => array(
            array(
                'key'           => 'field_tk_form_foto',
                'label'         => 'Foto Utama',
                'name'          => 'foto_utama',
                'type'          => 'image',
                'required'      => 1,
                'instructions'  => 'Foto yang tampil di card dan bagian atas artikel. Gunakan foto milik sendiri atau yang boleh dipakai ulang.',
                'return_format' => 'id',
                'library'       => 'uploadedTo',
                'mime_types'    => 'jpg,jpeg,png,webp',
                'max_size'      => 5, // MB
            ),
            $taxonomy_field + array(
                'key'        => 'field_tk_form_agama',
                'label'      => 'Agama',
                'name'       => 'tk_form_agama',
                'taxonomy'   => 'agama',
                'field_type' => 'select',
                'allow_null' => 1,
                'wrapper'    => array( 'width' => '50' ),
            ),
            $taxonomy_field + array(
                'key'        => 'field_tk_form_wilayah',
                'label'      => 'Provinsi / Wilayah',
                'name'       => 'tk_form_wilayah',
                'taxonomy'   => 'wilayah',
                'field_type' => 'select',
                'required'   => 1,
                'wrapper'    => array( 'width' => '50' ),
            ),
            $taxonomy_field + array(
                'key'          => 'field_tk_form_kategori',
                'label'        => 'Kategori Tradisi',
                'name'         => 'tk_form_kategori',
                'taxonomy'     => 'kategori-tradisi',
                'field_type'   => 'checkbox',
                'required'     => 1,
                'instructions' => 'Boleh memilih lebih dari satu.',
            ),
            array(
                'key'          => 'field_tk_form_kata_kunci',
                'label'        => 'Kata Kunci',
                'name'         => 'kata_kunci',
                'type'         => 'text',
                'instructions' => 'Pisahkan dengan koma. Contoh: kremasi, Hindu Bali, upacara kematian',
            ),
        ),
        'location' => $lokasi_form,
        'active'   => true,
    ) );
}

add_filter( 'acf/prepare_field/name=_post_title', 'tk_form_label_judul' );
add_filter( 'acf/prepare_field/name=_post_content', 'tk_form_label_isi' );

/** Label field bawaan ACF "Title" dalam Bahasa Indonesia. */
function tk_form_label_judul( $field ) {
    $field['label']        = 'Nama Tradisi';
    $field['instructions'] = 'Contoh: Ngaben, Tabuik, Pasola.';
    return $field;
}

/** Label field bawaan ACF "Content" dalam Bahasa Indonesia. */
function tk_form_label_isi( $field ) {
    $field['label']        = 'Isi Artikel';
    $field['instructions'] = 'Uraikan sejarah, makna, dan tata cara tradisi (minimal ' . TK_MIN_KATA_ISI . ' kata). Sebutkan sumber di field Sumber Referensi.';
    return $field;
}

/* =============================================================================
 * 2. Konteks: siapa yang mengisi, dan kiriman mana yang sedang diedit
 * ========================================================================== */

/**
 * Tentukan mode form dari pengguna & parameter URL.
 *
 * @return array {
 *     @type string $mode    'akun' | 'tamu' | 'tanpa_izin'
 *     @type int    $edit_id ID kiriman yang dilanjutkan/direvisi, 0 = kiriman baru.
 * }
 */
function tk_form_konteks() {
    // phpcs:disable WordPress.Security.NonceVerification -- hanya memilih data; izin dicek di bawah & di tk_form_guard().
    $edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
    $token   = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
    // phpcs:enable

    if ( is_user_logged_in() ) {
        if ( ! current_user_can( 'tk_kirim' ) ) {
            return array( 'mode' => 'tanpa_izin', 'edit_id' => 0 );
        }
        return array(
            'mode'    => 'akun',
            'edit_id' => ( $edit_id && tk_form_boleh_edit( $edit_id ) ) ? $edit_id : 0,
        );
    }

    return array(
        'mode'    => 'tamu',
        'edit_id' => ( $edit_id && tk_token_cocok( $edit_id, $token ) ) ? $edit_id : 0,
    );
}

/**
 * Pengguna login boleh membuka kiriman di form bila: tradisi, berstatus
 * draf (draf biasa atau perlu revisi), dan miliknya sendiri.
 *
 * @param int $post_id
 * @return bool
 */
function tk_form_boleh_edit( $post_id ) {
    $post = get_post( $post_id );

    return $post
        && 'tradisi' === $post->post_type
        && 'draft' === $post->post_status
        && (int) $post->post_author === get_current_user_id();
}

/**
 * Status yang diminta tombol yang diklik: 'draft' atau 'pending'.
 * Tamu selalu 'pending' (tidak punya draf).
 *
 * @return string
 */
function tk_form_status_diminta() {
    if ( ! is_user_logged_in() ) {
        return 'pending';
    }
    $status = isset( $_POST['tk_status'] ) ? sanitize_key( $_POST['tk_status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- nonce dicek ACF.
    return 'draft' === $status ? 'draft' : 'pending';
}

/* =============================================================================
 * 3. Sebelum & saat disimpan
 * ========================================================================== */

add_action( 'template_redirect', 'tk_form_head' );

/**
 * acf_form_head() wajib dipanggil sebelum header halaman dicetak.
 * Hanya di halaman yang berisi [tk_form_tradisi].
 */
function tk_form_head() {
    if ( ! function_exists( 'acf_form_head' ) || ! is_singular() ) {
        return;
    }
    $post = get_post();
    if ( $post && has_shortcode( $post->post_content, 'tk_form_tradisi' ) ) {
        acf_form_head();
    }
}

add_filter( 'acf/pre_save_post', 'tk_form_guard', 1 );

/**
 * Pengaman terakhir sebelum ACF menyimpan kiriman dari form depan.
 *
 * @param int|string $post_id 'new_post' atau ID kiriman yang diedit.
 * @return int|string
 */
function tk_form_guard( $post_id ) {
    if ( is_admin() ) {
        return $post_id; // Penyimpanan dari wp-admin tidak diubah.
    }

    $baru = ( 'new_post' === $post_id );

    if ( is_user_logged_in() ) {
        if ( ! current_user_can( 'tk_kirim' ) ) {
            wp_die( 'Akun Anda tidak memiliki izin untuk mengirim tradisi.', 403 );
        }
        if ( ! $baru && ! tk_form_boleh_edit( $post_id ) ) {
            wp_die( 'Anda tidak berhak mengubah tradisi ini.', 403 );
        }
    } else {
        $token = isset( $_REQUEST['token'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        if ( ! $baru && ! tk_token_cocok( $post_id, $token ) ) {
            wp_die( 'Link revisi tidak valid atau sudah kedaluwarsa.', 403 );
        }
        if ( $baru ) {
            tk_form_batasi_tamu();
        }
    }

    $GLOBALS['tk_form_baru'] = $baru; // Dipakai tk_form_after_save().
    return $post_id;
}

/**
 * Batasi jumlah kiriman tamu per jam per alamat IP.
 */
function tk_form_batasi_tamu() {
    $ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
    $kunci = 'tk_tamu_' . md5( $ip );
    $hitung = (int) get_transient( $kunci );

    if ( $hitung >= TK_TAMU_BATAS_PER_JAM ) {
        wp_die( 'Terlalu banyak kiriman dari jaringan Anda. Silakan coba lagi dalam satu jam.', 429 );
    }
    set_transient( $kunci, $hitung + 1, HOUR_IN_SECONDS );
}

add_filter( 'acf/validate_value', 'tk_form_validasi_draf', 20, 3 );

/**
 * "Simpan Draf" (khusus akun): field wajib boleh kosong kecuali Nama Tradisi.
 * Validasi ACF di browser dimatikan oleh assets/js/form.js.
 *
 * @param bool|string $valid
 * @param mixed       $value
 * @param array       $field
 * @return bool|string
 */
function tk_form_validasi_draf( $valid, $value, $field ) {
    if ( 'draft' === tk_form_status_diminta() && '_post_title' !== $field['name'] ) {
        return true;
    }
    return $valid;
}

add_action( 'acf/save_post', 'tk_form_after_save', 20 );

/**
 * Setelah ACF menyimpan: atur status, foto, tags, riwayat, dan email.
 *
 * @param int|string $post_id
 */
function tk_form_after_save( $post_id ) {
    if ( is_admin() || ! is_numeric( $post_id ) || 'tradisi' !== get_post_type( $post_id ) ) {
        return;
    }

    $baru    = ! empty( $GLOBALS['tk_form_baru'] );
    $sebelum = get_post_status( $post_id );
    $diminta = tk_form_status_diminta();

    // Status sesuai tombol. Tradisi yang sudah terbit tidak disentuh.
    if ( in_array( $sebelum, array( 'draft', 'pending' ), true ) && $sebelum !== $diminta ) {
        wp_update_post( array( 'ID' => $post_id, 'post_status' => $diminta ) );
    }

    // Foto utama → featured image.
    $foto = absint( get_post_meta( $post_id, 'foto_utama', true ) );
    if ( $foto ) {
        set_post_thumbnail( $post_id, $foto );
    }

    // Kata kunci "a, b, c" → Tags.
    $tags = array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $post_id, 'kata_kunci', true ) ) ) );
    if ( $tags ) {
        wp_set_post_terms( $post_id, $tags, 'post_tag', false );
    }

    // Baru dikirim (atau dikirim ulang setelah draf/revisi) → catat & beri tahu.
    if ( 'pending' === $diminta && ( $baru || 'draft' === $sebelum ) ) {
        $ulang = (bool) get_post_meta( $post_id, '_tk_perlu_revisi', true );

        delete_post_meta( $post_id, '_tk_perlu_revisi' );
        delete_post_meta( $post_id, '_tk_token' ); // Link revisi lama tidak berlaku lagi.

        tk_log_tambah( $post_id, $ulang ? 'kirim_ulang' : 'kirim', '', tk_nama_pengirim( $post_id ) );
        tk_email_ke_kurator( $post_id, $ulang );

        if ( $baru ) {
            tk_email_ke_pengirim( $post_id, 'diterima' );
        }
    }
}

/* =============================================================================
 * 4. Shortcode
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
 * 5. Bagian-bagian HTML
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
