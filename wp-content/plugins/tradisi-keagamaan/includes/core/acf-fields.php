<?php
/**
 * Field ACF: grup "Detail Tradisi" (tampil di editor post type "tradisi").
 *
 * Field yang tersedia (nama → tipe):
 *   asal_daerah        text         Kabupaten/kota. Dipakai di card & stats.
 *   deskripsi_singkat  textarea     Abstrak. Dipakai di card, hero, single.
 *   tanggal_perayaan   date_picker  Disimpan sebagai Ymd; ditampilkan lewat tk_format_tanggal_acf().
 *   sumber_referensi   url          Link sumber (wajib untuk konten kutipan).
 *   galeri_foto        image        SATU foto (ACF gratis). Ganti ke 'gallery'
 *                                   setelah memakai ACF Pro.
 *   latitude           number       Garis lintang titik peta (Indonesia: ±-11 s.d. 6).
 *   longitude          number       Garis bujur titik peta (Indonesia: ±95 s.d. 141).
 *                                   Keduanya dipakai [tk_peta] dan peta di
 *                                   halaman single. Kosong = tidak tampil di peta.
 *
 * CARA MEMPERBARUI:
 *   1. Ubah field lewat menu ACF → Field Groups.
 *   2. ACF → Tools → Generate PHP, salin hasilnya.
 *   3. Ganti isi acf_add_local_field_group() di bawah.
 *   4. Kembalikan 'key' grup menjadi TK_DETAIL_GROUP.
 *   PENTING: jangan ubah nilai 'key' (group_xxx / field_xxx), karena data
 *   tersimpan bergantung pada key tersebut.
 *
 * Pengaturan yang nilainya default (kosong/0) sengaja dihapus agar ringkas;
 * ACF otomatis mengisi nilai default tersebut.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'acf/include_fields', 'tk_register_acf_fields' );

/**
 * Daftarkan field group "Detail Tradisi".
 */
function tk_register_acf_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return; // ACF belum aktif.
    }

    acf_add_local_field_group( array(
        'key'         => TK_DETAIL_GROUP, // group_6aa0f20d00bc9 (config.php)
        'title'       => 'Detail Tradisi',
        'description' => 'Berisi atribut terkait tradisi',
        'fields'      => array(
            array(
                'key'   => 'field_6aa0f20e51a52',
                'label' => 'Asal Daerah',
                'name'  => 'asal_daerah',
                'type'  => 'text',
            ),
            array(
                'key'   => 'field_6aa0f24e51a54',
                'label' => 'Deskripsi Singkat',
                'name'  => 'deskripsi_singkat',
                'type'  => 'textarea',
            ),
            array(
                'key'            => 'field_6aa0f26151a55',
                'label'          => 'Tanggal Perayaan',
                'name'           => 'tanggal_perayaan',
                'type'           => 'date_picker',
                'display_format' => 'F j, Y',
                'return_format'  => 'd/m/Y',
                'first_day'      => 1,
            ),
            array(
                'key'   => 'field_6aa0f2c151a57',
                'label' => 'Sumber Referensi',
                'name'  => 'sumber_referensi',
                'type'  => 'url',
            ),
            array(
                'key'           => 'field_6aa0f2fa51a58',
                'label'         => 'Galeri Foto',
                'name'          => 'galeri_foto',
                'type'          => 'image',
                'return_format' => 'array',
                'library'       => 'all',
                'preview_size'  => 'medium',
            ),
            array(
                'key'          => 'field_tk_latitude',
                'label'        => 'Latitude',
                'name'         => 'latitude',
                'type'         => 'number',
                'instructions' => 'Klik kanan lokasi di Google Maps, lalu salin koordinatnya. Angka pertama (contoh: -8.4095).',
                'min'          => -90,
                'max'          => 90,
                'step'         => 'any',
                'wrapper'      => array( 'width' => '50' ),
            ),
            array(
                'key'          => 'field_tk_longitude',
                'label'        => 'Longitude',
                'name'         => 'longitude',
                'type'         => 'number',
                'instructions' => 'Angka kedua dari koordinat yang sama (contoh: 115.1889).',
                'min'          => -180,
                'max'          => 180,
                'step'         => 'any',
                'wrapper'      => array( 'width' => '50' ),
            ),
        ),
        'location'    => array(
            array(
                array(
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'tradisi',
                ),
            ),
        ),
        'position'    => 'normal',
        'active'      => true,
    ) );
}
