# Changelog

Riwayat perubahan plugin WARISI. Format: versi, tanggal, lalu perubahan yang dikelompokkan sebagai **Baru**, **Diubah**, atau **Diperbaiki**.

## 2.13.0 (2 Oktober 2026): Panduan Kontribusi & footer lembaga

**Baru**
- Halaman **Panduan Kontribusi** (`[tk_panduan_kontribusi]`, `includes/shortcodes/panduan.php`, `assets/css/panduan.css`): jenis koleksi, tamu vs akun, langkah mengirim, tabel isian, ketentuan foto, proses kurasi, usulan perubahan, etika. Angka aturan dibaca dari `config.php`. Slug `TK_SLUG_PANDUAN`, URL `tk_url_panduan()`.
- Form kiriman baru menautkan ke Panduan Kontribusi ("Baru pertama kali? …").
- Panduan: bagian **Hak cipta & lisensi foto** (boleh/jangan diunggah, contoh kredit, penurunan foto atas keberatan pemilik hak cipta).
- Field **Kredit Foto** (`kredit_foto`, textarea, grup Detail Koleksi): pembuat & lisensi tiap foto, tampil di bawah halaman koleksi (ikon `kamera`) dan masuk checklist kelengkapan kurator.
- Pernyataan tamu kini juga menyatakan foto milik pengirim atau bebas digunakan; petunjuk Foto Utama merujuk ke Kredit Foto.
- **Footer** (`includes/core/footer.php`, bagian 6 `base.css`): nama & slogan, lembaga pengelola (Pusat Riset Khazanah Keagamaan dan Peradaban · OR Arbastra · BRIN), kolom tautan Jelajahi & Berkontribusi, baris hak cipta menggantikan "Built with GeneratePress". Logo BRIN versi latar gelap (`assets/img/logo-brin.png`: latar transparan, tulisan putih, lambang merah tetap). Diatur lewat `TK_LEMBAGA`, `TK_LOGO_LEMBAGA`, `TK_SLOGAN`, `TK_FOOTER_TAUTAN`.

## 2.12.0 (2 Oktober 2026): Istilah Khazanah · Koleksi · Kontribusi

**Diubah**
- Teks situs memakai tiga istilah: **khazanah** untuk keseluruhan isi (judul hero "Merawat Khazanah Keagamaan Nusantara", "Jelajahi Khazanah", judul daftar), **koleksi** untuk tiap entri & hitungan (peta, dashboard, pesan kurasi, konfirmasi), dan **kontribusi** untuk aksi pengguna (tombol "+ Berkontribusi", ucapan terima kasih, email). "Tradisi" & "Budaya Material" tetap sebagai label jenis.
- Email ke pengirim: subjek "Terima kasih atas kontribusi Anda" dan "Kontribusi Anda sudah terbit"; isi tidak lagi menyebut "tradisi" untuk budaya material.
- Kartu unggulan di hero menampilkan jenis + provinsi (mis. "Budaya Material Nusa Tenggara Timur"), sebelumnya selalu "Tradisi ...".
- Grup field ACF "Detail Tradisi" → "Detail Koleksi"; pilihan Hubungi Kami "Koreksi data koleksi".
- Slug halaman (`tambah-tradisi`, `ubah-tradisi`) tidak berubah.

## 2.11.0 (1 Oktober 2026): Email konfirmasi untuk pengirim

