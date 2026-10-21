<?php
/**
 * Migrasi sekali jalan (2.14.2): kategori lama → kategori baru.
 *
 *   Tradisi          6 kategori ritual menurut Catherine Bell, Ritual:
 *                    Perspectives and Dimensions (1997).
 *   Budaya Material  10 kategori jenis benda (The Routledge Handbook of
 *                    Material Religion, 2024; Pintchman & Dempsey, Sacred
 *                    Matters, 2015).
 *
 * Berjalan sekali saat admin/kurator membuka wp-admin (opsi
 * tk_kategori_versi). Urutannya: buat kategori baru (beserta jenis &
 * keterangan), pindahkan setiap koleksi, lalu hapus kategori lama.
 *   Koleksi yang dikenal  dipetakan per judul (tk_kategori_migrasi_judul()).
 *   Koleksi lain          dipetakan dari kategori lamanya
 *                         (tk_kategori_migrasi_lama()).
 *
 * Setelah berjalan di semua situs, nama & keterangan kategori dikelola di
 * Koleksi → Kategori Tradisi. File ini boleh dihapus pada rilis berikutnya.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const TK_KATEGORI_VERSI = '2026-10-bell';

/**
 * Kategori baru: slug => array( nama, jenis, keterangan ).
 *
 * @return array<string, string[]>
 */
function tk_kategori_migrasi_baru() {
    return array(
        // Tradisi (Catherine Bell, 1997).
        'ritual-peralihan'                => array( 'Ritual Peralihan Kehidupan', 'tradisi', 'Rites of passage: kelahiran, khitan, perkawinan, kematian.' ),
        'ritual-kalender'                 => array( 'Ritual Kalender Keagamaan', 'tradisi', 'Calendrical rites: ritual berdasarkan kalender keagamaan, misalnya Maulid, Rajab, Ramadan, Syawalan.' ),
        'ritual-pertukaran-komunal'       => array( 'Ritual Pertukaran & Komunal', 'tradisi', 'Rites of exchange and communion: persembahan, ritual berbagi, solidaritas, sedekah makam, ziarah makam wali, slametan, kenduri, haul, peringatan tokoh.' ),
        'ritual-penyembuhan-perlindungan' => array( 'Ritual Penyembuhan & Perlindungan', 'tradisi', 'Rites of affliction: mengatasi penyakit atau ancaman, misalnya doa, ruqyah, tolak bala penyakit, krisis, musibah.' ),
        'ritual-santap-puasa-festival'    => array( 'Ritual Bersantap, Berpuasa & Festival', 'tradisi', 'Feasting, fasting, and festivals: perayaan kolektif, festival keagamaan, arak-arakan.' ),
        'ritual-politik-kekuasaan'        => array( 'Ritual Politik & Kekuasaan', 'tradisi', 'Political rites: ritual yang berkaitan dengan kekuasaan dan legitimasi.' ),
        // Budaya Material (jenis benda).
        'arsitektur-sakral'               => array( 'Arsitektur Sakral', 'budaya-material', 'Sacred architecture: masjid, pura, gereja, pesantren.' ),
        'benda-pemakaman'                 => array( 'Benda Pemakaman', 'budaya-material', 'Funerary objects: makam, nisan, epitaf, cungkup.' ),
        'objek-tekstual'                  => array( 'Objek Tekstual', 'budaya-material', 'Textual objects: manuskrip, mushaf, kitab, inskripsi.' ),
        'benda-ibadah'                    => array( 'Benda Ibadah', 'budaya-material', 'Devotional objects: tasbih, salib, rosario, jimat.' ),
        'benda-ritual'                    => array( 'Benda Ritual', 'budaya-material', 'Ritual objects: altar, wadah ritual, dupa, perlengkapan upacara.' ),
        'benda-visual'                    => array( 'Benda Visual', 'budaya-material', 'Visual objects: kaligrafi, lukisan, relief, ikon.' ),
        'zat-sakral'                      => array( 'Zat Sakral', 'budaya-material', 'Sacred substances: air, minyak, bunga, makanan.' ),
        'busana-keagamaan'                => array( 'Busana Keagamaan', 'budaya-material', 'Religious dress: jubah, kerudung, kopiah, atribut ritual.' ),
        'benda-bunyi-musik'               => array( 'Benda Bersuara & Musik', 'budaya-material', 'Musical-sound objects: bedug, gong, lonceng.' ),
        'benda-peringatan'                => array( 'Benda Peringatan', 'budaya-material', 'Commemorative objects: memorabilia, benda peninggalan tokoh, artefak haul.' ),
    );
}

/**
 * Kategori lama => kategori baru (untuk koleksi yang tidak ada di daftar judul).
 * Semua kategori lama di sini dihapus setelah migrasi.
 *
 * @return array<string, string>
 */
