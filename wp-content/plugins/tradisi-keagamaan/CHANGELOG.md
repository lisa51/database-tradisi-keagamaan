# Changelog

Riwayat perubahan plugin WARISI. Format: versi, tanggal, lalu perubahan yang dikelompokkan sebagai **Baru**, **Diubah**, atau **Diperbaiki**.

## 2.5.0 (29 September 2026): Konfirmasi keluar

**Baru**
- Jendela konfirmasi sebelum logout (`assets/js/akun.js`, gaya di `assets/css/dialog.css`). Berlaku untuk menu "Keluar" dan "Log Out" di admin bar, baik di halaman depan maupun di wp-admin. Fokus awal di tombol "Batal"; Esc atau klik latar menutup. Di halaman depan dimuat hanya saat login; tanpa JavaScript, logout berjalan langsung.

## 2.4.1 (29 September 2026): Perbaikan paging koleksi

**Diperbaiki**
- Nomor halaman `[tk_koleksi]` di Beranda (tombol 2 dan "Berikutnya") mengarah ke halaman detail tradisi terakhir di grid, bukan ke Beranda. URL halaman kini diambil sebelum loop.

**Diubah**
- Link nomor halaman membawa `#koleksi` (id baru di grid card), sehingga halaman berikutnya langsung tergulir ke grid, bukan ke atas Beranda.

## 2.4.0 (29 September 2026): Galeri Foto (ACF Pro)

**Baru**
- `galeri_foto` menjadi field **Gallery** ACF Pro (maks. 12 foto, JPG/PNG/WebP, 5 MB per foto).
- Form depan: **Foto Tambahan** (Repeater, uploader basic) untuk tamu & kontributor; foto dipindah ke Galeri Foto setelah simpan. Saat revisi, sisa kuota ditampilkan.
- Lightbox Galeri Foto di halaman detail (`assets/js/galeri.js`): tombol, keyboard, geser di HP, keterangan dari Caption, jumlah foto di judul.
- Konstanta `TK_GALERI_MAKS` dan `TK_FOTO_MAKS_MB`.

**Diubah**
- Foto galeri tidak lagi membuka tab baru; tanpa JavaScript, link tetap membuka foto besar.
- Data lama `galeri_foto` (satu ID) dimigrasi ke format Gallery.

## 2.3.0 (29 September 2026): Anti-bot Turnstile

**Baru**
- `includes/core/turnstile.php`: Cloudflare Turnstile (lewat plugin Simple Cloudflare Turnstile) di `[tk_kontak]` dan kiriman baru tamu di `[tk_form_tradisi]`. Tanpa plugin atau kunci, form tetap berjalan seperti sebelumnya.
- README: daftar plugin pendukung (FluentSMTP, Wordfence, Simple Cloudflare Turnstile, Rank Math) dan langkah deployment-nya.

## 2.2.0 (29 September 2026): Lampiran & ikon menu

**Baru**
- Lampiran opsional di `[tk_kontak]` (JPG, PNG, PDF, DOCX; maks. 5 MB). Isi file dicek, dikirim sebagai lampiran email, lalu dihapus. Ukuran juga dicek di browser (`assets/js/kontak.js`).
- Konstanta `TK_KONTAK_LAMPIRAN_MAKS_MB` dan `TK_KONTAK_LAMPIRAN_TIPE`.
- Ikon di menu lewat CSS Class `tk-ikon-<nama>`; Masuk/Keluar otomatis berikon. Ikon baru di `tk_icon()`: rumah, cari, peta, kurasi, info, surat, tambah, masuk, keluar.

**Diubah**
- `tk_kirim_email()` menerima daftar lampiran.

## 2.1.0 (29 September 2026): Hubungi Kami

**Baru**
- Shortcode `[tk_kontak]`: form Hubungi Kami (nama, email, perihal, pesan) yang dikirim ke email admin dengan Reply-To ke pengirim. Dilengkapi honeypot dan batas 3 pesan/jam/IP.
- Konstanta `TK_KONTAK_EMAIL` dan `TK_KONTAK_BATAS_PER_JAM` di `config.php`.
- Gaya sub-menu dropdown di header (desktop).