**Baru**
- Pengirim menerima email konfirmasi setiap kali kirimannya masuk antrean kurasi: kiriman baru (termasuk draf yang baru dikirim), kiriman ulang setelah revisi (`diterima_ulang`), dan usulan perubahan beserta revisinya. Sebelumnya hanya kiriman baru langsung dari form yang dikonfirmasi.
- Form Hubungi Kami mengirim konfirmasi singkat ke pengirim setelah pesan sampai ke admin (`tk_kontak_kirim_konfirmasi()`). Hanya menyebut perihal; isi pesan, nama, dan lampiran sengaja tidak disertakan agar form tidak bisa dipakai mengirim spam ke alamat orang lain.
- Semua email plugin kini ber-Reply-To ke kotak masuk tim (`tk_email_tim()`: `TK_KONTAK_EMAIL` atau email admin), sehingga balasan pengguna tetap sampai walau alamat pengirim di FluentSMTP noreply@. Email "pesan baru" Hubungi Kami tetap ber-Reply-To ke pengunjung.

## 2.10.2 (1 Oktober 2026): Batas per IP di belakang Cloudflare

**Diperbaiki**
- Batas kiriman tamu & pesan Hubungi Kami per IP kini memakai IP asli pengunjung bila situs di belakang proxy Cloudflare (header `CF-Connecting-IP`, hanya dipercaya dari rentang IP Cloudflare `TK_CLOUDFLARE_IP` di config.php). Sebelumnya semua pengunjung terbaca sebagai IP Cloudflare dan berbagi satu batas.
- Penghitung kedua form disatukan di `tk_batas_per_ip()` (`includes/core/helpers.php`); `tk_kontak_cek_batas()` dihapus.

## 2.10.1 (30 September 2026): Bersih-bersih kode

**Diubah**
- Lebih hemat query: jenis dibaca dari cache term (`tk_get_jenis()`), tautan "Terkait dengan" diambil dalam satu query, cache thumbnail/term disiapkan untuk Dashboard Kurasi & peta, hitungan kartu tidak diulang, daftar kategori per jenis di-memo, term jenis tidak dicek setiap request, pembersihan tautan usulan tanpa memindai tabel meta.
- Terbitkan memakai `wp_update_post()`: slug dibuat otomatis bila kosong dan tanggal terbit = waktu diterbitkan.
- Pengalihan `/koleksi/nama/`, `/tradisi/`, `/budaya-material/` lewat aturan rewrite (bukan membaca path saat 404).
- Pemeriksaan aksi kurasi disatukan (`tk_kurasi_cek()`); tombol & konfirmasi usulan dari `tk_panel_tombol_aksi()`; ringkasan usulan `tk_kurasi_ringkasan_usulan()`; paginasi `tk_kurasi_render_halaman()`; URL halaman `tk_url_halaman()`; izin usulan `tk_form_boleh_usul()`.
- Dihapus: `tk_get_jenis_label()`, fungsi pembungkus kategori per jenis, global & opsi yang tidak terpakai, CSS ganda (baris dashboard memakai `.tk-riwayat-daftar`).

## 2.10.0 (30 September 2026): Filter & aksi massal Dashboard Kurasi

**Baru**
- Kartu Menunggu Kurasi / Menunggu Revisi / Terpublikasi di Dashboard Kurasi menjadi filter (`?tampil=`), dengan pilihan Jenis Semua / Tradisi / Budaya Material (`?tipe=`); angka kartu mengikuti jenis. Menunggu Revisi & Terpublikasi tampil sebagai baris ringkas (jenis, kategori, provinsi, kelengkapan, Lihat, Ubah), 20 per halaman (`TK_KURASI_PER_HALAMAN`, `?khal=`).
- **Aksi massal**: centang koleksi (atau Pilih semua) → Terbitkan / Kembalikan ke Antrean / Minta Revisi / Tolak, dengan satu catatan untuk semua (`tk_kurasi_massal_handle()`, `assets/js/kurasi.js`). Koleksi yang aksinya tidak berlaku untuk statusnya dilewati dan dilaporkan.
- Tombol **Ubah** di setiap koleksi pada Dashboard Kurasi.
- **Pencarian** di Dashboard Kurasi (`?kcari=`, judul & isi), di dalam status & jenis yang dipilih; angka kartu, tab jenis, paging, dan aksi massal ikut hasil pencarian.

