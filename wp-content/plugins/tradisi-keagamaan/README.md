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

**Kebutuhan:** WordPress 6.x, PHP 7.4+ (disarankan 8.2), tema GeneratePress, plugin **ACF Pro** (untuk Galeri Foto & "Foto Tambahan"; dengan ACF gratis, kedua field itu tidak tampil tetapi fitur lain tetap berjalan).

**Plugin pendukung** (disarankan, tidak wajib):

| Plugin | Untuk | Catatan |
|---|---|---|
| FluentSMTP | Pengiriman email + log | Wajib di server; tanpa SMTP email kurasi & Hubungi Kami bisa tidak sampai |
| Wordfence | Firewall, scan, batas login, 2FA | Pakai satu plugin keamanan saja |
| Simple Cloudflare Turnstile | Anti-bot di login, daftar, lupa password, **dan** form WARISI | Otomatis dipakai oleh `[tk_kontak]` dan kiriman tamu `[tk_form_tradisi]` (lihat `includes/core/turnstile.php`) |
| Rank Math SEO | Meta, Open Graph, schema, sitemap | Deskripsi tradisi = `%customfield(deskripsi_singkat)%`; Kontribusi Koleksi & Dashboard Kurasi = noindex |

1. Salin folder `tradisi-keagamaan/` ke `wp-content/plugins/`, lalu aktifkan di **Plugins**.
2. **Appearance → Customize → Additional CSS** harus **kosong**. Semua CSS ada di plugin.
3. **Settings → General:** centang **Anyone can register**, pilih **New User Default Role: Kontributor**.
4. **Settings → Permalinks:** klik **Save** sekali.
5. Buat halaman dan isi dengan block **Shortcode**:

| Halaman | Slug | Isi |
|---|---|---|
| Beranda (jadikan Homepage) | bebas | `[tk_hero]` `[tk_stats]` `[tk_koleksi]` |
| Peta Koleksi | bebas | `[tk_peta]` |
| Kontribusi Koleksi | `tambah-tradisi` | `[tk_form_tradisi]` |
| Ubah Koleksi (tidak perlu di menu) | `ubah-tradisi` | `[tk_form_tradisi]` |
| Dashboard Kurasi | `dashboard-kurasi` | `[tk_kurasi]` |
| Riwayat Suntingan (tidak perlu di menu) | `riwayat-suntingan` | `[tk_riwayat_suntingan]` |
| Tentang | bebas | teks biasa |
| Hubungi Kami (sub-menu Tentang) | `hubungi-kami` | `[tk_kontak]` |
| Panduan Kontribusi | `panduan-kontribusi` | `[tk_panduan_kontribusi]` |

