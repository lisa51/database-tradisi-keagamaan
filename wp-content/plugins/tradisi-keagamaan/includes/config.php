<?php
/**
 * Konfigurasi plugin WARISI.
 *
 * SEMUA angka, nama, dan kunci yang mungkin perlu diubah dikumpulkan di sini,
 * supaya tidak perlu mencari-cari di banyak file. Ubah nilainya, simpan,
 * lalu refresh halaman.
 *
 * Konvensi: semua konstanta berawalan TK_ (singkatan "Tradisi Keagamaan").
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================================================
 * Halaman (slug) — harus sama dengan slug halaman di Pages
 * ========================================================================== */

/**
 * URL arsip semua koleksi (/koleksi/). Halaman detail memakai slug jenis
 * (/tradisi/nama/, /budaya-material/nama/); link /koleksi/nama/ dialihkan.
 * Aturan URL diperbarui otomatis saat nilai ini atau TK_JENIS diubah.
 */
define( 'TK_SLUG_KOLEKSI', 'koleksi' );

/** Halaman berisi [tk_form_tradisi]. */
define( 'TK_SLUG_TAMBAH', 'tambah-tradisi' );

/** Halaman "Ubah Tradisi", juga berisi [tk_form_tradisi] (dibuka dengan ?edit=ID). */
define( 'TK_SLUG_UBAH', 'ubah-tradisi' );

/** Halaman "Riwayat Suntingan", berisi [tk_riwayat_suntingan] (dibuka dengan ?id=ID). */
define( 'TK_SLUG_RIWAYAT', 'riwayat-suntingan' );

/** Halaman berisi [tk_kurasi]. */
define( 'TK_SLUG_KURASI', 'dashboard-kurasi' );

/** Halaman "Panduan Kontribusi", berisi [tk_panduan_kontribusi]. */
define( 'TK_SLUG_PANDUAN', 'panduan-kontribusi' );

/* =============================================================================
 * Lembaga & footer (includes/core/footer.php)
 * ========================================================================== */

/** Lembaga pengelola, dari unit terkecil ke induk. Tampil di footer. */
define( 'TK_LEMBAGA', array(
    'Pusat Riset Khazanah Keagamaan dan Peradaban',
    'Organisasi Riset Arkeologi, Bahasa, dan Sastra (OR Arbastra)',
    'Badan Riset dan Inovasi Nasional (BRIN)',
) );

/**
 * Logo lembaga induk di footer (path relatif folder plugin) & teks alternatifnya.
 * Versi untuk latar gelap: latar transparan, tulisan putih. Kosongkan untuk menyembunyikan.
 */
define( 'TK_LOGO_LEMBAGA', 'assets/img/logo-brin.png' );
define( 'TK_LOGO_LEMBAGA_ALT', 'Logo Badan Riset dan Inovasi Nasional (BRIN)' );

/** Slogan di bawah nama situs pada footer. */
define( 'TK_SLOGAN', 'Warisan Religi Indonesia' );

/**
 * Tautan footer per kolom: slug halaman => label. Halaman yang belum dibuat
 * dilewati. Awali dengan "/" untuk tautan langsung (mis. "/#jelajahi").
 */
define( 'TK_FOOTER_TAUTAN', array(
    'Jelajahi'      => array(
        '/'            => 'Beranda',
        '/#jelajahi'   => 'Jelajahi Khazanah',
        'peta-tradisi' => 'Peta Koleksi',
        'tentang'      => 'Tentang WARISI',
    ),
    'Berkontribusi' => array(
        TK_SLUG_PANDUAN => 'Panduan Kontribusi',
        TK_SLUG_TAMBAH  => 'Kirim Koleksi',
        'hubungi-kami'  => 'Hubungi Kami',
    ),
) );

/* =============================================================================
 * Field ACF — JANGAN diubah setelah ada data, karena data terikat pada key ini
 * ========================================================================== */

/** Grup "Detail Koleksi" (includes/core/acf-fields.php). */
define( 'TK_DETAIL_GROUP', 'group_6aa0f20d00bc9' );

/** Grup "Formulir Kontributor" (includes/kontribusi/fields.php). */
define( 'TK_FORM_GROUP', 'group_tk_form_kontributor' );