**Diubah**
- Panel Kurator dipindah ke bawah isi koleksi (sebelum tautan & Lihat Juga).
- Aksi kurasi disatukan di `tk_kurasi_jalankan()` (dipakai tombol per koleksi & aksi massal).

**Diperbaiki**
- Menerbitkan draf tanpa slug (mis. hasil impor) kini membuat slug dari judul; sebelumnya URL-nya rusak.

## 2.9.0 (30 September 2026): Ubah Tradisi & Riwayat Suntingan

**Baru**
- Halaman **Ubah Tradisi** (`ubah-tradisi`, `[tk_form_tradisi]`, `?edit=ID`), form yang sama dengan Tambah Tradisi. Link lama `tambah-tradisi?edit=` dialihkan ke sini.
  - Kontributor: mengubah draf / kiriman yang menunggu kurasi miliknya (tombol "Simpan Perubahan", status tetap), dan mengusulkan perubahan untuk koleksi terbit miliknya (tombol "Usulkan perubahan" di halaman detail).
  - Kurator & admin: menyunting koleksi apa pun dari form ("Ubah di form" di Panel Kurator, "Ubah" di Dashboard Kurasi); perubahan langsung berlaku, status tidak berubah.
- **Usulan perubahan** (`includes/kontribusi/usulan.php`): perubahan kontributor atas koleksi terbit disimpan sebagai salinan; versi terbit tetap tampil sampai kurator menekan **Setujui & Terapkan**, lalu isi usulan diterapkan dan salinan dihapus. Dashboard & Panel Kurator menandai usulan dan menampilkan bagian yang diubah; email & riwayat menyesuaikan. Usulan tidak bisa terbit sendiri.
- Halaman **Riwayat Suntingan** (`riwayat-suntingan`, `[tk_riwayat_suntingan]`, `?id=ID`), khusus kurator: daftar revisi dengan perbandingan sebelum/sesudah per bagian (judul, ringkasan, isi, field Detail). Panel Kurator menautkan ke sini, bukan ke wp-admin.
- Form mengisi Foto Utama & Kata Kunci dari data tersimpan saat mengubah; Kata Kunci yang dikosongkan menghapus Tags.

**Diperbaiki**
- Dashboard Kurasi: lama menunggu salah ("20000+ hari") untuk kiriman yang sebelumnya draf.

## 2.8.0 (30 September 2026): Kategori per jenis & nama "Koleksi"

**Baru**
- Kategori per jenis. Setiap kategori punya jenis (field "Untuk Jenis" di halaman edit kategori, term meta `tk_jenis`). Di form dan editor, field Kategori tampil setelah Jenis dan hanya berisi kategori jenis itu (`kategori_tradisi` / `kategori_material`), divalidasi di server.
- Kategori baru: Penyambutan Tamu, Pernikahan, Tari & Seni Ritual, Tradisi Sosial (Tradisi); Benda & Kerajinan, Naskah & Kitab, Bangunan & Situs, Kuliner Ritual (Budaya Material).
- Dropdown Kategori di Jelajahi dikelompokkan per jenis dan menyempit sesuai filter Jenis.

**Diubah**
- Post type tampil sebagai **Koleksi** di wp-admin.
- URL halaman detail mengikuti jenis: `/tradisi/nama/` atau `/budaya-material/nama/` (aturan per jenis di `tk_rewrite_jenis()`, permalink di `tk_permalink_jenis()`). Awalan yang tidak sesuai jenis (mis. setelah jenis diganti) dan `/koleksi/nama/` dialihkan 301; `/tradisi/` dan `/budaya-material/` diarahkan ke Jelajahi dengan filter jenis; arsip semua koleksi di `/koleksi/`. Aturan URL diperbarui otomatis.
- Panel taxonomy "Kategori Tradisi" bawaan di editor disembunyikan (diganti field per jenis); label taxonomy menjadi "Kategori".

