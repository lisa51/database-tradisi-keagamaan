# Plugin: Database Tradisi Keagamaan (WARISI)

Plugin custom untuk situs **WARISI (Warisan Religi Indonesia)**. Plugin ini mengatur struktur data tradisi, field ACF, shortcode Beranda, dan seluruh CSS tampilan.

## Struktur folder

```
tradisi-keagamaan/
├── tradisi-keagamaan.php        File utama. Hanya memuat modul di /includes.
├── README.md                    Dokumen ini.
├── assets/
│   └── css/
│       └── warisi.css           Semua CSS situs (warna, header, Beranda, single, peta).
│   ├── js/
│   │   ├── peta.js              Script peta interaktif (Leaflet).
│   │   └── form.js              Tombol "Simpan Draf" di form kontributor.
│   └── vendor/leaflet/          (opsional) salinan lokal Leaflet untuk server internal.
├── includes/
│   ├── helpers.php              Fungsi bantu: nama term, ikon SVG, URL.
│   ├── post-types.php           CPT "tradisi", taxonomy, tags, template single.
│   ├── acf-fields.php           Field ACF "Detail Tradisi".
│   ├── view-counter.php         Penghitung pembaca (meta tk_view_count).
│   ├── assets.php               Memuat font Google + warisi.css.
│   ├── roles.php                Peran Kontributor & Kurator, akses wp-admin, halaman login.
│   ├── menu.php                 Menu: anchor, item khusus kurator, tombol Masuk/Keluar.
│   ├── single.php               Data & logika untuk halaman single tradisi.
│   ├── kurasi-alur.php          Riwayat, checklist, pengirim (akun/tamu), token revisi, email, aturan aksi.
│   ├── panel-kurator.php        Panel Kurator di halaman tradisi (khusus kurator).
│   └── shortcodes/
│       ├── hero.php             [tk_hero]
│       ├── stats.php            [tk_stats]
│       ├── koleksi.php          [tk_koleksi]
│       ├── peta.php             [tk_peta] + peta kecil di halaman single
│       ├── form-tradisi.php     [tk_form_tradisi] form kirim tradisi
│       └── kurasi.php           [tk_kurasi] Dashboard Kurasi
└── templates/
    └── single-tradisi.php       Tampilan halaman detail tradisi (HTML saja).
```

Semua fungsi memakai awalan `tk_` supaya tidak bentrok dengan plugin lain.

## Struktur data

| Jenis | Nama | Keterangan |
|---|---|---|
| Post type | `tradisi` | URL `/tradisi/nama-tradisi/` |
| Taxonomy | `agama` | Hierarkis |
| Taxonomy | `wilayah` | Level teratas = provinsi. Dipakai stats dan filter |
| Taxonomy | `kategori-tradisi` | Boleh lebih dari satu per tradisi |
| Taxonomy | `post_tag` | Tags bawaan WP, sebagai "kata kunci" |
| ACF | `asal_daerah` | Text. Kabupaten/kota |
| ACF | `deskripsi_singkat` | Textarea. Abstrak |
| ACF | `tanggal_perayaan` | Date picker (format `d/m/Y`) |
| ACF | `sumber_referensi` | URL |
| ACF | `galeri_foto` | Image (1 foto). Ganti ke Gallery setelah ACF Pro |
| ACF | `latitude`, `longitude` | Number. Koordinat pin di peta. Kosong = tidak tampil di peta |
| Meta | `tk_view_count` | Jumlah pembaca (otomatis) |

## Shortcode

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

### `[tk_koleksi]`: pencarian + grid card

```
[tk_koleksi]
[tk_koleksi per_halaman="12"]
```

Membaca parameter URL `?cari=`, `?provinsi=` (slug wilayah), dan `?hal=`. Panel pencarian punya `id="jelajahi"`, jadi menu `/#jelajahi` langsung menggulir ke sana.

### `[tk_peta]`: peta interaktif

```
[tk_peta]
[tk_peta tinggi="600"]
```

