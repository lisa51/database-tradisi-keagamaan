<?php
/**
 * Field ACF: grup "Detail Koleksi" (tampil di editor post type "tradisi").
 *
 * Field yang tersedia (nama → tipe):
 *   jenis_warisan      button_group Tradisi / Budaya Material. Tidak disimpan sebagai meta,
 *                                   tapi sebagai term taxonomy "jenis" (includes/core/jenis.php).
 *                                   Field di bawah bertanda [T] hanya tampil untuk Tradisi,
 *                                   [M] hanya untuk Budaya Material.
 *   kategori_tradisi   taxonomy     [T] Kategori (term "kategori-tradisi" berjenis tradisi).
 *   kategori_material  taxonomy     [M] Kategori (term berjenis budaya-material).
 *                                   Keduanya menyimpan ke taxonomy; lihat includes/core/jenis.php.
 *   asal_daerah        text       Label "Kabupaten/Kota" (provinsi ada di taxonomy
 *                                   "wilayah"). Dipakai di card, peta & stats.
 *                                   Saran & validasi: includes/core/kabupaten-kota.php.
 *   deskripsi_singkat  textarea     Abstrak. Dipakai di card, hero, single.
 *   sistem_penanggalan select       [T] Kunci dari tk_sistem_penanggalan() (masehi, hijriah, ...).
 *   waktu_pelaksanaan  text         [T] Aturan waktu tetap, mis. "12 Rabiul Awal".
 *                                   Keduanya ditampilkan lewat tk_format_waktu_pelaksanaan().
 *   tanggal_perayaan   date_picker  [T] Label "Tanggal Terdekat" (opsional). Disimpan
 *                                   sebagai Ymd; ditampilkan lewat tk_format_tanggal_acf().
 *   bahan              text         [M] Bahan pembuat.
 *   lokasi_keberadaan  text         [M] Tempat penyimpanan / alamat bangunan atau situs.
 *   fungsi             textarea     [M] Fungsi/kegunaan.
 *   terkait            relationship Tradisi/budaya material lain (dua arah, ACF bidirectional).
 *                                   Ditampilkan lewat tk_single_get_tautan().
 *   sumber_referensi   url          Link sumber (wajib untuk konten kutipan).
 *   galeri_foto        gallery      Banyak foto (ACF Pro), maks. TK_GALERI_MAKS.
 *                                   Di form depan diganti field "Foto Tambahan"
 *                                   (includes/kontribusi/fields.php).
 *   kredit_foto        textarea     Pembuat & lisensi foto, satu baris per foto.
 *                                   Tampil di bagian bawah halaman single.
 *   latitude          number       Garis lintang titik peta (Indonesia: ±-11 s.d. 6).
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

/**
 * Pilihan field Sistem Penanggalan (kunci => label).
 * Kunci tersimpan di database, jadi jangan diubah; label boleh diubah.
 *
 * @return string[]
 */
function tk_sistem_penanggalan() {
    return array(
        'masehi'    => 'Masehi',
        'hijriah'   => 'Hijriah',
        'saka'      => 'Saka (Bali)',
        'jawa'      => 'Jawa',
        'imlek'     => 'Imlek',
        'adat'      => 'Kalender adat/musim',
        'peristiwa' => 'Mengikuti peristiwa',
    );
}

add_action( 'acf/include_fields', 'tk_register_acf_fields' );

/**
 * Daftarkan field group "Detail Koleksi".
 */