6. Atur menu (lihat [bagian 7](#7-menu)).
7. Jadikan akun tim kurasi sebagai Kurator: **Users → Edit → Role: Kurator**.

Peran **Kontributor**, **Kontributor Tamu**, dan **Kurator**, serta akun sistem "Kontributor Tamu", dibuat otomatis saat plugin pertama kali berjalan.

---

## 2. Struktur data

| Jenis | Nama | Keterangan |
|---|---|---|
| Post type | `tradisi` | Label **Koleksi**. URL mengikuti jenis: `/tradisi/nama/` atau `/budaya-material/nama/`; arsip `/koleksi/` (`TK_SLUG_KOLEKSI`). Awalan yang salah, jenis yang baru diganti, dan `/koleksi/nama/` dialihkan 301; `/tradisi/` & `/budaya-material/` ke Jelajahi dengan filter jenis. Revisi aktif |
| Taxonomy | `jenis` | `tradisi` (yang dilakukan: ritual, upacara, tarian) / `budaya-material` (yang berwujud: benda, bangunan/situs, naskah, makanan). Diisi lewat field ACF "Jenis"; panel taxonomy disembunyikan. Daftar di `TK_JENIS` |
| Taxonomy | `agama` | Hierarkis. 6 agama resmi + "Kepercayaan terhadap Tuhan Yang Maha Esa" (sub-term: kepercayaan lokal) |
| Taxonomy | `wilayah` | Label **Provinsi**. Hierarkis. Level teratas = 38 provinsi (dipakai stats & filter) |
| Taxonomy | `kategori-tradisi` | Label **Kategori**. Boleh lebih dari satu. Setiap kategori punya jenis (term meta `tk_jenis`, diatur di Koleksi → Kategori → edit). Dipilih lewat field ACF per jenis; panel bawaan di editor disembunyikan |
| Taxonomy | `post_tag` | Tags bawaan WP sebagai "kata kunci" |
| ACF | `asal_daerah` | Label **Kabupaten/Kota**. Text dengan saran & validasi dari daftar resmi (`includes/core/kabupaten-kota.php`) |
| ACF | `deskripsi_singkat` | Textarea: abstrak |
| ACF | `jenis_warisan` | Button group "Jenis". Tidak disimpan sebagai meta, tapi sebagai term `jenis`. Field di bawah bertanda (T) hanya tampil untuk Tradisi, (M) untuk Budaya Material |
| ACF | `kategori_tradisi` / `kategori_material` | (T)/(M) Checkbox kategori, hanya berisi kategori jenis tersebut. Menyimpan ke taxonomy `kategori-tradisi` |
| ACF | `sistem_penanggalan` | (T) Select: Masehi, Hijriah, Saka (Bali), Jawa, Imlek, Kalender adat/musim, Mengikuti peristiwa. Pilihan di `tk_sistem_penanggalan()` |
| ACF | `waktu_pelaksanaan` | (T) Text: aturan waktu tetap, mis. "12 Rabiul Awal" |
| ACF | `tanggal_perayaan` | (T) Label **Tanggal Terdekat**. Date picker, opsional |
| ACF | `bahan` | (M) Text |
| ACF | `lokasi_keberadaan` | (M) Text: tempat penyimpanan / alamat bangunan atau situs |
| ACF | `fungsi` | (M) Textarea: fungsi/kegunaan |
| ACF | `terkait` | Relationship dua arah (ACF bidirectional): "Digunakan dalam Tradisi" / "Budaya Material Terkait" di halaman detail |
| ACF | `sumber_referensi` | URL |
| ACF | `galeri_foto` | Gallery (ACF Pro), maks. 12 foto. Diatur kurator di wp-admin; dari form depan lewat "Foto Tambahan" |
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
| `label` | Database Digital Khazanah Keagamaan Indonesia | Teks kecil di atas judul |
| `judul` | Merawat Khazanah Keagamaan Nusantara | Judul besar |
| `deskripsi` | Telusuri tradisi dan benda budaya keagamaan ... | Paragraf |
| `id` | otomatis | ID koleksi unggulan. Kosong = pembaca terbanyak |

### `[tk_stats]`: empat kotak angka

Tanpa atribut. Menghitung tradisi terbit, budaya material terbit (term `jenis`), provinsi (term `wilayah` level teratas), dan kabupaten/kota (nilai unik `asal_daerah`).

### `[tk_koleksi]`: panel Jelajahi + grid card

```
[tk_koleksi]
[tk_koleksi per_halaman="12"]
```

- Pencarian nama + dropdown **Jenis**, **Provinsi**, dan **Kategori** (bisa dikombinasikan). Pilihan Kategori dikelompokkan per jenis, dan menyempit bila Jenis dipilih. Card menampilkan badge jenis di kanan atas.
- Parameter URL: `?cari=`, `?tipe=` (slug jenis), `?provinsi=`, `?kategori=`, `?hal=`.
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

### `[tk_kontak]`: form Hubungi Kami

```
[tk_kontak]
[tk_kontak judul="Hubungi Kami" deskripsi="..."]
```

- Isian: Nama, Email, Perihal (Pertanyaan umum / Koreksi data koleksi / Bantuan kontribusi / Kerja sama / Lainnya), Pesan. Nama & email terisi otomatis bila pengunjung sudah login.
- Pesan dikirim ke email admin (atau `TK_KONTAK_EMAIL`). Tekan **Balas** di email untuk menjawab langsung ke pengirim.
- Pengirim menerima **email konfirmasi** singkat (hanya perihal, tanpa isi pesan agar form tidak bisa dipakai mengirim spam).
- **Lampiran** opsional: satu file JPG, PNG, PDF, atau DOCX, maks. 5 MB. Isi file dicek (bukan hanya nama), lalu file dikirim sebagai lampiran email dan langsung dihapus dari server, tidak masuk Media Library.
- Anti-spam: honeypot + maks. 3 pesan/jam/IP (`TK_KONTAK_BATAS_PER_JAM`).
- Pesan tidak disimpan di database, hanya dikirim lewat email. Pastikan SMTP berfungsi di server dan batas ukuran email penyedia SMTP ≥ ukuran lampiran.

### `[tk_panduan_kontribusi]`: halaman Panduan Kontribusi

Tanpa atribut. Isi: jenis koleksi, cara berkontribusi (tamu/akun), langkah mengirim, tabel isian (wajib/dianjurkan/opsional), ketentuan foto, proses kurasi, mengubah koleksi terbit, dan etika. Angka aturan (minimal kata, ukuran & jumlah foto) dibaca dari `config.php`, jadi selalu sama dengan form. Form kiriman baru menautkan ke halaman ini.

### Footer

Dipasang otomatis lewat hook GeneratePress (`includes/core/footer.php`), tanpa widget: nama & slogan, lembaga pengelola, dua kolom tautan, dan baris hak cipta (menggantikan "Built with GeneratePress"). Isi diatur di `config.php`: `TK_LEMBAGA`, `TK_LOGO_LEMBAGA`, `TK_SLOGAN`, `TK_FOOTER_TAUTAN` (halaman yang belum dibuat dilewati). Logo BRIN (`assets/img/logo-brin.png`) adalah versi untuk latar gelap: latar transparan, tulisan putih.

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

Nama tradisi, isi artikel (≥150 kata disarankan), foto utama (wajib → featured image), **Foto Tambahan** (opsional → Galeri Foto), agama, provinsi (wajib), kategori (wajib), kata kunci (→ Tags), lalu field Detail Koleksi lainnya, termasuk **Kredit Foto** (pembuat & lisensi tiap foto; tampil di halaman koleksi). Foto wajib milik sendiri atau bebas digunakan, lihat halaman Panduan Kontribusi.

**Foto Tambahan:** klik **Tambah Foto** untuk setiap foto (JPG/PNG/WebP, maks. 5 MB per foto, total galeri maks. 12). Setelah form disimpan, foto dipindah ke Galeri Foto. Saat melanjutkan draf/revisi, foto baru ditambahkan di belakang foto yang sudah ada. Menghapus atau mengurutkan foto galeri dilakukan kurator di wp-admin.

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
| Kiriman baru / kiriman ulang / usulan perubahan | ke kurator (atau admin bila belum ada kurator) |
| Kiriman baru / kiriman ulang / usulan perubahan | konfirmasi ke pengirim |
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

Urutan: Panel Kurator (khusus kurator) → link kembali → badge jenis, kategori & status → judul → meta (penulis, kabupaten/kota, provinsi, pembaca) → gambar utama → abstrak → info (Tradisi: waktu pelaksanaan, tanggal terdekat, agama; Budaya Material: bahan, lokasi, agama, lalu fungsi) → isi → galeri → peta lokasi → kata kunci & sumber → tautan "Terkait dengan" ("Digunakan dalam Tradisi" / "Budaya Material Terkait") → Lihat Juga.

**Galeri Foto:** klik foto untuk membuka lightbox. Navigasi dengan tombol ‹ ›, panah keyboard, atau geser di HP; tutup dengan ×, Esc, atau klik latar. Keterangan foto diambil dari **Caption** di Media Library.

Bagian yang datanya kosong tidak ditampilkan. Halaman tetap tampil walaupun ACF nonaktif.

---

## 7. Menu

**Appearance → Menus**, lokasi **Primary Menu**. Aktifkan kolom lewat **Screen Options → CSS Classes**.

| Item | Cara | CSS Class |
|---|---|---|
| Jelajahi Khazanah | Custom Link `/#jelajahi` | – |
| + Berkontribusi (tombol oranye) | Halaman Kontribusi Koleksi | `tk-menu-cta` |
| Dashboard Kurasi (hanya kurator/admin) | Halaman Dashboard Kurasi | `tk-menu-kurator` |
| Masuk / Keluar (otomatis) | Custom Link URL `#` | `tk-menu-akun` |
| Hubungi Kami (sub-menu) | Halaman Hubungi Kami, geser ke kanan di bawah **Tentang** | – |

**Ikon menu:** tambahkan CSS Class `tk-ikon-<nama>` pada item menu (boleh digabung dengan class lain, dipisah spasi). Item Masuk/Keluar mendapat ikon otomatis.

| Item | CSS Class ikon |
|---|---|
| Beranda | `tk-ikon-rumah` |
| Jelajahi Khazanah | `tk-ikon-cari` |
| Peta Koleksi | `tk-ikon-peta` |
| Dashboard Kurasi | `tk-menu-kurator tk-ikon-kurasi` |
| Tentang | `tk-ikon-info` |
| Hubungi Kami | `tk-ikon-surat` |
| Berkontribusi | `tk-menu-cta tk-ikon-tambah` (tulis teksnya tanpa "+") |

Ikon lain yang tersedia: `pin`, `gedung`, `buku`, `user`, `mata`, `link`, `masuk`, `keluar`.

---

## 8. Pengaturan yang bisa diubah

Semua ada di **`includes/config.php`**:

| Konstanta | Default | Fungsi |
|---|---|---|
| `TK_SLUG_TAMBAH` | `tambah-tradisi` | Slug halaman form |
| `TK_SLUG_KURASI` | `dashboard-kurasi` | Slug halaman dashboard |
| `TK_SLUG_PANDUAN` | `panduan-kontribusi` | Slug halaman Panduan Kontribusi |
| `TK_LEMBAGA` | Pusat Riset … BRIN | Lembaga pengelola di footer (unit terkecil → induk) |
| `TK_LOGO_LEMBAGA` | `assets/img/logo-brin.png` | Logo lembaga di footer (kosong = disembunyikan); teks alt di `TK_LOGO_LEMBAGA_ALT` |
| `TK_SLOGAN` | Warisan Religi Indonesia | Slogan di footer |
| `TK_FOOTER_TAUTAN` | Jelajahi, Berkontribusi | Kolom & tautan footer (slug halaman => label) |
| `TK_TAMU_BATAS_PER_JAM` | `5` | Batas kiriman tamu per jam per IP |
| `TK_MIN_KATA_ISI` | `150` | Minimum kata untuk checklist "Isi" |
| `TK_GALERI_MAKS` | `12` | Jumlah foto maksimum di Galeri Foto |
| `TK_FOTO_MAKS_MB` | `5` | Ukuran maksimum per foto (foto utama & galeri) |
| `TK_KURASI_HARI_PERINGATAN` | `7` | Batas hari sebelum antrean ditandai merah |
| `TK_RIWAYAT_PER_HALAMAN` | `15` | Baris per halaman "Riwayat Kurasi Saya" |
| `TK_KONTAK_EMAIL` | kosong (= email admin) | Penerima pesan Hubungi Kami, sekaligus alamat Reply-To semua email |
| `TK_KONTAK_BATAS_PER_JAM` | `3` | Batas pesan Hubungi Kami per jam per IP |
| `TK_KONTAK_LAMPIRAN_MAKS_MB` | `5` | Ukuran maksimum lampiran Hubungi Kami |
| `TK_KONTAK_LAMPIRAN_TIPE` | jpg, png, pdf, docx | Format lampiran yang diizinkan |
| `TK_CLOUDFLARE_IP` | daftar IP Cloudflare | Proxy yang dipercaya untuk membaca IP asli pengunjung (batas per IP) |
| `TK_ROLES_VERSION` | `2` | Naikkan bila hak akses peran diubah |
| `TK_FONTS_URL` | Google Fonts | Sumber font |
| `TK_LEAFLET_VERSI` | `1.9.4` | Versi Leaflet dari CDN |

Warna situs diubah di bagian **Token** pada `assets/css/base.css`.

---

## 9. Deployment ke server

- **Font:** diambil dari Google Fonts. Bila server/pengunjung tanpa internet, host font secara lokal lalu ubah `TK_FONTS_URL`.
- **Peta:** Leaflet dari CDN, kecuali `assets/vendor/leaflet/` berisi `leaflet.js` & `leaflet.css` (salin folder `dist` dari https://leafletjs.com/download.html). Gambar peta (tile) tetap dari OpenStreetMap: pengunjung butuh internet, dan OpenStreetMap membatasi pemakaian berat.
- **Email:** atur FluentSMTP (**Settings → FluentSMTP**) dengan layanan pengirim asli (mis. Brevo atau Google Workspace), lalu kirim email uji. Di LocalWP, FluentSMTP diarahkan ke Mailpit (`127.0.0.1:10001`); email bisa dilihat di tab **Mailpit**.
- **Turnstile:** di lokal memakai **kunci uji** Cloudflare (selalu lolos). Di server, buat kunci asli di dash.cloudflare.com → Turnstile, lalu isi di **Settings → Turnstile**.
- **Rank Math:** pengaturan tidak ikut git. Di server atur ulang: deskripsi tradisi `%customfield(deskripsi_singkat)%`, schema Article, noindex untuk Kontribusi Koleksi & Dashboard Kurasi, sitemap Post & Category dimatikan.
- **Wordfence:** selesaikan onboarding (email notifikasi), aktifkan 2FA untuk admin & kurator, lalu **Optimize Firewall**. Folder `wp-content/wflogs/` tidak di-commit.
- **Permalink:** setelah pindah server atau mengubah slug, buka **Settings → Permalinks → Save**.
- **Cache halaman:** plugin cache bisa membuat penghitung pembaca tidak bertambah, dan halaman form/dashboard **harus dikecualikan** dari cache.
- **Additional CSS** di Customizer harus tetap kosong.
