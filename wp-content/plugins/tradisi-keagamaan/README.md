# Plugin: Database Tradisi Keagamaan (WARISI)

Plugin custom untuk situs **WARISI (Warisan Religi Indonesia)**. Plugin ini mengatur struktur data tradisi, field ACF, shortcode Beranda, dan seluruh CSS tampilan.

## Struktur folder

```
tradisi-keagamaan/
├── tradisi-keagamaan.php        File utama. Hanya memuat modul di /includes.
├── README.md                    Dokumen ini.
├── assets/
│   └── css/
│       └── warisi.css           Semua CSS situs (warna, header, Beranda, single).
├── includes/
│   ├── helpers.php              Fungsi bantu: nama term, ikon SVG, URL.
│   ├── post-types.php           CPT "tradisi", taxonomy, tags, template single.
│   ├── acf-fields.php           Field ACF "Detail Tradisi".
│   ├── view-counter.php         Penghitung pembaca (meta tk_view_count).
│   ├── assets.php               Memuat font Google + warisi.css.
│   ├── menu.php                 Perbaikan menu anchor (#jelajahi).
│   ├── single.php               Data & logika untuk halaman single tradisi.
│   └── shortcodes/
│       ├── hero.php             [tk_hero]
│       ├── stats.php            [tk_stats]
│       └── koleksi.php          [tk_koleksi]
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

Susunan Beranda saat ini:

```
[tk_hero id="62"]
[tk_stats]
[tk_koleksi]
```

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
