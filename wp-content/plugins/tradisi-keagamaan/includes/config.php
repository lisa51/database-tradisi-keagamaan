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

/* =============================================================================
 * Field ACF — JANGAN diubah setelah ada data, karena data terikat pada key ini
 * ========================================================================== */

/** Grup "Detail Tradisi" (includes/core/acf-fields.php). */
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

/** Penerima pesan. Kosong = email admin (Settings → General → Administration Email Address). */
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
