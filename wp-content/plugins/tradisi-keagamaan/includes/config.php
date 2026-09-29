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

/** Halaman berisi [tk_form_tradisi]. */
define( 'TK_SLUG_TAMBAH', 'tambah-tradisi' );

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

/** Nama post meta jumlah pembaca. */
define( 'TK_VIEW_META', 'tk_view_count' );

/* =============================================================================
 * Aturan kontribusi & kurasi
 * ========================================================================== */

/** Batas kiriman tamu per jam per alamat IP (anti-spam). */
define( 'TK_TAMU_BATAS_PER_JAM', 5 );

/** Jumlah kata minimum isi artikel agar lolos checklist "Isi". */
define( 'TK_MIN_KATA_ISI', 150 );

/** Kiriman yang menunggu lebih dari sekian hari ditandai merah di dashboard. */
define( 'TK_KURASI_HARI_PERINGATAN', 7 );

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