**Diubah**
- `tk_kirim_email()` menerima header tambahan dan mengembalikan hasil `wp_mail()`.

## 2.0.0 (28 September 2026): Refactoring menyeluruh

Perilaku dan tampilan **tidak berubah**. Semua fungsi, shortcode, nama class CSS, hook (32 hook, prioritas identik), dan data tetap sama.

**Diubah**
- Kode dikelompokkan per domain: `includes/core`, `akun`, `kontribusi`, `kurasi`, `shortcodes`.
- Semua pengaturan dipindah ke `includes/config.php` (slug halaman, batas kiriman, key ACF, versi peran, URL font, versi Leaflet).
- Modul besar dipecah: form (field / proses / tampilan), kurasi (data / riwayat / token / email / aksi / komponen / admin / panel), peran (peran / akses / login).
- CSS dipecah per fitur (`base`, `beranda`, `koleksi`, `single`, `peta`, `kontribusi`, `kurasi`, `login`).
- Semua warna memakai token; tidak ada kode hex di luar `base.css`.
- Aturan tombol `<a>` dan `<button>` disatukan.
- Gaya halaman login dipindah dari PHP ke `login.css`.
- Dokumentasi dipisah: README (pengguna), PANDUAN-PENGEMBANG, dan CHANGELOG.

**Diperbaiki**
- Tombol daftar di panel peta bisa tertimpa warna hover/fokus tombol GeneratePress.
- Kode mati dihapus: `.tk-stats--dua` dan `.tk-ajakan`.
- Pembacaan meta `_tk_dikurasi_oleh` diberi pengaman `(array)` agar tidak error bila datanya tidak lazim.
- Contoh kunci filter di dokumentasi (`agama` → `pilih_agama`) untuk mencegah bentrok dengan query var taxonomy.

## 1.6.0: Panel Kurator
- **Baru:** Panel Kurator di halaman tradisi (pengirim, kurator, penyunting, checklist, ubah status, riwayat kurasi & suntingan).
- **Baru:** aksi **Kembalikan ke Antrean**; aksi kurasi kini berlaku sesuai status (menunggu / terbit / draf).
- **Baru:** revisi WordPress & kolom Author untuk post type tradisi.
- **Diubah:** kunjungan kurator/admin dan pratinjau tidak dihitung sebagai pembaca.

## 1.5.x: Kontributor Tamu & alur kurasi
- **Baru:** kirim tanpa login (nama, email, instansi, pernyataan), dengan batas 5 kiriman/jam/IP.
- **Baru:** Minta Revisi & Tolak dengan catatan wajib; link revisi rahasia untuk tamu.
- **Baru:** checklist kelengkapan, riwayat kurasi, email ke kurator & pengirim.
- **Baru:** "Riwayat Kurasi Saya" dengan tab saringan dan tombol Pulihkan.
- **Baru:** filter **Kategori** di panel Jelajahi (dropdown filter bisa ditambah lewat satu baris).

## 1.4.x: Kontribusi & kurasi dasar
- **Baru:** peran Kontributor & Kurator; form `[tk_form_tradisi]` dengan Simpan Draf & lanjutkan draf; Dashboard `[tk_kurasi]`.
- **Baru:** menu khusus kurator dan tombol Masuk/Keluar otomatis; halaman login bergaya WARISI.

## 1.3.x: Peta
- **Baru:** `[tk_peta]` (Leaflet + OpenStreetMap), field latitude/longitude, peta kecil di halaman detail, pin gabungan untuk lokasi yang sama.

## 1.2.0: Halaman detail
- **Baru:** tata ulang halaman detail (badge, meta berikon, kotak info, tradisi terkait); galeri siap untuk ACF Pro Gallery.
- **Diperbaiki:** urutan muat CSS setelah GeneratePress; bentrok class `single-tradisi` dengan `<body>`.

## 1.1.0: Modular
- Plugin dipecah menjadi modul; CSS dipindah dari Additional CSS ke file plugin.
- **Baru:** `[tk_hero]`, `[tk_stats]` berikon, `[tk_koleksi]` (pencarian + filter provinsi).

## 1.0.0: Awal
- Post type `tradisi`, taxonomy agama / wilayah / kategori-tradisi, field ACF, template single, penghitung pembaca.
