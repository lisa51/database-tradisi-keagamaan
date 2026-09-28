<?php
/**
 * Shortcode [tk_form_tradisi]: form kirim tradisi dari halaman depan.
 *
 * Pemakaian:
 *   Taruh [tk_form_tradisi] di halaman "Tambah Tradisi" (slug: tambah-tradisi).
 *
 * Alur:
 *   1. Pengunjung belum login  → tombol Masuk / Daftar.
 *   2. Pengguna login (punya hak tk_kirim) → form tampil.
 *   3. Form dikirim → tradisi baru dibuat dengan status "pending"
 *      (Menunggu Kurasi), pengirim = pengguna yang login.
 *   4. Foto utama dijadikan featured image, kata kunci dijadikan Tags,
 *      lalu kurator menerima email pemberitahuan.
 *   5. Di bawah form, pengguna melihat daftar "Kiriman Saya" beserta statusnya.
 *
 * Dua tombol di akhir form:
 *   Simpan Draf           → status "draft". Field wajib boleh kosong (kecuali
 *                           Nama Tradisi). Kurator belum diberi tahu.
 *   Kirim untuk Dikurasi  → status "pending", semua field wajib dicek.
 *
 * Melanjutkan draf:
 *   Di "Kiriman Saya", draf punya link "Lanjutkan" → ?edit=ID. Form terisi
 *   data draf tersebut. Hanya pemilik draf yang bisa membukanya, dan hanya
 *   selama statusnya masih draf.
 *
 * Isi form:
 *   - Nama Tradisi & Isi Artikel (bawaan ACF form).
 *   - Grup "Formulir Kontributor" (didefinisikan di file ini): foto utama,
 *     agama, wilayah, kategori, kata kunci.
 *   - Grup "Detail Tradisi" (includes/acf-fields.php): asal daerah, abstrak,
 *     tanggal, sumber, galeri, koordinat.
 *
 * Keamanan:
 *   - Hanya pengguna dengan hak tk_kirim yang bisa mengirim (dicek dua kali:
 *     saat menampilkan form dan saat menyimpan).
 *   - Status selalu "pending"; tidak ada kiriman yang langsung terbit.
 *   - ACF menambahkan nonce dan honeypot anti-spam secara otomatis.
 *   - Upload memakai uploader "basic", sehingga kontributor tidak bisa
 *     melihat Media Library milik orang lain.
 *
 * Butuh plugin ACF aktif.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Key field group khusus form (dipakai beberapa fungsi di bawah). */
define( 'TK_FORM_GROUP', 'group_tk_form_kontributor' );

/** Key field group detail tradisi (lihat includes/acf-fields.php). */
define( 'TK_DETAIL_GROUP', 'group_6aa0f20d00bc9' );

/* =============================================================================
 * 1. Field group "Formulir Kontributor"
 * ========================================================================== */

add_action( 'acf/include_fields', 'tk_form_register_fields' );

/**
 * Field tambahan yang hanya muncul di form depan.
 *
 * Lokasinya sengaja diarahkan ke post type yang tidak ada ("tk_form_only"),
 * sehingga grup ini TIDAK muncul di editor wp-admin. acf_form() tetap bisa
 * menampilkannya karena grup dipanggil langsung lewat 'field_groups'.
 *
 * Field taxonomy memakai save_terms/load_terms, jadi pilihan pengguna
 * langsung tersimpan sebagai term (agama, wilayah, kategori).
 */
function tk_form_register_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    // Pengaturan bersama untuk field taxonomy.
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
                'key'        => 'field_tk_form_kategori',
                'label'      => 'Kategori Tradisi',
                'name'       => 'tk_form_kategori',
                'taxonomy'   => 'kategori-tradisi',
                'field_type' => 'checkbox',
                'required'   => 1,
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
        'location' => array(
            array(
                array(
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'tk_form_only', // Sengaja tidak ada: grup ini khusus form depan.
                ),
            ),
        ),
        'active'   => true,
    ) );
}