## 2.7.0 (30 September 2026): Budaya Material Keagamaan

**Baru**
- Jenis konten **Tradisi** (yang dilakukan) dan **Budaya Material** (yang berwujud: benda, bangunan/situs, naskah, makanan), dalam post type `tradisi` yang sama. Disimpan di taxonomy baru `jenis` (`includes/core/jenis.php`, daftar di `TK_JENIS`), diisi lewat field ACF "Jenis".
- Field khusus Budaya Material: **Bahan**, **Lokasi Penyimpanan/Keberadaan**, **Fungsi/Kegunaan**. Field waktu (Sistem Penanggalan, Waktu Pelaksanaan, Tanggal Terdekat) hanya tampil untuk Tradisi.
- Field **Terkait dengan** (relationship dua arah): halaman detail menampilkan "Digunakan dalam Tradisi" / "Budaya Material Terkait".
- Filter **Jenis** di panel Jelajahi (`?tipe=`), badge jenis di card dan halaman detail, kotak **Budaya Material** di `[tk_stats]`, ikon `benda`.

**Diubah**
- Card: badge "Terpublikasi" diganti badge jenis.
- Panel Jelajahi: "Jelajahi Warisan Religi", "Koleksi Warisan Religi", "Menampilkan N koleksi".
- Halaman detail: "Tradisi Terkait" otomatis menjadi "Lihat Juga" (tanpa mengulang tautan pilihan).
- Form: label "Nama Tradisi / Budaya Material" dan "Kategori".

## 2.6.0 (30 September 2026): Lokasi seragam & waktu pelaksanaan

**Baru**
- Field **Sistem Penanggalan** (Masehi, Hijriah, Saka (Bali), Jawa, Imlek, Kalender adat/musim, Mengikuti peristiwa) dan **Waktu Pelaksanaan** (teks, mis. "12 Rabiul Awal"), untuk tradisi yang tanggal Masehinya berubah setiap tahun atau tidak terikat kalender.
- Halaman detail: kotak info menampilkan "Waktu Pelaksanaan", mis. "12 Rabiul Awal (kalender Hijriah)", lewat `tk_format_waktu_pelaksanaan()`.
- Saran isian **Kabupaten/Kota** saat mengetik (`assets/js/kabkota.js`, datalist bawaan browser), disaring sesuai Provinsi yang dipilih: di form depan, editor blok, dan editor klasik.
- Daftar resmi 514 kabupaten/kota di 38 provinsi (`includes/data/kabupaten-kota.php`, Kepmendagri No. 300.2.2-2138 Tahun 2025).
- `includes/core/kabupaten-kota.php`: isian harus ada di daftar (dan di provinsi yang dipilih, bila diketahui); penulisan diseragamkan saat disimpan, mis. "kab. tana toraja" → "Kabupaten Tana Toraja". "Simpan Draf" tetap boleh berisi teks bebas.
- Tanggal Perayaan: ikon kalender di form depan dan petunjuk isian.

**Diubah**
- "Tanggal Perayaan" menjadi **Tanggal Terdekat** (opsional, tetap field `tanggal_perayaan`); di halaman detail tampil dengan label yang sama.
- Format tampilan date picker Tanggal Terdekat menjadi `j F Y` (mis. "30 September 2026"), sama dengan halaman detail. Nama bulan mengikuti bahasa situs.
- Label taxonomy `wilayah` menjadi **Provinsi** dan field `asal_daerah` menjadi **Kabupaten/Kota** (wp-admin, form depan, daftar kelengkapan kurasi). Slug dan nama field tidak berubah, jadi data dan URL tetap.
- Field Kabupaten/Kota diberi petunjuk dan contoh isian.
- Halaman detail: provinsi tidak lagi tampil dua kali (dihapus dari kotak info, tetap di baris meta). Baris meta diberi tooltip "Kabupaten/Kota" dan "Provinsi".

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