Peta sebaran semua tradisi yang koordinatnya sudah diisi. Klik pin untuk menampilkan detail di panel kanan (di HP, panel pindah ke bawah peta).

Tradisi dengan koordinat yang sama (selisih kurang dari ±11 meter) digabung menjadi satu pin cokelat berangka. Klik pin itu untuk melihat daftar tradisinya, lalu klik salah satu nama untuk detailnya. Batas penggabungan diatur lewat `PRESISI_GRUP` di `assets/js/peta.js`. Sebisa mungkin, isi koordinat **lokasi upacara**, bukan pusat kota, supaya pin terpisah dengan sendirinya. Halaman single juga otomatis menampilkan peta kecil kalau koordinatnya ada.

Cara mengisi koordinat: buka Google Maps, klik kanan lokasi, klik angka koordinat untuk menyalin (contoh `-8.4095, 115.1889`). Angka pertama masuk ke **Latitude**, angka kedua ke **Longitude**.

Susunan Beranda saat ini:

```
[tk_hero id="62"]
[tk_stats]
[tk_koleksi]
```

## Kontribusi & kurasi

### Jenis pengirim & peran

| Siapa | Bisa apa |
|---|---|
| **Kontributor Tamu** (tanpa login) | Kirim tradisi dengan mengisi Nama, Email, dan pernyataan persetujuan. Tidak ada draf. Revisi lewat link rahasia di email. Dibatasi 5 kiriman/jam/IP. |
| **Kontributor** (akun) | Kirim atau Simpan Draf, pantau status & catatan kurator di "Kiriman Saya", lanjutkan draf/revisi. Tidak bisa masuk wp-admin (kecuali Profil). |
| **Kurator** | Dashboard Kurasi (terbitkan, minta revisi, tolak), edit tradisi & term di wp-admin. Tidak bisa mengubah pengaturan, plugin, atau pengguna. |
| **Administrator** | Semua hak, termasuk kurasi. |

Peran **Kontributor Tamu** dipakai oleh satu akun sistem bernama "Kontributor Tamu" (dibuat otomatis, tidak untuk login). Akun ini menjadi "penulis" semua kiriman tamu di database, sedangkan nama & email asli pengirim disimpan di meta `tk_tamu_nama` dan `tk_tamu_email`. Di situs, yang tampil sebagai penulis adalah nama asli pengirim; emailnya hanya terlihat oleh kurator.

Peran dibuat oleh `includes/roles.php`. Kalau daftar hak akses diubah, naikkan `TK_ROLES_VERSION`.

### Alur

```
Tamu / Kontributor ── Kirim ──▶ Menunggu Kurasi ──┬── Terbitkan ──▶ Terpublikasi   (email ke pengirim)
        ▲                                         ├── Minta Revisi ─▶ Perlu Revisi  (email + catatan + link revisi)
        └─────────── Kirim ulang ◀────────────────┘                     │
                                                  └── Tolak ─────▶ Trash (email + alasan, bisa dipulihkan 30 hari)
```

- Setiap kiriman & kiriman ulang → kurator mendapat email.
- Kiriman baru → pengirim mendapat email konfirmasi.
- Minta Revisi dan Tolak **wajib** disertai catatan.
- Link revisi tamu berisi token rahasia (`?edit=ID&token=...`), dibuat ulang setiap kali revisi diminta, dan tidak berlaku lagi setelah kiriman dikirim ulang.

### Dashboard Kurasi

Setiap kiriman di antrean menampilkan:
- Pengirim (dengan label **Tamu** dan email bila tamu), lama menunggu (merah bila lebih dari 7 hari), wilayah & kategori.
- **Checklist kelengkapan**: foto utama, abstrak, isi ≥150 kata, sumber, asal daerah, wilayah, kategori, koordinat. Kriteria diatur di `tk_kelengkapan()` (`includes/kurasi-alur.php`).
- Tombol **Pratinjau**, **Edit**, **Terbitkan**, serta panel **Minta Revisi / Tolak** dan **Riwayat**.

