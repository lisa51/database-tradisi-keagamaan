# Panduan Pengembang: Plugin WARISI

Panduan untuk siapa pun yang akan membaca, mengubah, atau menambah kode plugin ini. Untuk cara pakai fitur, lihat [README.md](README.md).

## Daftar isi

1. [Struktur folder](#1-struktur-folder)
2. [Cara plugin dimuat](#2-cara-plugin-dimuat)
3. [Konvensi kode](#3-konvensi-kode)
4. [Peta modul & fungsi penting](#4-peta-modul--fungsi-penting)
5. [CSS](#5-css)
6. [Resep perubahan umum](#6-resep-perubahan-umum)
7. [Post meta](#post-meta)
8. [Keamanan](#8-keamanan)
9. [Pengujian manual sebelum commit](#9-pengujian-manual-sebelum-commit)

---

## 1. Struktur folder

```
tradisi-keagamaan/
├── tradisi-keagamaan.php          Loader: konstanta path + daftar modul ($tk_modules)
├── README.md                      Cara pakai (admin & kurator)
├── PANDUAN-PENGEMBANG.md          Dokumen ini
├── CHANGELOG.md                   Riwayat versi
│
├── includes/
│   ├── config.php                 SEMUA pengaturan yang bisa diubah (TK_*)
│   │
│   ├── core/                      Fondasi, dipakai semua fitur
│   │   ├── helpers.php            tk_term_names, tk_icon, tk_url_* (URL halaman)
│   │   ├── post-types.php         Post type "tradisi", taxonomy, template single
│   │   ├── jenis.php              Jenis: Tradisi / Budaya Material (taxonomy + field ACF)
│   │   ├── acf-fields.php         Field ACF "Detail Tradisi"
│   │   ├── kabupaten-kota.php     Saran, validasi & penyeragaman Kabupaten/Kota
│   │   ├── single-data.php        Data untuk templates/single-tradisi.php
│   │   ├── view-counter.php       Penghitung pembaca
│   │   ├── assets.php             Font, CSS per fitur, registrasi script peta
│   │   ├── menu.php               Menu: anchor, item kurator, Masuk/Keluar, ikon
│   │   └── turnstile.php          Anti-bot Turnstile untuk form WARISI (opsional)
│   │
│   ├── akun/                      Pengguna
│   │   ├── peran.php              Peran & akun sistem "Kontributor Tamu"
│   │   ├── akses.php              Batasi wp-admin & admin bar kontributor
│   │   └── login.php              Tampilan wp-login.php
│   │
│   ├── kontribusi/                Logika form kirim tradisi
│   │   ├── fields.php             Field ACF khusus form (identitas tamu, klasifikasi)
│   │   └── proses.php             Izin, validasi, penyimpanan
│   │
│   ├── kurasi/                    Alur kurasi
│   │   ├── data.php               Pengirim, status, kelengkapan, aturan aksi
│   │   ├── riwayat.php            Riwayat (_tk_log) & pencatatan otomatis
│   │   ├── token.php              Link revisi rahasia untuk tamu
│   │   ├── email.php              Email ke kurator & pengirim
│   │   ├── aksi.php               Pemroses tombol kurasi (admin-post.php)
│   │   ├── komponen.php           HTML bersama dashboard & panel
│   │   ├── admin.php              Kotak riwayat di editor wp-admin
│   │   └── panel-kurator.php      Panel Kurator di halaman tradisi
│   │
│   ├── kontak/                    Form Hubungi Kami
│   │   └── proses.php             Validasi, lampiran, honeypot, batas per IP, email ke admin
│   │
│   └── shortcodes/                TAMPILAN halaman depan (satu file per shortcode)
│       ├── hero.php               [tk_hero]
│       ├── stats.php              [tk_stats]
│       ├── koleksi.php            [tk_koleksi]
│       ├── peta.php               [tk_peta]
│       ├── form-tradisi.php       [tk_form_tradisi]
│       ├── kurasi.php             [tk_kurasi]
│       └── kontak.php             [tk_kontak]
│
├── templates/
│   └── single-tradisi.php         Halaman detail tradisi (HTML saja)
│
└── assets/
    ├── css/                       Satu file per fitur (lihat bagian 5)
    ├── js/
    │   ├── peta.js                Peta Leaflet, pin gabungan, panel detail
    │   ├── form.js                Tombol "Simpan Draf" (matikan validasi ACF)
    │   ├── galeri.js              Lightbox Galeri Foto di halaman detail
    │   └── kontak.js              Cek ukuran lampiran Hubungi Kami di browser
    └── vendor/leaflet/            (opsional) salinan lokal Leaflet
```

**Prinsip pembagian:**
- `includes/<domain>/` berisi **logika & data**. File di `shortcodes/` dan `templates/` berisi **tampilan**.
- Satu file = satu tanggung jawab. Kalau sebuah file melewati sekitar 300 baris atau mulai mengurus dua hal, pecah.

---

## 2. Cara plugin dimuat

1. WordPress memuat `tradisi-keagamaan.php`.
2. File itu mendefinisikan `TK_VERSION`, `TK_PATH`, `TK_URL`, lalu `require_once` setiap file di `$tk_modules` **sesuai urutan**.
3. Setiap modul hanya **mendefinisikan fungsi** dan **mendaftarkan hook** (`add_action`/`add_filter`/`add_shortcode`). Tidak ada kode yang langsung berjalan saat file dimuat, kecuali `define()` di `config.php`.

Karena itu, urutan modul hanya penting untuk `config.php` (paling atas). Fungsi dari modul lain boleh dipanggil di dalam fungsi, karena saat hook berjalan semua modul sudah dimuat.

---

## 3. Konvensi kode

### Penamaan

| Hal | Aturan | Contoh |
|---|---|---|
| Fungsi | awalan `tk_`, huruf kecil, `snake_case`, bahasa Indonesia | `tk_kurasi_render_item()` |
| Fungsi per modul | awalan kedua = nama modul | `tk_koleksi_*`, `tk_form_*`, `tk_kurasi_*`, `tk_panel_*` |
| Fungsi yang mengembalikan HTML | `*_render_*`, **return** string (bukan echo) | `tk_form_render_pesan()` |
| Konstanta | `TK_` + huruf besar, **hanya** di `config.php` | `TK_MIN_KATA_ISI` |
| Meta tersembunyi | awalan `_tk_` | `_tk_log` |
| Hak akses khusus | `tk_kirim`, `tk_kurasi` | `current_user_can( 'tk_kurasi' )` |
| CSS class | `.tk-*`; BEM untuk template (`.tk-single__meta`); varian `--` | `.tk-status--publish` |

### Susunan setiap file PHP

```php
<?php
/**
 * Judul singkat modul.
 *
 * Penjelasan: apa yang diurus file ini, fungsi utamanya, hook yang dipakai.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'nama_hook', 'tk_nama_fungsi' );   // Hook tepat di atas fungsinya.

/**
 * Apa yang dilakukan fungsi.
 *
 * @param int $post_id Penjelasan.
 * @return string Penjelasan.
 */
function tk_nama_fungsi( $post_id ) { ... }
```

### Gaya

- Standar penulisan WordPress: spasi di dalam kurung, `array()`, indentasi 4 spasi.
- **Semua output di-escape** (`esc_html`, `esc_attr`, `esc_url`) tepat saat dicetak.
- **Semua input disanitasi** (`sanitize_text_field`, `sanitize_key`, `absint`, `wp_unslash`).
- Data tradisi dibaca dengan `get_post_meta()`, bukan `get_field()`, supaya tampilan tetap jalan walau ACF nonaktif.
- Teks untuk pengguna ditulis dalam Bahasa Indonesia.
- Akhir baris **LF** (diatur `.gitattributes` di repo).

---

## 4. Peta modul & fungsi penting

| Kebutuhan | Fungsi | File |
|---|---|---|
| Nama term sebuah post | `tk_term_names( $id, $tax, $sep, $limit )` | core/helpers.php |
| Ikon SVG | `tk_icon( 'pin'\|'gedung'\|'buku'\|'benda'\|'user'\|'mata'\|'link', $size )` | core/helpers.php |
| Jenis sebuah post | `tk_get_jenis( $id )` (slug); label: `TK_JENIS[ tk_get_jenis( $id ) ]` | core/jenis.php |
| URL halaman form / dashboard | `tk_url_tambah()`, `tk_url_kurasi()` | core/helpers.php |
| URL tradisi untuk kurator | `tk_url_tinjau( $id )` (publik atau pratinjau) | core/helpers.php |
| Semua data satu tradisi | `tk_single_get_data( $id )` | core/single-data.php |
| ID gambar galeri (Image/Gallery) | `tk_get_galeri_ids( $value )` | core/single-data.php |
| Jumlah pembaca | `tk_get_view_count( $id )` | core/view-counter.php |
| Card tradisi | `tk_koleksi_render_kartu( $id )` | shortcodes/koleksi.php |
| Data titik peta | `tk_peta_get_titik( $ids )`, `tk_peta_render()` | shortcodes/peta.php |
| Nama / email pengirim | `tk_nama_pengirim()`, `tk_email_pengirim()` | kurasi/data.php |
| Label status (termasuk "Perlu Revisi") | `tk_status_kiriman( $post )` | kurasi/data.php |
| Checklist kelengkapan | `tk_kelengkapan( $id )` | kurasi/data.php |
| Aksi yang boleh per status | `tk_aksi_diizinkan( $status )` | kurasi/data.php |
| Catat riwayat | `tk_log_tambah( $id, $aksi, $catatan )` | kurasi/riwayat.php |
| Kirim email alur | `tk_email_ke_kurator()`, `tk_email_ke_pengirim()` | kurasi/email.php |

---

## 5. CSS

CSS dimuat oleh `core/assets.php` dalam urutan berikut. Semua file bergantung pada `base.css`.

| File | Isi |
|---|---|
| `base.css` | **Token** (warna, font, ukuran), dasar, header & menu, komponen bersama: `.tk-btn`, `.tk-btn-kecil`, `.tk-label`, `.tk-pill`, `.tag-pill`, `.tk-status`, `.tk-notice`, `.tk-kosong`, `.tk-koleksi-head`, `.tk-halaman` |
| `beranda.css` | `[tk_hero]`, `[tk_stats]` |
| `koleksi.css` | `[tk_koleksi]`: panel Jelajahi, grid, card |
| `single.css` | Halaman detail tradisi (`.tk-single__*`) |
| `peta.css` | `[tk_peta]`, pin, panel, popup |
| `kontribusi.css` | `[tk_form_tradisi]`, Kiriman Saya |
| `kurasi.css` | `[tk_kurasi]`, Panel Kurator, riwayat, checklist |
| `kontak.css` | `[tk_kontak]` (kotak & input memakai `.tk-form` dari `kontribusi.css`) |
| `login.css` | Halaman wp-login.php (dimuat terpisah) |

**Aturan:**
- **Warna hanya lewat token** `var(--tk-*)` dari `base.css`. Jangan menulis kode hex di file lain.
- Komponen yang dipakai lebih dari satu fitur → taruh di `base.css`.
- Setiap file punya blok `Responsif` sendiri di bagian akhir (tablet ≤ 900px, HP ≤ 600px).
- Jangan memberi gaya lewat class `single-tradisi`. WordPress memasang class itu di `<body>` halaman detail, sehingga aturannya ikut mengenai seluruh halaman.
- CSS dimuat **setelah** CSS GeneratePress, jadi aturan umum (body, a, h1) menang tanpa `!important`.

---

## 6. Resep perubahan umum

### Mengubah warna situs
Ubah nilai token di bagian **1. Token** pada `assets/css/base.css`.

### Menambah shortcode baru
1. Buat `includes/shortcodes/nama.php` dengan susunan file standar.
2. `add_shortcode( 'tk_nama', 'tk_nama_shortcode' );` dan fungsi yang **mengembalikan** HTML.
3. Daftarkan file di `$tk_modules` (bagian "Shortcode").
4. Buat `assets/css/nama.css`, lalu tambahkan `'nama'` ke `tk_css_files()` di `core/assets.php`.
5. Tambahkan dokumentasinya di README bagian 3.

### Menambah filter di panel Jelajahi
Tambahkan satu baris di `tk_koleksi_filter_taksonomi()` (`shortcodes/koleksi.php`):

```php
'pilih_agama' => array( 'taxonomy' => 'agama', 'label' => 'Semua Agama', 'hanya_induk' => false ),
```

Dropdown, query, pagination, tombol hapus pencarian, dan lebar kolom menyesuaikan otomatis.

> **Jangan** memakai nama kunci yang sama dengan nama taxonomy (`agama`, `wilayah`, `kategori-tradisi`) atau parameter bawaan WordPress (`s`, `p`, `cat`, `tag`, `page`, `paged`, `name`, `author`, `year`, `m`). WordPress membaca nama itu sendiri, dan Beranda akan berubah menjadi halaman arsip.

### Menambah / mengubah field "Detail Tradisi"
1. Ubah di **ACF → Field Groups**, lalu **ACF → Tools → Generate PHP**.
2. Ganti isi `acf_add_local_field_group()` di `core/acf-fields.php`, dan pastikan `'key' => TK_DETAIL_GROUP`.
3. **Jangan** ubah `key` field yang sudah ada.
4. Bila field perlu tampil di halaman detail, tambahkan ke `tk_single_get_data()` lalu ke template.
5. Bila perlu masuk checklist kurator, tambahkan ke `tk_kelengkapan()`.

### Galeri Foto (ACF Pro)
- `galeri_foto` bertipe **Gallery** (`core/acf-fields.php`), hanya tampil di wp-admin. Di form depan disembunyikan oleh `tk_form_sembunyikan_galeri()`, karena field Gallery butuh Media Library yang tidak bisa dipakai tamu/kontributor.
- Form depan memakai Repeater **`galeri_unggah`** ("Foto Tambahan", `kontribusi/fields.php`) berisi field Image dengan uploader basic. Setelah simpan, `tk_form_pindahkan_galeri()` (`kontribusi/proses.php`) memindahkan fotonya ke `galeri_foto`, lalu mengosongkan `galeri_unggah`. Hanya foto dengan `post_parent` = tradisi itu yang diterima (mencegah ID lampiran lain disisipkan).
- Tampilan: `templates/single-tradisi.php` + lightbox `assets/js/galeri.js` (dimuat hanya di halaman tradisi) + gaya `.tk-galeri` / `.tk-lightbox` di `single.css`.
- `tk_get_galeri_ids()` menerima semua format (ID tunggal, "1,2", array ID, array ACF), jadi data lama tetap terbaca.
- Batas jumlah & ukuran: `TK_GALERI_MAKS`, `TK_FOTO_MAKS_MB` di `config.php`.

### Menambah kriteria checklist kurator
Tambahkan satu baris `'Label' => kondisi_boolean` di `tk_kelengkapan()` (`kurasi/data.php`).

### Menambah aksi kurasi baru
1. Tambahkan ke `tk_aksi_diizinkan()` (status mana saja) dan bila perlu `tk_aksi_wajib_catatan()` (`kurasi/data.php`).
2. Tambahkan label riwayat di `tk_log_label()` dan, bila merupakan keputusan kurator, di `tk_log_aksi_kurator()` (`kurasi/riwayat.php`).
3. Tambahkan `case` di `tk_kurasi_handle()` (`kurasi/aksi.php`).
4. Tambahkan label tombol di `tk_panel_tombol_aksi()` (`kurasi/panel-kurator.php`) dan pesan di `tk_kurasi_render_pesan()` (`kurasi/komponen.php`).

### Mengubah hak akses peran
Ubah daftar di `tk_setup_roles()` (`akun/peran.php`), lalu **naikkan `TK_ROLES_VERSION`** di `config.php` supaya peran dibuat ulang.

---

<a id="post-meta"></a>
## 7. Post meta

| Meta | Isi | Ditulis oleh |
|---|---|---|
| `asal_daerah`, `deskripsi_singkat`, `sistem_penanggalan`, `waktu_pelaksanaan`, `tanggal_perayaan`, `bahan`, `lokasi_keberadaan`, `fungsi`, `terkait`, `sumber_referensi`, `galeri_foto`, `latitude`, `longitude` | Field Detail Tradisi | ACF |
| `foto_utama`, `kata_kunci`, `tk_form_*` | Field form (diubah menjadi featured image, Tags, dan term) | ACF (form) |
| `tk_tamu_nama`, `tk_tamu_email`, `tk_tamu_instansi`, `tk_tamu_setuju` | Identitas kontributor tamu | ACF (form tamu) |
| `tk_view_count` | Jumlah pembaca | `core/view-counter.php` |
| `_tk_log` | Riwayat kurasi (array: waktu, aksi, oleh, user_id, catatan) | `kurasi/riwayat.php` |
| `_tk_dikurasi_oleh` | ID kurator yang pernah memutuskan (satu baris per kurator) | `kurasi/riwayat.php` |
| `_tk_perlu_revisi` | `1` = dikembalikan untuk revisi | `kurasi/aksi.php` |
| `_tk_catatan` | Catatan revisi / alasan tolak terakhir | `kurasi/aksi.php` |
| `_tk_token` | Token link revisi tamu | `kurasi/token.php` |

Option WordPress: `tk_roles_version`, `tk_user_tamu`, `tk_penanda_kurator_v1`.

---

## 8. Keamanan

| Titik | Perlindungan |
|---|---|
| Form depan | Nonce & honeypot ACF; izin dicek ulang di `tk_form_guard()`; status tidak pernah langsung `publish`; uploader "basic" (tanpa Media Library); batas kiriman tamu per IP |
| Revisi tamu | Token acak 32 karakter, dibandingkan dengan `hash_equals()`, hanya berlaku selama status draf |
| Aksi kurasi | POST ke `admin-post.php`, nonce per tradisi, cek `tk_kurasi` + `edit_post`, cek aksi sah untuk status saat ini |
| Output | Semua di-escape; peta memakai `textContent` di JS |
| wp-admin | Kontributor diarahkan keluar; admin bar disembunyikan |
| Email tamu | Tidak pernah ditampilkan ke publik |

---

## 9. Pengujian manual sebelum commit

1. **Beranda:** hero, stats, pencarian + filter provinsi & kategori, pagination.
2. **Halaman detail:** sebagai pengunjung (tanpa Panel Kurator) dan sebagai kurator (dengan panel).
3. **Peta:** pin tampil, pin gabungan, panel detail, peta kecil di halaman detail.
4. **Form tamu** (jendela Incognito): kirim, lalu cek email di Mailpit.
5. **Form akun:** Simpan Draf → Lanjutkan → Kirim.
6. **Dashboard:** Minta Revisi → revisi lewat link → kirim ulang → Terbitkan; coba Tolak → Pulihkan.
7. Pastikan **tidak ada** peringatan PHP di halaman. Aktifkan `WP_DEBUG` di `wp-config.php` saat menguji.

Cek sintaks cepat (Site Shell LocalWP):

```
for /r wp-content\plugins\tradisi-keagamaan %f in (*.php) do php -l "%f"
```
