
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
│   │   ├── acf-fields.php         Field ACF "Detail Koleksi"
│   │   ├── kabupaten-kota.php     Saran, validasi & penyeragaman Kabupaten/Kota
│   │   ├── single-data.php        Data untuk templates/single-tradisi.php
│   │   ├── view-counter.php       Penghitung pembaca
│   │   ├── assets.php             Font, CSS per fitur, registrasi script peta
│   │   ├── menu.php               Menu: anchor, item kurator, Masuk/Keluar, ikon
│   │   ├── turnstile.php          Anti-bot Turnstile untuk form WARISI (opsional)
│   │   ├── footer.php             Footer: lembaga pengelola, tautan, hak cipta
│   │   ├── bagikan.php            Tombol Bagikan di halaman detail (tk_bagikan_render)
│   │   └── ikon-situs.php         Favicon (tag <link rel=icon>, /favicon.ico)
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
│   │   ├── email.php              Email ke kurator & pengirim; tk_kirim_email() untuk semua email
│   │   ├── aksi.php               Pemroses tombol kurasi (admin-post.php)
│   │   ├── komponen.php           HTML bersama dashboard & panel
│   │   ├── admin.php              Kotak riwayat di editor wp-admin
│   │   └── panel-kurator.php      Panel Kurator di halaman tradisi
│   │
│   ├── kontak/                    Form Hubungi Kami
│   │   └── proses.php             Validasi, lampiran, honeypot, batas per IP, email ke tim & konfirmasi
│   │
│   └── shortcodes/                TAMPILAN halaman depan (satu file per shortcode)
│       ├── hero.php               [tk_hero]
│       ├── stats.php              [tk_stats]
│       ├── koleksi.php            [tk_koleksi]
│       ├── peta.php               [tk_peta]
│       ├── form-tradisi.php       [tk_form_tradisi]
│       ├── kurasi.php             [tk_kurasi]
│       ├── kontak.php             [tk_kontak]
│       └── panduan.php            [tk_panduan_kontribusi]
│
├── templates/
│   └── single-tradisi.php         Halaman detail tradisi (HTML saja)
│
└── assets/
    ├── css/                       Satu file per fitur (lihat bagian 5)
    ├── img/logo-brin.png          Logo BRIN untuk footer (latar transparan, tulisan putih)
    ├── img/favicon.svg            Ikon situs (sumber); favicon-*.png & apple-touch-icon.png hasil render
    ├── js/
    │   ├── peta.js                Peta Leaflet, pin gabungan, panel detail
    │   ├── form.js                Tombol "Simpan Draf" (matikan validasi ACF)
    │   ├── galeri.js              Lightbox Galeri Foto di halaman detail
    │   ├── bagikan.js             Tombol Salin tautan & Instagram (Web Share API)
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
| Ikon SVG | `tk_icon( 'pin'\|'gedung'\|'buku'\|'benda'\|'kamera'\|'instagram'\|'centang'\|'bagikan'\|'user'\|'mata'\|'link', $size )` | core/helpers.php |
| Jenis sebuah post | `tk_get_jenis( $id )` (slug); label: `TK_JENIS[ tk_get_jenis( $id ) ]` | core/jenis.php |
| URL halaman form / dashboard | `tk_url_tambah()`, `tk_url_kurasi()` | core/helpers.php |
| URL tradisi untuk kurator | `tk_url_tinjau( $id )` (publik atau pratinjau) | core/helpers.php |
| IP pengunjung / batas per IP | `tk_ip_pengunjung()`, `tk_batas_per_ip( $awalan, $batas )` | core/helpers.php |
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
| Kirim email alur | `tk_email_ke_kurator()`, `tk_email_ke_pengirim( $id, $jenis, $catatan )` | kurasi/email.php |
| Kirim email apa pun (awalan nama situs + Reply-To tim) | `tk_kirim_email( $ke, $judul, $isi, $headers, $lampiran )` | kurasi/email.php |
| Kotak masuk tim (penerima Hubungi Kami & Reply-To) | `tk_email_tim()` | kurasi/email.php |

---

## 5. CSS

CSS dimuat oleh `core/assets.php` dalam urutan berikut. Semua file bergantung pada `base.css`.