Di bawah antrean ada **Riwayat Kurasi Saya**: semua tradisi yang pernah Anda terbitkan, minta revisi, atau tolak, dengan tab saringan (Semua, Diterbitkan, Diminta Revisi, Ditolak), status terkini, dan tombol **Lihat** (terbit), **Pratinjau** (menunggu/perlu revisi), atau **Pulihkan** (ditolak, kembali menjadi Draf). Daftar ini memakai meta `_tk_dikurasi_oleh`; riwayat lama diisi otomatis sekali jalan dengan mencocokkan nama kurator.

Riwayat (siapa melakukan apa, kapan, dan catatannya) juga tampil di kotak **Pengirim & Riwayat Kurasi** di sidebar editor tradisi wp-admin. Kalau kurator menerbitkan langsung dari editor, tetap tercatat dan pengirim tetap diberi tahu.

### Panel Kurator

Saat kurator/admin membuka halaman tradisi (termasuk pratinjau kiriman yang belum terbit), di bagian atas muncul **Panel Kurator** (`includes/panel-kurator.php`). Pengunjung biasa tidak melihatnya. Isinya:

- **Status** saat ini (dan penanda mode pratinjau).
- **Pengirim** (akun/tamu, email, instansi, tanggal dibuat), **Kurator** yang pernah memutuskan, **Penyunting** (dari revisi WordPress, beserta jumlah suntingan), dan **Terakhir diubah** oleh siapa.
- **Checklist kelengkapan**.
- Tautan **Edit di wp-admin**, **Bandingkan revisi**, **Dashboard Kurasi**.
- **Ubah status publikasi** dengan catatan. Tombol menyesuaikan status:

| Status sekarang | Aksi tersedia |
|---|---|
| Menunggu Kurasi | Terbitkan · Minta Revisi · Tolak |
| Terpublikasi | Kembalikan ke Antrean · Minta Revisi · Tolak |
| Draf / Perlu Revisi | Terbitkan · Kembalikan ke Antrean · Tolak |

  Catatan wajib untuk Minta Revisi, Kembalikan ke Antrean, dan Tolak. Setelah aksi, kurator kembali ke halaman tradisi yang sama (kecuali Tolak, yang kembali ke dashboard).
- **Riwayat kurasi** dan **Riwayat suntingan** (link "lihat perubahan" ke layar pembanding revisi wp-admin).

Judul tradisi di antrean dan di "Riwayat Kurasi Saya" langsung membuka halaman ini. Revisi WordPress aktif untuk post type tradisi, dan kunjungan kurator/admin serta pratinjau tidak dihitung sebagai pembaca.

### Pengaturan awal (sekali saja)