function tk_kategori_migrasi_lama() {
    return array(
        'kematian'           => 'ritual-peralihan',
        'pernikahan'         => 'ritual-peralihan',
        'keagamaan'          => 'ritual-kalender',
        'pertanian'          => 'ritual-kalender',
        'perayaan-adat'      => 'ritual-santap-puasa-festival',
        'tari-seni-ritual'   => 'ritual-santap-puasa-festival',
        'penyambutan-tamu'   => 'ritual-pertukaran-komunal',
        'tradisi-sosial'     => 'ritual-pertukaran-komunal',
        'adat-marapu'        => 'ritual-pertukaran-komunal',
        'kebudayaan-maritim' => 'ritual-pertukaran-komunal',
        'ritual-ketangkasan' => 'ritual-penyembuhan-perlindungan',
        'bangunan-situs'     => 'arsitektur-sakral',
        'benda-kerajinan'    => 'benda-ritual',
        'kuliner-ritual'     => 'zat-sakral',
        'naskah-kitab'       => 'objek-tekstual',
    );
}

/**
 * Pemetaan per koleksi: judul (post_title) => kategori baru.
 *
 * @return array<string, string[]>
 */
function tk_kategori_migrasi_judul() {
    return array(
        // Tradisi.
        '[Test] Ngaben'                     => array( 'ritual-peralihan' ),
        'Magirik Bissu'                     => array( 'ritual-penyembuhan-perlindungan' ),
        'Mappalili'                         => array( 'ritual-kalender' ),
        'Mapecca Sure'                      => array( 'ritual-kalender' ),
        'Maulid Ambelu'                     => array( 'ritual-kalender' ),
        'Mebayuh'                           => array( 'ritual-penyembuhan-perlindungan' ),
        'Pagulang'                          => array( 'ritual-pertukaran-komunal' ),
        'Ulumatan atau Persembahan'         => array( 'ritual-pertukaran-komunal', 'ritual-penyembuhan-perlindungan' ),
        '[Test] Tabuik'                     => array( 'ritual-kalender', 'ritual-santap-puasa-festival' ),
        '[Test] Pasola'                     => array( 'ritual-kalender', 'ritual-santap-puasa-festival' ),
        '[Test] Yadnya Kasada'              => array( 'ritual-kalender', 'ritual-pertukaran-komunal' ),
        '[Test] Nyusur Tanah'               => array( 'ritual-peralihan', 'ritual-pertukaran-komunal' ),
        'A’lammang'                         => array( 'ritual-kalender', 'ritual-santap-puasa-festival' ),
        'Allembara'                         => array( 'ritual-pertukaran-komunal' ),
        'Anggunjungi Korong Tigi'           => array( 'ritual-peralihan' ),
        'Baca Usus Ayam'                    => array( 'ritual-pertukaran-komunal' ),
        "Tradisi Henge'do atau Cium Hidung" => array( 'ritual-pertukaran-komunal' ),
        'Dabus'                             => array( 'ritual-penyembuhan-perlindungan' ),
        'Gala Marwa'                        => array( 'ritual-pertukaran-komunal' ),
        'Magiri'                            => array( 'ritual-penyembuhan-perlindungan' ),
        'Mapacci'                           => array( 'ritual-peralihan' ),
        'Maudu Lompoa'                      => array( 'ritual-kalender', 'ritual-santap-puasa-festival' ),
        'Melukat'                           => array( 'ritual-penyembuhan-perlindungan' ),
        'Pajaga'                            => array( 'ritual-politik-kekuasaan' ),
        'Potong ayam untuk tamu'            => array( 'ritual-pertukaran-komunal' ),
        'Sibalipari'                        => array( 'ritual-pertukaran-komunal' ),
        'Tiba Meka'                         => array( 'ritual-pertukaran-komunal' ),
        'Tradisi Matutu'                    => array( 'ritual-peralihan', 'ritual-pertukaran-komunal' ),
        // Budaya Material.
        'Batu Altar Jingtiu'                => array( 'benda-ritual' ),
        'Janur Kuning Bugis'                => array( 'benda-ritual' ),
        'Kitab Kuning Mattuttung'           => array( 'objek-tekstual' ),
        'Kotak Obat Tradisional Dayak'      => array( 'benda-ritual' ),
        'Makam Habib Ali Bidin'             => array( 'benda-pemakaman' ),
        'Makam Kelambu Kuning'              => array( 'benda-pemakaman' ),
        'Makam Tgk. Syeh Tuan Tapa'         => array( 'benda-pemakaman' ),
        'Masjid Shirotol Mustaqim'          => array( 'arsitektur-sakral' ),
        'Naskah Tradisi Miraje'             => array( 'objek-tekstual' ),
        'Pakkaja'                           => array( 'benda-ritual' ),
        "Al-Qur'an Syekh Soleh Darat"       => array( 'objek-tekstual', 'benda-peringatan' ),
        'Serosero'                          => array( 'zat-sakral' ),
        'Bella Pitunrupa'                   => array( 'zat-sakral' ),
        'Canang Sari'                       => array( 'benda-ritual' ),
        'Gendongan Bayi Tradisional Dayak'  => array( 'benda-ibadah' ),
        'Lumbung Desa Lung Anai'            => array( 'arsitektur-sakral' ),
        'Makam Habib Hasim'                 => array( 'benda-pemakaman' ),
        'Makam Keramat Mangkupalas'         => array( 'benda-pemakaman' ),
        'Makam Raja Datu Mahkota'           => array( 'benda-pemakaman' ),
        'Makam Syarifah Mariam binti Abdullah Al Idrus' => array( 'benda-pemakaman' ),
        'Masjid Putih'                      => array( 'arsitektur-sakral' ),
        'Onde-onde atau Umba-umba'          => array( 'zat-sakral' ),
        'Peca Sure'                         => array( 'zat-sakral' ),
        'Rumah Panggung Bugis'              => array( 'arsitektur-sakral' ),
        'Sirih Pinang'                      => array( 'zat-sakral' ),
        'Tenun Toraja'                      => array( 'busana-keagamaan' ),
        'Tombak Sumpit Dayak'               => array( 'benda-ritual' ),
    );
}