| File | Isi |
|---|---|
| `base.css` | **Token** (warna, font, ukuran), dasar, header & menu, footer, komponen bersama: `.tk-btn`, `.tk-btn-kecil`, `.tk-label`, `.tk-pill`, `.tag-pill`, `.tk-status`, `.tk-notice`, `.tk-kosong`, `.tk-koleksi-head`, `.tk-halaman` |
| `beranda.css` | `[tk_hero]`, `[tk_stats]` |
| `koleksi.css` | `[tk_koleksi]`: panel Jelajahi, grid, card |
| `single.css` | Halaman detail tradisi (`.tk-single__*`) |
| `peta.css` | `[tk_peta]`, pin, panel, popup |
| `kontribusi.css` | `[tk_form_tradisi]`, Kiriman Saya |
| `kurasi.css` | `[tk_kurasi]`, Panel Kurator, riwayat, checklist |
| `kontak.css` | `[tk_kontak]` (kotak & input memakai `.tk-form` dari `kontribusi.css`) |
| `panduan.css` | `[tk_panduan_kontribusi]` |
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

### Menambah / mengubah field "Detail Koleksi"
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

### Email pemberitahuan
Semua email plugin berupa teks biasa dan dikirim lewat `tk_kirim_email()` (`kurasi/email.php`), yang:
- menambahkan awalan nama situs di subjek, mis. `[WARISI] Kiriman baru: ...`;
- menambahkan **Reply-To ke kotak masuk tim** (`tk_email_tim()` = `TK_KONTAK_EMAIL`, atau email admin bila kosong), kecuali `$headers` sudah berisi Reply-To. Dengan begitu balasan pengguna tetap sampai walau alamat pengirim di FluentSMTP `noreply@`.

Alamat & nama **pengirim** (From) tidak diatur plugin, melainkan di **Settings → FluentSMTP**.

**Daftar email**

| Kejadian | Penerima | Dipanggil dari |
|---|---|---|
| Kiriman baru / kiriman ulang / usulan perubahan masuk antrean | Semua Kurator (admin bila belum ada) | `tk_email_ke_kurator( $id, $ulang )` di `tk_form_after_save()` (`kontribusi/proses.php`) |
| Sama seperti di atas: konfirmasi | Pengirim | `tk_email_ke_pengirim( $id, 'diterima' \| 'diterima_ulang' )` di `tk_form_after_save()` |
| Terbitkan | Pengirim | `'terbitkan'` di `tk_kurasi_jalankan()` (`kurasi/aksi.php`), juga `tk_catat_terbit_dari_editor()` (`kurasi/riwayat.php`) bila diterbitkan dari editor wp-admin |
| Usulan perubahan disetujui | Pengirim | `'terapkan'` di `tk_kurasi_jalankan()` |
| Minta Revisi (berisi link revisi; token untuk tamu) | Pengirim | `'revisi'` di `tk_kurasi_jalankan()` |
| Tolak (berisi alasan) | Pengirim | `'tolak'` di `tk_kurasi_jalankan()` |
| Pesan Hubungi Kami (Reply-To = pengunjung, lampiran ikut) | Tim (`tk_email_tim()`) | `tk_kontak_kirim()` (`kontak/proses.php`) |
| Konfirmasi Hubungi Kami | Pengunjung | `tk_kontak_kirim_konfirmasi()` (`kontak/proses.php`) |

Kembalikan ke Antrean tidak mengirim email. Untuk usulan perubahan (`tk_usulan_asal()` terisi), `tk_email_ke_pengirim()` memakai teks khusus usulan; `'terbitkan'` tidak berlaku di sana (usulan diterapkan, bukan terbit).

