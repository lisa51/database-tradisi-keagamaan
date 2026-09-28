<?php
/**
 * Field ACF khusus form depan [tk_form_tradisi].
 *
 *   Identitas Pengirim (TK_TAMU_GROUP)   khusus tamu: nama, email, instansi, pernyataan.
 *   Formulir Kontributor (TK_FORM_GROUP) foto utama, agama, wilayah, kategori, kata kunci.
 *
 * Kedua grup sengaja diberi lokasi post type yang tidak ada ("tk_form_only"),
 * sehingga TIDAK tampil di editor wp-admin (di sana taxonomy & featured image
 * sudah punya panel sendiri). acf_form() tetap menampilkannya lewat 'field_groups'.
 *
 * Juga mengganti label bawaan ACF "Title"/"Content" ke Bahasa Indonesia.
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

/** Label field bawaan ACF "Title" dalam Bahasa Indonesia. */
function tk_form_label_judul( $field ) {
    $field['label']        = 'Nama Tradisi';
    $field['instructions'] = 'Contoh: Ngaben, Tabuik, Pasola.';
    return $field;
}

add_filter( 'acf/prepare_field/name=_post_content', 'tk_form_label_isi' );

/** Label field bawaan ACF "Content" dalam Bahasa Indonesia. */
function tk_form_label_isi( $field ) {
    $field['label']        = 'Isi Artikel';
    $field['instructions'] = 'Uraikan sejarah, makna, dan tata cara tradisi (minimal ' . TK_MIN_KATA_ISI . ' kata). Sebutkan sumber di field Sumber Referensi.';
    return $field;
}