add_action( 'admin_init', 'tk_kategori_migrasi' );

/**
 * Jalankan migrasi kategori, sekali.
 */
function tk_kategori_migrasi() {
    if ( TK_KATEGORI_VERSI === get_option( 'tk_kategori_versi' ) || ! current_user_can( 'manage_categories' ) ) {
        return;
    }

    $tax = 'kategori-tradisi';

    // 1. Kategori baru.
    $id_baru = array();
    foreach ( tk_kategori_migrasi_baru() as $slug => list( $nama, $jenis, $ket ) ) {
        $ada = get_term_by( 'slug', $slug, $tax );
        if ( $ada ) {
            $id_baru[ $slug ] = $ada->term_id;
        } else {
            $hasil = wp_insert_term( $nama, $tax, array( 'slug' => $slug, 'description' => $ket ) );
            if ( is_wp_error( $hasil ) ) {
                continue;
            }
            $id_baru[ $slug ] = $hasil['term_id'];
        }
        update_term_meta( $id_baru[ $slug ], 'tk_jenis', $jenis );
        update_term_meta( $id_baru[ $slug ], '_tk_jenis', 'field_tk_kategori_jenis' ); // Referensi field ACF.
    }

    // 2. Pindahkan koleksi yang masih memakai kategori lama.
    $lama  = tk_kategori_migrasi_lama();
    $judul = tk_kategori_migrasi_judul();
    $ids   = get_posts( array(
        'post_type'      => 'tradisi',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'tax_query'      => array( array( 'taxonomy' => $tax, 'field' => 'slug', 'terms' => array_keys( $lama ) ) ), // phpcs:ignore WordPress.DB.SlowDBQuery -- sekali jalan.
    ) );
    foreach ( $ids as $id ) {
        $slug_lama = wp_get_object_terms( $id, $tax, array( 'fields' => 'slugs' ) );
        $raw       = get_post_field( 'post_title', $id, 'raw' );
        $dikenal   = isset( $judul[ $raw ] );
        $tujuan    = $dikenal ? $judul[ $raw ] : array();

        foreach ( $slug_lama as $s ) {
            if ( ! isset( $lama[ $s ] ) ) {
                $tujuan[] = $s; // Sudah kategori baru: dipertahankan.
            } elseif ( ! $dikenal ) {
                $tujuan[] = $lama[ $s ];
            }
        }

        $term_ids = array();
        foreach ( array_unique( $tujuan ) as $s ) {
            if ( isset( $id_baru[ $s ] ) ) {
                $term_ids[] = (int) $id_baru[ $s ];
                continue;
            }
            $t = get_term_by( 'slug', $s, $tax );
            if ( $t ) {
                $term_ids[] = (int) $t->term_id;
            }
        }
        wp_set_object_terms( $id, $term_ids, $tax );
    }

    // 3. Hapus kategori lama.
    foreach ( array_keys( $lama ) as $slug ) {
        $t = get_term_by( 'slug', $slug, $tax );
        if ( $t ) {
            wp_delete_term( $t->term_id, $tax );
        }
    }

    update_option( 'tk_kategori_versi', TK_KATEGORI_VERSI, true ); // Setelah selesai: terhenti di tengah = diulang.
}