/** Grup "Identitas Pengirim" untuk tamu (includes/kontribusi/fields.php). */
define( 'TK_TAMU_GROUP', 'group_tk_form_tamu' );

/**
 * Jenis konten (taxonomy "jenis", includes/core/jenis.php): slug term => label.
 * Slug tersimpan di database, jadi jangan diubah; label boleh diubah.
 */
define( 'TK_JENIS', array(
    'tradisi'         => 'Tradisi',
    'budaya-material' => 'Budaya Material',
) );

/** Nama post meta jumlah pembaca. */
define( 'TK_VIEW_META', 'tk_view_count' );

/* =============================================================================
 * Aturan kontribusi & kurasi
 * ========================================================================== */

/** Batas kiriman tamu per jam per alamat IP (anti-spam). */
define( 'TK_TAMU_BATAS_PER_JAM', 5 );

/** Jumlah kata minimum isi artikel agar lolos checklist "Isi". */
define( 'TK_MIN_KATA_ISI', 150 );

/** Jumlah foto maksimum di Galeri Foto satu tradisi (wp-admin & form depan). */
define( 'TK_GALERI_MAKS', 12 );

/** Ukuran maksimum per foto yang diunggah (MB): foto utama & galeri. */
define( 'TK_FOTO_MAKS_MB', 5 );

/** Kiriman yang menunggu lebih dari sekian hari ditandai merah di dashboard. */
define( 'TK_KURASI_HARI_PERINGATAN', 7 );

/** Jumlah koleksi per halaman di Dashboard Kurasi (Menunggu Revisi, Terpublikasi). */
define( 'TK_KURASI_PER_HALAMAN', 20 );

/** Jumlah baris per halaman di "Riwayat Kurasi Saya". */
define( 'TK_RIWAYAT_PER_HALAMAN', 15 );

/* =============================================================================
 * Form Hubungi Kami [tk_kontak]
 * ========================================================================== */

/** Penerima pesan Hubungi Kami & alamat Reply-To semua email plugin. Kosong = email admin (Settings → General → Administration Email Address). */
define( 'TK_KONTAK_EMAIL', '' );

/** Batas pesan per jam per alamat IP (anti-spam). */
define( 'TK_KONTAK_BATAS_PER_JAM', 3 );

/** Ukuran maksimum lampiran (MB). Jangan melebihi upload_max_filesize & batas server email. */
define( 'TK_KONTAK_LAMPIRAN_MAKS_MB', 5 );

/**
 * Format lampiran yang diizinkan (ekstensi => MIME).
 * Hindari format yang bisa berisi makro/skrip (doc, xls, docm, zip, svg, html).
 */
define( 'TK_KONTAK_LAMPIRAN_TIPE', array(
    'jpg|jpeg' => 'image/jpeg',
    'png'      => 'image/png',
    'pdf'      => 'application/pdf',
    'docx'     => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
) );

/**
 * Rentang IP proxy Cloudflare (https://www.cloudflare.com/ips/). Permintaan dari
 * rentang ini memakai header CF-Connecting-IP sebagai IP pengunjung, untuk
 * batas per IP (tk_ip_pengunjung()). Perbarui bila Cloudflare mengubah daftarnya.
 */
define( 'TK_CLOUDFLARE_IP', array(
    '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
    '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
    '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
    '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
    '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
    '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
) );

/**
 * Versi daftar peran & hak akses (includes/akun/peran.php).
 * Naikkan angka ini setiap kali hak akses peran diubah agar peran dibuat ulang.
 */
define( 'TK_ROLES_VERSION', 2 );

/* =============================================================================
 * Aset eksternal
 * ========================================================================== */

/**
 * Font Google: DM Serif Display (judul) + DM Sans (teks).
 * Untuk server tanpa internet: host font secara lokal (lihat README → Deployment).
 */
define( 'TK_FONTS_URL', 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=DM+Serif+Display&display=swap' );

/** Versi Leaflet (peta). Salinan lokal di assets/vendor/leaflet/ dipakai bila ada. */
define( 'TK_LEAFLET_VERSI', '1.9.4' );
