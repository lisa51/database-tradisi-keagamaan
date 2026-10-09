# WARISI — Warisan Religi Indonesia

Sistem informasi berbasis basis data untuk menyimpan dan mengelola warisan keagamaan di Indonesia, baik **tradisi keagamaan** (ritual, upacara, tarian) maupun **budaya material keagamaan** (benda, bangunan atau situs, naskah, makanan). Dikembangkan oleh Pusat Riset Khazanah Keagamaan dan Peradaban (PR-KKP), BRIN.

Data berasal dari para kontributor: hasil riset sivitas PR-KKP, sivitas BRIN, dan masyarakat umum. Validitas setiap data diuji oleh kurator sebelum diterbitkan.

**Prototipe:** https://warisi-prototype.brin.go.id

---

## Fitur

- **Jelajahi Khazanah**: pencarian dan filter menurut jenis, agama dan kepercayaan, provinsi, dan kategori.
- **Peta interaktif**: sebaran koleksi di 38 provinsi (Leaflet + OpenStreetMap).
- **Statistik koleksi** di Beranda.
- **Halaman detail koleksi**: galeri foto, peta lokasi, koleksi terkait, penghitung pembaca, dan tombol Bagikan.
- **Kontribusi**: lewat akun (dengan draf dan "Kiriman Saya") atau sebagai tamu tanpa login (revisi lewat tautan rahasia di email).
- **Kurasi berjenjang**: Dashboard Kurasi, checklist kelengkapan, Terbitkan / Minta Revisi / Tolak, usulan perubahan, dan riwayat suntingan.
- **Notifikasi email** otomatis untuk kurator dan pengirim.
- **Halaman Panduan Kontribusi** dan form **Hubungi Kami** dengan lampiran.
- **Keamanan**: peran pengguna (Kontributor, Kontributor Tamu, Kurator), nonce, sanitasi masukan, Cloudflare Turnstile, dan batas kiriman per IP.

## Teknologi

| Komponen | Versi |
|---|---|
| WordPress | 7.1 |
| PHP | 7.4+ (disarankan 8.2) |
| Tema | GeneratePress 3.6.1 + GenerateBlocks 2.4.1 |
| Advanced Custom Fields PRO | 6.8.x (wajib, berlisensi) |
| Leaflet | 1.9.4 |
| Font | DM Sans & DM Serif Display (Google Fonts) |

Plugin pendukung (tidak di-commit, pasang dari wordpress.org): FluentSMTP, Wordfence, Simple Cloudflare Turnstile, Rank Math SEO.

## Struktur repositori

Repositori ini berisi folder situs WordPress **tanpa** core WordPress, uploads, dan plugin pihak ketiga (lihat [.gitignore](.gitignore)).

```
wp-content/
├── plugins/
│   ├── tradisi-keagamaan/   ← plugin utama WARISI (kode buatan sendiri)
│   └── generateblocks/
└── themes/
    └── generatepress/
```

Semua logika dan tampilan WARISI ada di plugin [`tradisi-keagamaan`](wp-content/plugins/tradisi-keagamaan/):

```
tradisi-keagamaan/
├── tradisi-keagamaan.php   Loader: memuat semua modul
├── includes/
│   ├── config.php          Semua pengaturan yang bisa diubah
│   ├── core/               Post type, taxonomy, field ACF, aset, menu, footer
│   ├── akun/               Peran pengguna, pembatasan akses, halaman login
│   ├── kontribusi/         Field & pemrosesan form kontribusi
│   ├── kurasi/             Alur kurasi, riwayat, email, Panel Kurator
│   ├── kontak/             Pemrosesan form Hubungi Kami
│   ├── shortcodes/         Tampilan halaman depan
│   └── data/               Daftar kabupaten/kota
├── templates/              Template halaman detail koleksi
└── assets/                 CSS, JavaScript, gambar
```

## Instalasi lokal

1. Pasang WordPress (mis. dengan [LocalWP](https://localwp.com/)), lalu clone repositori ini ke folder `public` situs.
2. Pasang ACF PRO dari akun advancedcustomfields.com, lalu plugin pendukung dari wordpress.org.
3. Aktifkan tema **GeneratePress** dan plugin **Database Tradisi Keagamaan (WARISI)**.
4. **Settings → General**: centang *Anyone can register*, pilih peran default **Kontributor**.
5. **Settings → Permalinks**: klik **Save** sekali.
6. Buat halaman berisi shortcode berikut:

| Halaman | Slug | Shortcode |
|---|---|---|
| Beranda | bebas | `[tk_hero]` `[tk_stats]` `[tk_koleksi]` |
| Peta Koleksi | bebas | `[tk_peta]` |
| Kontribusi Koleksi | `tambah-tradisi` | `[tk_form_tradisi]` |
| Ubah Koleksi | `ubah-tradisi` | `[tk_form_tradisi]` |
| Dashboard Kurasi | `dashboard-kurasi` | `[tk_kurasi]` |
| Riwayat Suntingan | `riwayat-suntingan` | `[tk_riwayat_suntingan]` |
| Panduan Kontribusi | `panduan-kontribusi` | `[tk_panduan_kontribusi]` |
| Hubungi Kami | `hubungi-kami` | `[tk_kontak]` |

Langkah lengkap, termasuk pengaturan menu, ada di README plugin.

## Dokumentasi

| Dokumen | Isi |
|---|---|
| [README plugin](wp-content/plugins/tradisi-keagamaan/README.md) | Fitur, struktur data, shortcode, kontribusi, kurasi, pengaturan, deployment |
| [Panduan Pengembang](wp-content/plugins/tradisi-keagamaan/PANDUAN-PENGEMBANG.md) | Struktur kode, konvensi, resep perubahan, keamanan, pengujian manual |
| [Changelog](wp-content/plugins/tradisi-keagamaan/CHANGELOG.md) | Riwayat versi (saat ini 2.14.0) |

## Metode pengembangan

WARISI dikembangkan dengan metode **Agile** dan kerangka kerja **Scrum**. Pengembangan berjalan secara berulang dan bertahap, dengan fokus pada kolaborasi antar-*stakeholder* dan adaptasi terhadap perubahan kebutuhan, sehingga konten dan fungsionalitasnya terus berkembang.

---

Dikelola oleh Pusat Riset Khazanah Keagamaan dan Peradaban · OR Arkeologi, Bahasa, dan Sastra · BRIN.