/* =============================================================================
 * 2. Persiapan & pengaman sebelum form diproses
 * ========================================================================== */

add_action( 'template_redirect', 'tk_form_head' );

/**
 * acf_form_head() wajib dipanggil sebelum header halaman dicetak.
 * Hanya dijalankan di halaman yang berisi shortcode [tk_form_tradisi].
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
 * Tolak kiriman dari pengguna tanpa hak tk_kirim, dan cegah form depan
 * dipakai untuk mengubah tradisi milik orang lain.
 *
 * @param int|string $post_id 'new_post' atau ID post.
 * @return int|string
 */
function tk_form_guard( $post_id ) {
    if ( is_admin() ) {
        return $post_id; // Penyimpanan dari wp-admin tidak diubah.
    }
    if ( ! current_user_can( 'tk_kirim' ) ) {
        wp_die( 'Anda perlu masuk sebagai kontributor untuk mengirim tradisi.', 403 );
    }
    if ( 'new_post' !== $post_id && ! tk_form_boleh_edit( $post_id ) ) {
        wp_die( 'Anda tidak berhak mengubah tradisi ini.', 403 );
    }
    return $post_id;
}

/**
 * Apakah pengguna yang login boleh membuka tradisi ini di form depan?
 * Syarat: tradisi berstatus draf dan milik pengguna itu sendiri.
 *
 * @param int $post_id ID tradisi.
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
 * Nilainya dari tombol yang diklik (name="tk_status").
 *
 * @return string
 */