1. **Settings → General**: centang **Anyone can register**, pilih **New User Default Role: Kontributor**.
2. Halaman **Tambah Tradisi** (slug `tambah-tradisi`) berisi `[tk_form_tradisi]`.
3. Halaman **Dashboard Kurasi** (slug `dashboard-kurasi`) berisi `[tk_kurasi]`.
4. **Appearance → Menus** (Screen Options → CSS Classes):
   - "Dashboard Kurasi" diberi class `tk-menu-kurator` → hanya untuk kurator/admin.
   - Custom Link (URL `#`) dengan class `tk-menu-akun` → otomatis **Masuk**/**Keluar**.
5. Jadikan akun tim kurasi sebagai Kurator lewat **Users → Edit → Role: Kurator**.

### Isi form

Tamu: Nama, Email, Instansi (opsional), Pernyataan. Semua pengirim: nama tradisi, isi artikel, foto utama (wajib), agama, provinsi/wilayah (wajib), kategori (wajib), kata kunci (jadi Tags), lalu semua field Detail Tradisi.

Akun punya dua tombol: **Simpan Draf** (field wajib boleh kosong kecuali Nama Tradisi) dan **Kirim untuk Dikurasi**. Logika tombol draf ada di `assets/js/form.js`.

### Email

Dikirim lewat `wp_mail()`. Di LocalWP tidak terkirim sungguhan; lihat tab **Mailpit**. Di server internal pastikan SMTP berfungsi (misalnya plugin WP Mail SMTP).

### Post meta alur kurasi

| Meta | Isi |
|---|---|
| `tk_tamu_nama`, `tk_tamu_email`, `tk_tamu_instansi` | Identitas pengirim tamu |
| `_tk_log` | Riwayat kurasi (array) |
| `_tk_perlu_revisi` | 1 = dikembalikan untuk revisi |
| `_tk_catatan` | Catatan revisi / alasan tolak terakhir |
| `_tk_token` | Token link revisi tamu |
| `_tk_dikurasi_oleh` | ID kurator yang pernah memutuskan (satu baris per kurator) |

## Halaman single tradisi

Template `templates/single-tradisi.php` hanya berisi HTML. Semua data diambil lewat `tk_single_get_data()` di `includes/single.php`.

Urutan bagian: link kembali → badge kategori & status → judul → meta (penulis, asal daerah, wilayah, pembaca) → gambar utama → abstrak → kotak info (tanggal perayaan, agama, wilayah) → isi → galeri → kata kunci & sumber → tradisi terkait.

- Bagian yang datanya kosong tidak ditampilkan.
- Data dibaca dengan `get_post_meta()`, jadi halaman tetap tampil walaupun ACF nonaktif.
- Galeri memakai `tk_get_galeri_ids()`, yang sudah mendukung field Image (sekarang) maupun Gallery (ACF Pro). Template tidak perlu diubah saat upgrade.
- Tradisi terkait: 3 tradisi dengan kategori atau wilayah yang sama, memakai card yang sama dengan Beranda.

## CSS

Semua CSS ada di `assets/css/warisi.css` dan dimuat otomatis oleh plugin.

- **Additional CSS di Customizer harus dikosongkan**, supaya tidak ada aturan ganda.
- Untuk mengganti warna situs, cukup ubah variabel di bagian **1. Token warna & font**.
- Browser otomatis memuat versi terbaru setiap file disimpan (versi memakai waktu ubah file).

## Menu

Menu utama dibuat di **Appearance → Menus** dengan lokasi **Primary Menu**.

- "Jelajahi Tradisi" adalah Custom Link ke `/#jelajahi`.
- Tombol oranye "+ Tambah Tradisi" adalah item menu biasa dengan **CSS Class** `tk-menu-cta` (aktifkan kolom lewat **Screen Options → CSS Classes**).

## Cara menambah fitur

1. Buat file baru di `includes/` (atau `includes/shortcodes/` untuk shortcode).
2. Awali file dengan `if ( ! defined( 'ABSPATH' ) ) { exit; }`.
3. Daftarkan path file di array `$tk_modules` pada `tradisi-keagamaan.php`.
4. Tambahkan CSS-nya sebagai bagian bernomor baru di `warisi.css`, di atas bagian **Responsif**.

## Memperbarui field ACF

1. Ubah field di **ACF → Field Groups**.
2. **ACF → Tools → Generate PHP**, salin hasilnya.
3. Ganti isi `acf_add_local_field_group()` di `includes/acf-fields.php`.
4. Jangan ubah nilai `key` (`field_...`), karena data tersimpan bergantung pada key tersebut.

## Catatan deployment

- Font diambil dari Google Fonts. Kalau server internal tidak punya akses internet, font perlu di-host lokal (lihat komentar di `includes/assets.php`).
- Setelah memindahkan situs atau mengubah slug, buka **Settings → Permalinks** lalu klik **Save**.
- Plugin cache halaman bisa membuat view counter tidak bertambah.
- **Peta:** Leaflet diambil dari CDN jsDelivr, kecuali folder `assets/vendor/leaflet/` berisi `leaflet.js` dan `leaflet.css` (salin dari folder `dist` di https://leafletjs.com/download.html). Gambar peta (tile) tetap diambil dari OpenStreetMap, jadi pengunjung butuh akses internet. OpenStreetMap membatasi pemakaian berat; untuk trafik tinggi pertimbangkan penyedia tile lain.
