# Plugin: Database Tradisi Keagamaan (WARISI)

Plugin custom untuk situs **WARISI (Warisan Religi Indonesia)**, database tradisi keagamaan Indonesia. Plugin ini mengatur struktur data, field ACF, tampilan halaman depan, form kontribusi (akun maupun tamu), alur kurasi, dan seluruh CSS.

| Dokumen | Untuk siapa | Isi |
|---|---|---|
| **README.md** (ini) | Admin & kurator | Fitur, cara pakai, pengaturan awal, deployment |
| [PANDUAN-PENGEMBANG.md](PANDUAN-PENGEMBANG.md) | Pengembang | Struktur kode, konvensi, cara menambah & mengubah fitur |
| [CHANGELOG.md](CHANGELOG.md) | Semua | Riwayat versi |

---

## Daftar isi

1. [Pemasangan & pengaturan awal](#1-pemasangan--pengaturan-awal)
2. [Struktur data](#2-struktur-data)
3. [Shortcode](#3-shortcode)
4. [Kontribusi](#4-kontribusi)
5. [Kurasi](#5-kurasi)
6. [Halaman detail tradisi](#6-halaman-detail-tradisi)
7. [Menu](#7-menu)
8. [Pengaturan yang bisa diubah](#8-pengaturan-yang-bisa-diubah)
9. [Deployment ke server](#9-deployment-ke-server)

---

## 1. Pemasangan & pengaturan awal

**Kebutuhan:** WordPress 6.x, PHP 7.4+ (disarankan 8.2), tema GeneratePress, plugin ACF (gratis atau Pro).

1. Salin folder `tradisi-keagamaan/` ke `wp-content/plugins/`, lalu aktifkan di **Plugins**.
2. **Appearance → Customize → Additional CSS** harus **kosong**. Semua CSS ada di plugin.
3. **Settings → General:** centang **Anyone can register**, pilih **New User Default Role: Kontributor**.
4. **Settings → Permalinks:** klik **Save** sekali.
5. Buat halaman dan isi dengan block **Shortcode**:

| Halaman | Slug | Isi |
|---|---|---|
| Beranda (jadikan Homepage) | bebas | `[tk_hero]` `[tk_stats]` `[tk_koleksi]` |
| Peta Tradisi | bebas | `[tk_peta]` |
| Tambah Tradisi | `tambah-tradisi` | `[tk_form_tradisi]` |
| Dashboard Kurasi | `dashboard-kurasi` | `[tk_kurasi]` |
| Tentang | bebas | teks biasa |

6. Atur menu (lihat [bagian 7](#7-menu)).
7. Jadikan akun tim kurasi sebagai Kurator: **Users → Edit → Role: Kurator**.

Peran **Kontributor**, **Kontributor Tamu**, dan **Kurator**, serta akun sistem "Kontributor Tamu", dibuat otomatis saat plugin pertama kali berjalan.

---

## 2. Struktur data

| Jenis | Nama | Keterangan |
|---|---|---|
| Post type | `tradisi` | URL `/tradisi/nama-tradisi/`. Revisi aktif. |
| Taxonomy | `agama` | Hierarkis |
| Taxonomy | `wilayah` | Hierarkis. Level teratas = provinsi (dipakai stats & filter) |
| Taxonomy | `kategori-tradisi` | Hierarkis. Boleh lebih dari satu per tradisi |
| Taxonomy | `post_tag` | Tags bawaan WP sebagai "kata kunci" |
| ACF | `asal_daerah` | Text: kabupaten/kota |
| ACF | `deskripsi_singkat` | Textarea: abstrak |
| ACF | `tanggal_perayaan` | Date picker |
| ACF | `sumber_referensi` | URL |
| ACF | `galeri_foto` | Image (1 foto). Ganti ke Gallery setelah ACF Pro; kode sudah siap |
| ACF | `latitude`, `longitude` | Number: titik peta. Kosong = tidak tampil di peta |
| Meta | `tk_view_count` | Jumlah pembaca (otomatis; kurator/admin & pratinjau tidak dihitung) |

Meta alur kurasi (identitas tamu, riwayat, catatan, token) dijelaskan di [PANDUAN-PENGEMBANG.md](PANDUAN-PENGEMBANG.md#post-meta).

---

## 3. Shortcode

Tulis shortcode di block **Shortcode** (bukan Paragraph), dengan tanda kutip lurus `"`.

### `[tk_hero]`: pembuka Beranda

```
[tk_hero]
[tk_hero id="62"]
[tk_hero judul="..." deskripsi="..." label="..."]
```

| Atribut | Default | Fungsi |
|---|---|---|
| `label` | Database Digital Tradisi Keagamaan Indonesia | Teks kecil di atas judul |
| `judul` | Mengenal, Mendokumentasikan, ... | Judul besar |
| `deskripsi` | WARISI adalah ruang digital ... | Paragraf |
| `id` | otomatis | ID tradisi unggulan. Kosong = pembaca terbanyak |

### `[tk_stats]`: tiga kotak angka

Tanpa atribut. Menghitung tradisi terbit, provinsi (term `wilayah` level teratas), dan kabupaten/kota (nilai unik `asal_daerah`).

### `[tk_koleksi]`: panel Jelajahi + grid card

```
[tk_koleksi]
[tk_koleksi per_halaman="12"]
```

- Pencarian nama + dropdown **Provinsi** dan **Kategori** (bisa dikombinasikan).
- Parameter URL: `?cari=`, `?provinsi=`, `?kategori=`, `?hal=`.
- Panel punya `id="jelajahi"`, sehingga menu `/#jelajahi` langsung menggulir ke sana.
- Menambah dropdown filter (misalnya Agama): lihat [PANDUAN-PENGEMBANG.md](PANDUAN-PENGEMBANG.md#menambah-filter-di-panel-jelajahi).

### `[tk_peta]`: peta interaktif

```
[tk_peta]
[tk_peta tinggi="600"]
```

- Pin untuk setiap tradisi yang punya koordinat. Klik pin → detail di panel kanan (di HP, di bawah peta).
- Tradisi dengan koordinat sama (±11 m) digabung jadi **pin cokelat berangka**; klik untuk melihat daftarnya.
- Halaman detail tradisi otomatis menampilkan peta kecil bila koordinat ada.
- **Mengisi koordinat:** buka Google Maps, klik kanan lokasi, klik angka koordinat untuk menyalin (contoh `-8.4095, 115.1889`). Angka pertama → **Latitude**, kedua → **Longitude**. Sebisa mungkin isi **lokasi upacara**, bukan pusat kota.

### `[tk_form_tradisi]` dan `[tk_kurasi]`

Lihat [bagian 4](#4-kontribusi) dan [bagian 5](#5-kurasi).

---

## 4. Kontribusi

### Jenis pengirim

| Siapa | Bisa apa |
|---|---|
| **Kontributor Tamu** (tanpa login) | Kirim dengan Nama, Email, Instansi (opsional), dan Pernyataan. Tanpa draf. Revisi lewat link rahasia di email. Maks. 5 kiriman/jam/IP. |
| **Kontributor** (akun) | **Simpan Draf** atau **Kirim untuk Dikurasi**; pantau status & catatan kurator di **Kiriman Saya**; lanjutkan draf/revisi. Tidak bisa masuk wp-admin (kecuali Profil). |

Kiriman tamu tercatat atas nama akun sistem "Kontributor Tamu", tetapi di situs yang tampil sebagai penulis adalah **nama asli** pengirim. Email tamu hanya terlihat oleh kurator.

### Isi form

Nama tradisi, isi artikel (≥150 kata disarankan), foto utama (wajib → featured image), agama, provinsi/wilayah (wajib), kategori (wajib), kata kunci (→ Tags), lalu semua field Detail Tradisi.

"Simpan Draf" hanya mewajibkan Nama Tradisi. "Kirim untuk Dikurasi" mewajibkan semua field wajib.

---

## 5. Kurasi

### Alur

```
Tamu / Kontributor ── Kirim ──▶ Menunggu Kurasi ──┬── Terbitkan ────▶ Terpublikasi ──┐
        ▲                                         ├── Minta Revisi ─▶ Perlu Revisi   │ Kembalikan
        └──────────── Kirim ulang ◀───────────────┘                                 │ ke Antrean
                                                  └── Tolak ────────▶ Trash          │
                              Menunggu Kurasi ◀──────────────────────────────────────┘
```

| Kejadian | Email |
|---|---|
| Kiriman baru / kiriman ulang | ke kurator (atau admin bila belum ada kurator) |
| Kiriman baru | konfirmasi ke pengirim |
| Terbitkan | ke pengirim, dengan link |
| Minta Revisi | ke pengirim, dengan catatan & link revisi |
| Tolak | ke pengirim, dengan alasan |

**Catatan wajib** untuk Minta Revisi, Kembalikan ke Antrean, dan Tolak. Tradisi yang ditolak masuk Trash dan bisa **dipulihkan** dalam 30 hari.

### Dashboard Kurasi `[tk_kurasi]`

- **Ringkasan:** menunggu kurasi, menunggu revisi, terpublikasi.
- **Antrean** (terlama di atas): pengirim (label **Tamu** + email), lama menunggu (merah bila > 7 hari), **checklist kelengkapan**, tombol Pratinjau · Edit · Terbitkan, panel **Minta Revisi / Tolak**, dan **Riwayat**.
- **Riwayat Kurasi Saya:** tradisi yang pernah Anda putuskan, dengan tab saringan, status terkini, dan tombol **Lihat** / **Pratinjau** / **Pulihkan**.

### Panel Kurator

Saat kurator/admin membuka halaman tradisi (termasuk pratinjau), di atasnya muncul **Panel Kurator** berisi: status, pengirim, kurator, penyunting (dari revisi WordPress), terakhir diubah, checklist, tautan Edit/Bandingkan revisi, **Ubah status publikasi**, riwayat kurasi, dan riwayat suntingan. Pengunjung biasa tidak melihatnya.

| Status sekarang | Aksi tersedia |
|---|---|
| Menunggu Kurasi | Terbitkan · Minta Revisi · Tolak |
| Terpublikasi | Kembalikan ke Antrean · Minta Revisi · Tolak |
| Draf / Perlu Revisi | Terbitkan · Kembalikan ke Antrean · Tolak |

Riwayat juga tampil di kotak **Pengirim & Riwayat Kurasi** di sidebar editor wp-admin. Menerbitkan langsung dari editor tetap tercatat dan pengirim tetap diberi tahu.

---

## 6. Halaman detail tradisi

Urutan: Panel Kurator (khusus kurator) → link kembali → badge kategori & status → judul → meta (penulis, asal daerah, wilayah, pembaca) → gambar utama → abstrak → info (tanggal perayaan, agama, wilayah) → isi → galeri → peta lokasi → kata kunci & sumber → tradisi terkait.

Bagian yang datanya kosong tidak ditampilkan. Halaman tetap tampil walaupun ACF nonaktif.

---

## 7. Menu

**Appearance → Menus**, lokasi **Primary Menu**. Aktifkan kolom lewat **Screen Options → CSS Classes**.

| Item | Cara | CSS Class |
|---|---|---|
| Jelajahi Tradisi | Custom Link `/#jelajahi` | – |
| + Tambah Tradisi (tombol oranye) | Halaman Tambah Tradisi | `tk-menu-cta` |
| Dashboard Kurasi (hanya kurator/admin) | Halaman Dashboard Kurasi | `tk-menu-kurator` |
| Masuk / Keluar (otomatis) | Custom Link URL `#` | `tk-menu-akun` |

---

## 8. Pengaturan yang bisa diubah

Semua ada di **`includes/config.php`**:

| Konstanta | Default | Fungsi |
|---|---|---|
| `TK_SLUG_TAMBAH` | `tambah-tradisi` | Slug halaman form |
| `TK_SLUG_KURASI` | `dashboard-kurasi` | Slug halaman dashboard |
| `TK_TAMU_BATAS_PER_JAM` | `5` | Batas kiriman tamu per jam per IP |
| `TK_MIN_KATA_ISI` | `150` | Minimum kata untuk checklist "Isi" |
| `TK_KURASI_HARI_PERINGATAN` | `7` | Batas hari sebelum antrean ditandai merah |
| `TK_RIWAYAT_PER_HALAMAN` | `15` | Baris per halaman "Riwayat Kurasi Saya" |
| `TK_ROLES_VERSION` | `2` | Naikkan bila hak akses peran diubah |
| `TK_FONTS_URL` | Google Fonts | Sumber font |
| `TK_LEAFLET_VERSI` | `1.9.4` | Versi Leaflet dari CDN |

Warna situs diubah di bagian **Token** pada `assets/css/base.css`.

---

## 9. Deployment ke server

- **Font:** diambil dari Google Fonts. Bila server/pengunjung tanpa internet, host font secara lokal lalu ubah `TK_FONTS_URL`.
- **Peta:** Leaflet dari CDN, kecuali `assets/vendor/leaflet/` berisi `leaflet.js` & `leaflet.css` (salin folder `dist` dari https://leafletjs.com/download.html). Gambar peta (tile) tetap dari OpenStreetMap: pengunjung butuh internet, dan OpenStreetMap membatasi pemakaian berat.
- **Email:** pastikan SMTP berfungsi (mis. plugin WP Mail SMTP). Di LocalWP, email bisa dilihat di tab **Mailpit**.
- **Permalink:** setelah pindah server atau mengubah slug, buka **Settings → Permalinks → Save**.
- **Cache halaman:** plugin cache bisa membuat penghitung pembaca tidak bertambah, dan halaman form/dashboard **harus dikecualikan** dari cache.
- **Additional CSS** di Customizer harus tetap kosong.