function tk_form_status_diminta() {
    $status = isset( $_POST['tk_status'] ) ? sanitize_key( $_POST['tk_status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- nonce dicek oleh ACF.
    return 'draft' === $status ? 'draft' : 'pending';
}

add_filter( 'acf/validate_value', 'tk_form_validasi_draf', 20, 3 );

/**
 * Saat "Simpan Draf", field wajib boleh kosong kecuali Nama Tradisi.
 * Validasi AJAX ACF sendiri dimatikan oleh assets/js/form.js saat tombol
 * draf diklik; filter ini menangani validasi di server.
 *
 * @param bool|string $valid Hasil validasi sebelumnya.
 * @param mixed       $value Nilai field.
 * @param array       $field Pengaturan field.
 * @return bool|string
 */
function tk_form_validasi_draf( $valid, $value, $field ) {
    if ( 'draft' === tk_form_status_diminta() && '_post_title' !== $field['name'] ) {
        return true;
    }
    return $valid;
}

/* =============================================================================
 * 3. Setelah tersimpan: status, foto utama, kata kunci, notifikasi
 * ========================================================================== */

add_action( 'acf/save_post', 'tk_form_after_save', 20 );

/**
 * @param int|string $post_id ID tradisi yang baru disimpan.
 */
function tk_form_after_save( $post_id ) {
    if ( is_admin() || ! is_numeric( $post_id ) || 'tradisi' !== get_post_type( $post_id ) ) {
        return;
    }

    // Status sesuai tombol: Simpan Draf → draft, Kirim → pending.
    // Tradisi yang sudah terbit tidak pernah diubah statusnya dari sini.
    $status_sekarang = get_post_status( $post_id );
    $status_diminta  = tk_form_status_diminta();
    if ( in_array( $status_sekarang, array( 'draft', 'pending' ), true ) && $status_sekarang !== $status_diminta ) {
        wp_update_post( array( 'ID' => $post_id, 'post_status' => $status_diminta ) );
    }

    // Foto utama → featured image.
    $foto = absint( get_post_meta( $post_id, 'foto_utama', true ) );
    if ( $foto ) {
        set_post_thumbnail( $post_id, $foto );
    }

    // Kata kunci "a, b, c" → Tags.
    $kata_kunci = (string) get_post_meta( $post_id, 'kata_kunci', true );
    $tags       = array_filter( array_map( 'trim', explode( ',', $kata_kunci ) ) );
    if ( $tags ) {
        wp_set_post_terms( $post_id, $tags, 'post_tag', false );
    }

    // Beri tahu kurator (sekali saja per tradisi).
    if ( 'pending' === get_post_status( $post_id ) && ! get_post_meta( $post_id, '_tk_kurator_diberitahu', true ) ) {
        tk_form_notify_kurator( $post_id );
        update_post_meta( $post_id, '_tk_kurator_diberitahu', 1 );
    }
}

/**
 * Kirim email ke semua Kurator (atau email admin kalau belum ada kurator).
 *
 * Di LocalWP, email tidak benar-benar terkirim; lihat di tab "Mailpit".
 *
 * @param int $post_id ID tradisi.
 */
function tk_form_notify_kurator( $post_id ) {
    $emails = get_users( array( 'role__in' => array( 'kurator' ), 'fields' => 'user_email' ) );
    if ( ! $emails ) {
        $emails = array( get_option( 'admin_email' ) );
    }

    $pengirim = get_the_author_meta( 'display_name', get_post_field( 'post_author', $post_id ) );
    $judul    = get_the_title( $post_id );

    wp_mail(
        $emails,
        sprintf( '[%s] Kiriman baru menunggu kurasi: %s', get_bloginfo( 'name' ), $judul ),
        sprintf(
            "Ada tradisi baru yang menunggu kurasi.\n\nJudul    : %s\nPengirim : %s\n\nTinjau di Dashboard Kurasi:\n%s\n",
            $judul,
            $pengirim,
            tk_url_kurasi()
        )
    );
}

/* =============================================================================
 * 4. Label field bawaan ACF form (Title/Content) dalam Bahasa Indonesia
 * ========================================================================== */

add_filter( 'acf/prepare_field/name=_post_title', 'tk_form_label_judul' );
add_filter( 'acf/prepare_field/name=_post_content', 'tk_form_label_isi' );

/** @param array $field */
function tk_form_label_judul( $field ) {
    $field['label']        = 'Nama Tradisi';
    $field['instructions'] = 'Contoh: Ngaben, Tabuik, Pasola.';
    return $field;
}

/** @param array $field */
function tk_form_label_isi( $field ) {
    $field['label']        = 'Isi Artikel';
    $field['instructions'] = 'Uraikan sejarah, makna, dan tata cara tradisi. Sebutkan sumber di field Sumber Referensi.';
    return $field;
}

/* =============================================================================
 * 5. Shortcode
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

    if ( ! is_user_logged_in() ) {
        return tk_form_render_ajakan_masuk();
    }

    if ( ! current_user_can( 'tk_kirim' ) ) {
        return '<p class="tk-kosong">Akun Anda belum memiliki izin untuk mengirim tradisi. Hubungi admin WARISI.</p>';
    }

    // Mode lanjutkan draf (?edit=ID), hanya untuk draf milik sendiri.
    $edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification -- hanya memilih data yang ditampilkan.
    if ( $edit_id && ! tk_form_boleh_edit( $edit_id ) ) {
        $edit_id = 0;
    }

    ob_start();

    echo tk_form_render_pesan();

    if ( $edit_id ) {
        printf(
            '<div class="tk-notice tk-notice--info">Melanjutkan draf <strong>%s</strong>. <a href="%s">Buat kiriman baru</a></div>',
            esc_html( get_the_title( $edit_id ) ),
            esc_url( get_permalink() )
        );
    }

    wp_enqueue_script(
        'tk-form',
        TK_URL . 'assets/js/form.js',
        array( 'acf-input' ),
        filemtime( TK_PATH . 'assets/js/form.js' ),
        true
    );

    echo '<div class="tk-form">';
    acf_form( array(
        'id'                 => 'tk-form-tradisi',
        'post_id'            => $edit_id ? $edit_id : 'new_post',
        'new_post'           => array(
            'post_type'   => 'tradisi',
            'post_status' => 'pending', // Diubah ke draft oleh tk_form_after_save() bila perlu.
        ),
        'field_groups'       => array( TK_FORM_GROUP, TK_DETAIL_GROUP ),
        'post_title'         => true,
        'post_content'       => true,
        'uploader'           => 'basic',
        'honeypot'           => true,
        'return'             => add_query_arg( 'tersimpan', '%post_id%', get_permalink() ),
        'submit_value'       => 'Kirim untuk Dikurasi',
        'html_submit_button' => tk_form_render_tombol(),
        'updated_message'    => false, // Pesan ditangani tk_form_render_pesan().
    ) );
    echo '</div>';

    echo tk_form_render_kiriman_saya();

    return ob_get_clean();
}

/**
 * Dua tombol di akhir form.
 *
 * Kedua tombol memakai name="tk_status", sehingga nilai tombol yang diklik
 * ikut terkirim ('draft' atau 'pending'). assets/js/form.js mematikan
 * validasi ACF saat "Simpan Draf" diklik.
 *
 * Catatan:
 *   - ACF memasukkan teks tombol lewat sprintf(), jadi %s = 'submit_value'.
 *   - ACF 6.2+ menyaring HTML ini (hanya tag & atribut aman), karena itu
 *     tidak memakai onclick atau <input> tersembunyi.
 *
 * @return string HTML.
 */
function tk_form_render_tombol() {
    return '<div class="tk-form-tombol">'
        . '<button type="submit" name="tk_status" value="draft" class="tk-btn tk-btn-garis">Simpan Draf</button>'
        . '<button type="submit" name="tk_status" value="pending" class="tk-btn tk-btn-utama">%s</button>'
        . '</div>';
}

/**
 * Pesan setelah form disimpan (?tersimpan=ID), sesuai status tradisinya.
 *
 * @return string HTML, atau '' kalau tidak ada pesan.
 */
function tk_form_render_pesan() {
    $id   = isset( $_GET['tersimpan'] ) ? absint( $_GET['tersimpan'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification -- hanya menampilkan pesan.
    $post = $id ? get_post( $id ) : null;

    if ( ! $post || (int) $post->post_author !== get_current_user_id() ) {
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
 * Kotak ajakan masuk/daftar untuk pengunjung yang belum login.
 *
 * @return string HTML.
 */
function tk_form_render_ajakan_masuk() {
    ob_start();
    ?>
    <div class="tk-ajakan">
      <span class="tk-label">Kontribusi</span>
      <h2>Bagikan tradisi dari daerah Anda</h2>
      <p>Masuk atau buat akun gratis untuk mengirim tradisi. Setiap kiriman akan ditinjau kurator sebelum diterbitkan.</p>
      <div class="tk-hero-tombol">
        <a class="tk-btn tk-btn-utama" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">Masuk</a>
        <?php if ( get_option( 'users_can_register' ) ) : ?>
          <a class="tk-btn tk-btn-garis" href="<?php echo esc_url( wp_registration_url() ); ?>">Daftar Akun</a>
        <?php endif; ?>
      </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Label & warna untuk setiap status tradisi.
 *
 * @param string $status Status post.
 * @return string[] array( label, modifier class )
 */
function tk_status_label( $status ) {
    $daftar = array(
        'pending' => array( 'Menunggu Kurasi', 'pending' ),
        'publish' => array( 'Terpublikasi', 'publish' ),
        'draft'   => array( 'Draf', 'draft' ),
        'trash'   => array( 'Ditolak', 'trash' ),
    );
    return isset( $daftar[ $status ] ) ? $daftar[ $status ] : array( $status, 'draft' );
}

/**
 * Daftar tradisi yang pernah dikirim pengguna yang sedang login.
 *
 * @return string HTML, atau '' kalau belum ada kiriman.
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
            list( $label, $mod ) = tk_status_label( $p->post_status );
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
              <a class="tk-btn-kecil" href="<?php echo esc_url( add_query_arg( 'edit', $p->ID, get_permalink() ) ); ?>">Lanjutkan</a>
            <?php else : ?>
              <span></span>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php
    return ob_get_clean();
}
