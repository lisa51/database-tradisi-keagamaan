# Changelog

Riwayat perubahan plugin WARISI. Format: versi, tanggal, lalu perubahan yang dikelompokkan sebagai **Baru**, **Diubah**, atau **Diperbaiki**.

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