function tk_register_acf_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return; // ACF belum aktif.
    }

    // Tampil hanya untuk jenis tertentu (field "Jenis" di atas).
    $untuk = function ( $jenis ) {
        return array( array( array( 'field' => 'field_tk_jenis', 'operator' => '==', 'value' => $jenis ) ) );
    };

    $kategori = array(
        'label'         => 'Kategori',
        'type'          => 'taxonomy',
        'taxonomy'      => 'kategori-tradisi',
        'field_type'    => 'checkbox',
        'instructions'  => 'Boleh memilih lebih dari satu.',
        'required'      => 1,
        'add_term'      => 0,
        'save_terms'    => 1,
        'load_terms'    => 1,
        'return_format' => 'id',
    );

    acf_add_local_field_group( array(
        'key'         => TK_DETAIL_GROUP, // group_6aa0f20d00bc9 (config.php)
        'title'       => 'Detail Koleksi',
        'description' => 'Berisi atribut terkait koleksi',
        'fields'      => array(
            array(
                'key'           => 'field_tk_jenis',
                'label'         => 'Jenis',
                'name'          => 'jenis_warisan', // Disimpan ke taxonomy "jenis" (includes/core/jenis.php).
                'type'          => 'button_group',
                'instructions'  => 'Tradisi = sesuatu yang dilakukan (ritual, upacara, tarian). Budaya Material = sesuatu yang berwujud (benda, bangunan/situs, naskah, makanan).',
                'choices'       => TK_JENIS,
                'default_value' => 'tradisi',
                'required'      => 1,
                'return_format' => 'value',
            ),
            // Kategori: satu field per jenis, pilihan disaring di includes/core/jenis.php.
            $kategori + array(
                'key'               => 'field_tk_kategori_tradisi',
                'name'              => 'kategori_tradisi',
                'conditional_logic' => $untuk( 'tradisi' ),
            ),
            $kategori + array(
                'key'               => 'field_tk_kategori_material',
                'name'              => 'kategori_material',
                'conditional_logic' => $untuk( 'budaya-material' ),
            ),
            array(
                'key'          => 'field_6aa0f20e51a52',
                'label'        => 'Kabupaten/Kota',
                'name'         => 'asal_daerah',
                'type'         => 'text',
                'instructions' => 'Ketik sebagian nama, lalu pilih dari saran. Saran mengikuti Provinsi yang dipilih.',
                'placeholder'  => 'Contoh: Kabupaten Tana Toraja', // Diganti kabkota.js sesuai provinsi.
            ),
            array(
                'key'   => 'field_6aa0f24e51a54',
                'label' => 'Deskripsi Singkat',
                'name'  => 'deskripsi_singkat',
                'type'  => 'textarea',
            ),
            array(
                'key'           => 'field_tk_sistem_penanggalan',
                'label'         => 'Sistem Penanggalan',
                'name'          => 'sistem_penanggalan',
                'type'          => 'select',
                'instructions'  => 'Kalender yang menentukan waktu pelaksanaan.',
                'choices'       => tk_sistem_penanggalan(),
                'allow_null'    => 1,
                'placeholder'   => 'Pilih', // ACF menampilkannya sebagai "- Pilih -".
                'return_format' => 'value',
                'wrapper'       => array( 'width' => '50' ),
                'conditional_logic' => $untuk( 'tradisi' ),
            ),
            array(
                'key'          => 'field_tk_waktu_pelaksanaan',
                'label'        => 'Waktu Pelaksanaan',
                'name'         => 'waktu_pelaksanaan',
                'type'         => 'text',
                'instructions' => 'Aturan waktu yang tetap setiap kali dilaksanakan.',
                'placeholder'  => 'Contoh: 12 Rabiul Awal',
                'maxlength'    => 120,
                'wrapper'      => array( 'width' => '50' ),
                'conditional_logic' => $untuk( 'tradisi' ),
            ),
            array(
                'key'            => 'field_6aa0f26151a55',
                'label'          => 'Tanggal Terdekat',
                'name'           => 'tanggal_perayaan',
                'type'           => 'date_picker',
                'instructions'   => 'Opsional. Tanggal Masehi pelaksanaan berikutnya (atau yang terakhir), untuk tradisi yang tanggalnya berubah setiap tahun. Klik kotak untuk memilih dari kalender.',
                'display_format' => 'j F Y', // Sama dengan tk_format_tanggal_acf(); bulan mengikuti bahasa situs.
                'return_format'  => 'd/m/Y',
                'first_day'      => 1,
                'conditional_logic' => $untuk( 'tradisi' ),
            ),
            array(
                'key'          => 'field_tk_bahan',
                'label'        => 'Bahan',
                'name'         => 'bahan',
                'type'         => 'text',
                'placeholder'  => 'Contoh: kayu ulin, kain tenun, daun lontar',
                'wrapper'      => array( 'width' => '50' ),
                'conditional_logic' => $untuk( 'budaya-material' ),
            ),
            array(
                'key'          => 'field_tk_lokasi_keberadaan',
                'label'        => 'Lokasi Penyimpanan/Keberadaan',
                'name'         => 'lokasi_keberadaan',
                'type'         => 'text',
                'instructions' => 'Tempat benda disimpan atau alamat bangunan/situs.',
                'placeholder'  => 'Contoh: Museum La Galigo, Makassar',
                'wrapper'      => array( 'width' => '50' ),
                'conditional_logic' => $untuk( 'budaya-material' ),
            ),
            array(
                'key'          => 'field_tk_fungsi',
                'label'        => 'Fungsi/Kegunaan',
                'name'         => 'fungsi',
                'type'         => 'textarea',
                'rows'         => 3,
                'instructions' => 'Untuk apa benda ini digunakan, terutama dalam kehidupan keagamaan.',
                'conditional_logic' => $untuk( 'budaya-material' ),
            ),
            array(
                'key'           => 'field_tk_terkait',
                'label'         => 'Terkait dengan',
                'name'          => 'terkait',
                'type'          => 'relationship',
                'instructions'  => 'Opsional. Tradisi atau budaya material lain yang berhubungan, mis. kitab yang dipakai dalam sebuah tradisi. Tautan otomatis tampil di kedua halaman.',
                'post_type'     => array( 'tradisi' ),
                'post_status'   => array( 'publish' ), // Hanya yang sudah terbit (juga di form publik).
                'filters'       => array( 'search' ),
                'return_format' => 'id',
                'max'           => 10,
                'bidirectional' => 1, // A terkait B ⇒ B otomatis terkait A.
                'bidirectional_target' => array( 'field_tk_terkait' ),
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
                'type'          => 'gallery', // ACF Pro. Tanpa Pro, ACF gratis menampilkannya sebagai field tak dikenal.
                'instructions'  => 'Foto pendukung (maks. ' . TK_GALERI_MAKS . '). Seret untuk mengubah urutan. Foto utama tidak perlu dimasukkan lagi di sini.',
                'return_format' => 'id',
                'library'       => 'all',
                'preview_size'  => 'medium',
                'insert'        => 'append',
                'max'           => TK_GALERI_MAKS,
                'mime_types'    => 'jpg,jpeg,png,webp',
                'max_size'      => TK_FOTO_MAKS_MB,
            ),
            array(
                'key'          => 'field_tk_kredit_foto',
                'label'        => 'Kredit Foto',
                'name'         => 'kredit_foto',
                'type'         => 'textarea',
                'rows'         => 3,
                'new_lines'    => '',
                'instructions' => 'Pembuat dan lisensi setiap foto, satu baris per foto. Contoh: "Foto utama: dokumentasi pribadi, Siti Aminah, 2024" atau "Foto 2: Budi Santoso, Wikimedia Commons, CC BY-SA 4.0". Tampil di halaman koleksi.',
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