**Menambah email baru**
1. Untuk alur kurasi: tambahkan `case '<jenis>'` di `tk_email_ke_pengirim()` (pada kedua `switch`: usulan & kiriman biasa bila perlu), lalu panggil dari tempat kejadiannya. Untuk email lain: panggil `tk_kirim_email()` langsung.
2. Jangan memanggil `wp_mail()` langsung, supaya awalan subjek & Reply-To konsisten.
3. **Email ke alamat yang diketik pengunjung** (mis. konfirmasi Hubungi Kami): jangan sertakan teks bebas dari pengunjung (nama, pesan, nama file). Alamat tujuan tidak terverifikasi, jadi teks itu bisa dipakai menitipkan spam ke alamat orang lain atas nama situs. Pakai hanya nilai dari daftar tetap (mis. perihal). Email ke pengirim kiriman tradisi aman memuat judul & catatan kurator karena alamatnya tersimpan bersama kiriman.
4. Nilai yang masuk ke header (nama di Reply-To) bersihkan dari `"`, `<`, `>`, `,`, dan baris baru.
5. Uji di LocalWP: email tertangkap di tab **Mailpit** (FluentSMTP lokal diarahkan ke sana). Periksa subjek, isi, dan header Reply-To.
6. Tambahkan barisnya di tabel di atas dan di tabel email README.

### Mengubah hak akses peran
Ubah daftar di `tk_setup_roles()` (`akun/peran.php`), lalu **naikkan `TK_ROLES_VERSION`** di `config.php` supaya peran dibuat ulang.

---

<a id="post-meta"></a>
## 7. Post meta

| Meta | Isi | Ditulis oleh |
|---|---|---|
| `asal_daerah`, `deskripsi_singkat`, `sistem_penanggalan`, `waktu_pelaksanaan`, `tanggal_perayaan`, `bahan`, `lokasi_keberadaan`, `fungsi`, `terkait`, `sumber_referensi`, `galeri_foto`, `kredit_foto`, `latitude`, `longitude` | Field Detail Koleksi | ACF |
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
| Form depan | Nonce & honeypot ACF; izin dicek ulang di `tk_form_guard()`; status tidak pernah langsung `publish`; uploader "basic" (tanpa Media Library); batas kiriman tamu per IP (`tk_batas_per_ip()`, IP asli di belakang Cloudflare) |
| Revisi tamu | Token acak 32 karakter, dibandingkan dengan `hash_equals()`, hanya berlaku selama status draf |
| Aksi kurasi | POST ke `admin-post.php`, nonce per tradisi, cek `tk_kurasi` + `edit_post`, cek aksi sah untuk status saat ini |
| Output | Semua di-escape; peta memakai `textContent` di JS |
| wp-admin | Kontributor diarahkan keluar; admin bar disembunyikan |
| Email tamu | Tidak pernah ditampilkan ke publik |
| Email keluar | Hanya lewat `tk_kirim_email()`; konfirmasi ke alamat ketikan pengunjung tanpa teks bebas pengunjung (anti-relay spam); nama di header dibersihkan; Hubungi Kami dilindungi Turnstile + batas per IP |

---

## 9. Pengujian manual sebelum commit

1. **Beranda:** hero, stats, pencarian + filter provinsi & kategori, pagination.
2. **Halaman detail:** sebagai pengunjung (tanpa Panel Kurator) dan sebagai kurator (dengan panel).
3. **Peta:** pin tampil, pin gabungan, panel detail, peta kecil di halaman detail.
4. **Form tamu** (jendela Incognito): kirim, lalu cek email di Mailpit (ke kurator + konfirmasi ke tamu).
5. **Form akun:** Simpan Draf → Lanjutkan → Kirim (konfirmasi ke pengirim tetap terkirim).
6. **Dashboard:** Minta Revisi → revisi lewat link → kirim ulang (email "Revisi Anda kami terima") → Terbitkan; coba Tolak → Pulihkan.
7. **Usulan perubahan:** kontributor ubah koleksi terbit miliknya → kirim → cek email kurator & konfirmasi → Setujui.
8. **Hubungi Kami:** kirim pesan; di Mailpit ada pesan ke tim (Reply-To pengunjung) dan konfirmasi singkat ke pengunjung (tanpa isi pesan, Reply-To tim).
9. Pastikan **tidak ada** peringatan PHP di halaman. Aktifkan `WP_DEBUG` di `wp-config.php` saat menguji.

Cek sintaks cepat (Site Shell LocalWP):

```
for /r wp-content\plugins\tradisi-keagamaan %f in (*.php) do php -l "%f"
```
