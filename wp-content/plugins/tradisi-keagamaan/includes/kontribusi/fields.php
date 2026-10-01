<?php
/**
 * Field ACF khusus form depan [tk_form_tradisi].
 *
 *   Identitas Pengirim (TK_TAMU_GROUP)   khusus tamu: nama, email, instansi, pernyataan.
 *   Formulir Kontributor (TK_FORM_GROUP) foto utama, agama, provinsi, kata kunci.
 *                                        (Kategori ada di grup Detail Koleksi, setelah Jenis.)
 *
 * Kedua grup sengaja diberi lokasi post type yang tidak ada ("tk_form_only"),
 * sehingga TIDAK tampil di editor wp-admin (di sana taxonomy & featured image
 * sudah punya panel sendiri). acf_form() tetap menampilkannya lewat 'field_groups'.
 *
 * Juga mengganti label bawaan ACF "Title"/"Content" ke Bahasa Indonesia, dan
 * mengisi Foto Utama & Kata Kunci dari data tersimpan saat mengubah koleksi.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
                'message'  => 'Saya menyatakan informasi yang saya kirim benar, foto yang saya unggah milik saya atau bebas digunakan, dan saya bersedia kiriman ini ditinjau serta diterbitkan oleh kurator WARISI.',
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
                'instructions'  => 'Foto yang tampil di card dan bagian atas artikel. Gunakan foto milik sendiri atau yang bebas digunakan, lalu tulis pembuat & lisensinya di Kredit Foto.',
                'return_format' => 'id',
                'library'       => 'uploadedTo',
                'mime_types'    => 'jpg,jpeg,png,webp',
                'max_size'      => TK_FOTO_MAKS_MB,
            ),
            // Galeri untuk form depan. Field Gallery (galeri_foto) butuh Media
            // Library yang tidak bisa dipakai tamu/kontributor, jadi di sini
            // tiap foto diunggah lewat input file biasa (Repeater ACF Pro).
            // Setelah simpan, foto dipindah ke galeri_foto oleh
            // tk_form_pindahkan_galeri() (includes/kontribusi/proses.php).
            array(
                'key'          => 'field_tk_form_galeri',
                'label'        => 'Foto Tambahan',
                'name'         => 'galeri_unggah',
                'type'         => 'repeater',
                'instructions' => 'Opsional, untuk Galeri Foto (maks. ' . TK_GALERI_MAKS . ' foto, masing-masing ' . TK_FOTO_MAKS_MB . ' MB). Klik "Tambah Foto" untuk setiap foto.',
                'layout'       => 'table',
                'max'          => TK_GALERI_MAKS,
                'button_label' => 'Tambah Foto',
                'sub_fields'   => array(
                    array(
                        'key'           => 'field_tk_form_galeri_foto',
                        'label'         => 'Foto',
                        'name'          => 'foto',
                        'type'          => 'image',
                        'return_format' => 'id',
                        'library'       => 'uploadedTo',
                        'mime_types'    => 'jpg,jpeg,png,webp',
                        'max_size'      => TK_FOTO_MAKS_MB,
                        'preview_size'  => 'thumbnail',
                    ),
                ),
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
                'label'      => 'Provinsi',
                'name'       => 'tk_form_wilayah',
                'taxonomy'   => 'wilayah',
                'field_type' => 'select',
                'required'   => 1,
                'wrapper'    => array( 'width' => '50' ),
            ),
            // Kategori ada di grup Detail Koleksi, setelah "Jenis" (includes/core/acf-fields.php).
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

add_filter( 'acf/load_value/key=field_tk_form_foto', 'tk_form_isi_foto_utama', 10, 2 );

/**
 * Saat mengubah koleksi yang sudah ada, Foto Utama terisi dari featured image
 * (koleksi dari wp-admin/impor tidak punya meta foto_utama).
 *
 * @param mixed      $value
 * @param int|string $post_id
 * @return mixed
 */
function tk_form_isi_foto_utama( $value, $post_id ) {
    if ( ! $value && is_numeric( $post_id ) && 'tradisi' === get_post_type( $post_id ) ) {
        $foto = get_post_thumbnail_id( $post_id );
        return $foto ? $foto : $value;
    }
    return $value;
}

add_filter( 'acf/load_value/key=field_tk_form_kata_kunci', 'tk_form_isi_kata_kunci', 10, 2 );

/**
 * Kata Kunci terisi dari Tags yang tersimpan ("a, b, c").
 *
 * @param mixed      $value
 * @param int|string $post_id
 * @return mixed
 */
function tk_form_isi_kata_kunci( $value, $post_id ) {
    $tags = ( is_numeric( $post_id ) && 'tradisi' === get_post_type( $post_id ) ) ? tk_term_names( $post_id, 'post_tag' ) : '';
    return '' !== $tags ? html_entity_decode( $tags ) : $value;
}

add_filter( 'acf/prepare_field/key=field_6aa0f2fa51a58', 'tk_form_sembunyikan_galeri' );

/**
 * Field Gallery (galeri_foto) hanya untuk wp-admin. Di form depan
 * digantikan "Foto Tambahan" (galeri_unggah).
 *
 * @param array $field
 * @return array|false
 */
function tk_form_sembunyikan_galeri( $field ) {
    return is_admin() ? $field : false;
}

add_filter( 'acf/prepare_field/key=field_tk_form_galeri', 'tk_form_info_galeri' );

/**
 * Saat melanjutkan draf/revisi: sebutkan jumlah foto yang sudah ada di galeri,
 * dan kurangi batas baris agar total tidak melebihi TK_GALERI_MAKS.
 *
 * @param array $field
 * @return array|false
 */
function tk_form_info_galeri( $field ) {
    $post_id = is_numeric( acf_get_form_data( 'post_id' ) ) ? (int) acf_get_form_data( 'post_id' ) : 0;
    $ada     = $post_id ? count( tk_get_galeri_ids( get_post_meta( $post_id, 'galeri_foto', true ) ) ) : 0;
    if ( ! $ada ) {
        return $field;
    }

    $sisa = TK_GALERI_MAKS - $ada;
    if ( $sisa <= 0 ) {
        return false; // Galeri sudah penuh.
    }
    $field['max']           = $sisa;
    $field['instructions'] .= sprintf( ' Galeri sudah berisi %d foto; foto baru akan ditambahkan (sisa %d).', $ada, $sisa );
    return $field;
}

add_filter( 'acf/prepare_field/name=_post_title', 'tk_form_label_judul' );

/** Label field bawaan ACF "Title" dalam Bahasa Indonesia. */
function tk_form_label_judul( $field ) {
    $field['label']        = 'Nama Tradisi / Budaya Material';
    $field['instructions'] = 'Contoh tradisi: Ngaben, Tabuik, Pasola. Contoh budaya material: Kitab Kuning Mattuttung, Tenun Toraja.';
    return $field;
}

add_filter( 'acf/prepare_field/name=_post_content', 'tk_form_label_isi' );

/** Label field bawaan ACF "Content" dalam Bahasa Indonesia. */
function tk_form_label_isi( $field ) {
    $field['label']        = 'Isi Artikel';
    $field['instructions'] = 'Uraikan sejarah, makna, dan tata cara tradisi, atau asal-usul, wujud, dan makna budaya material (minimal ' . TK_MIN_KATA_ISI . ' kata). Sebutkan sumber di field Sumber Referensi.';
    return $field;
}
