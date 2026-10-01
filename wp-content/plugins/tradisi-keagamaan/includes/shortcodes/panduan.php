<?php
/**
 * Shortcode [tk_panduan_kontribusi]: halaman Panduan Kontribusi.
 *
 * Pemakaian: taruh di halaman "Panduan Kontribusi" (slug TK_SLUG_PANDUAN).
 *
 * Angka aturan (minimal kata, ukuran & jumlah foto) dibaca dari config.php,
 * jadi panduan selalu sama dengan aturan form. Gaya: assets/css/panduan.css.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'tk_panduan_kontribusi', 'tk_panduan_shortcode' );

/**
 * Render halaman panduan.
 *
 * @return string HTML.
 */
function tk_panduan_shortcode() {
    $url_kirim   = tk_url_tambah();
    $url_kontak  = tk_url_halaman( 'hubungi-kami' );
    $url_masuk   = wp_login_url( $url_kirim );
    $url_daftar  = get_option( 'users_can_register' ) ? wp_registration_url() : '';

    $bagian = array(
        'apa'     => 'Koleksi yang dapat dikirim',
        'cara'    => 'Dua cara berkontribusi',
        'langkah' => 'Langkah pengiriman',
        'isian'   => 'Isian dan kelengkapan',
        'foto'    => 'Ketentuan foto',
        'lisensi' => 'Hak cipta dan lisensi foto',
        'kurasi'  => 'Proses kurasi',
        'ubah'    => 'Mengubah koleksi yang telah terbit',
        'etika'   => 'Etika berkontribusi',
    );

    ob_start();
    ?>
    <div class="tk-panduan">

      <p class="tk-panduan__lead">WARISI dihimpun bersama masyarakat. Setiap orang yang mengenal tradisi atau benda budaya keagamaan di daerahnya dapat turut memperkaya khazanah keagamaan Nusantara. Panduan ini menjelaskan jenis koleksi yang dapat dikirim, tata cara pengirimannya, serta proses peninjauan oleh kurator.</p>

      <div class="tk-panduan__aksi">
        <a class="tk-btn tk-btn-utama" href="<?php echo esc_url( $url_kirim ); ?>">+ Mulai Berkontribusi</a>
      </div>

      <nav class="tk-panduan__daftar" aria-label="Daftar isi panduan">
        <h2 class="tk-label">Daftar isi</h2>
        <ol>
          <?php foreach ( $bagian as $id => $judul ) : ?>
            <li><a href="#<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $judul ); ?></a></li>
          <?php endforeach; ?>
        </ol>
      </nav>

      <section id="apa" class="tk-panduan__bagian">
        <h2><?php echo esc_html( $bagian['apa'] ); ?></h2>
        <p>Setiap kiriman termasuk dalam salah satu dari dua jenis koleksi berikut.</p>
        <div class="tk-panduan__kartu-grup">
          <div class="tk-panduan__kartu">
            <h3><?php echo tk_icon( 'buku', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG statis. ?> <?php echo esc_html( TK_JENIS['tradisi'] ); ?></h3>
            <p>Praktik yang <strong>dijalankan</strong>, seperti ritual, upacara, perayaan, tarian, atau kebiasaan keagamaan.</p>
            <p class="tk-panduan__contoh">Contoh: Ngaben, Tabuik, Pasola.</p>
          </div>
          <div class="tk-panduan__kartu">
            <h3><?php echo tk_icon( 'benda', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG statis. ?> <?php echo esc_html( TK_JENIS['budaya-material'] ); ?></h3>
            <p>Wujud kebendaan, seperti <strong>benda, bangunan atau situs, naskah, dan makanan</strong>, yang berkaitan dengan kehidupan keagamaan.</p>
            <p class="tk-panduan__contoh">Contoh: Kitab Kuning Mattuttung, Tenun Toraja.</p>
          </div>
        </div>
      </section>

      <section id="cara" class="tk-panduan__bagian">
        <h2><?php echo esc_html( $bagian['cara'] ); ?></h2>
        <div class="tk-panduan__kartu-grup">
          <div class="tk-panduan__kartu">
            <h3>Tanpa akun</h3>
            <ul>
              <li>Anda tidak memerlukan akun; cukup mengisi nama dan alamat email.</li>
              <li>Kiriman langsung masuk ke antrean kurasi.</li>
              <li>Apabila diperlukan revisi, Anda akan menerima tautan pribadi melalui email.</li>
            </ul>
          </div>
          <div class="tk-panduan__kartu">
            <h3>Dengan akun</h3>
            <ul>
              <li>Anda dapat menyimpan draf dan melanjutkannya kapan saja.</li>
              <li>Status seluruh kiriman dapat dipantau pada bagian "Kiriman Saya".</li>
              <li>Anda dapat mengusulkan perubahan atas koleksi Anda yang telah terbit.</li>
            </ul>
            <p class="tk-panduan__contoh">
              <a href="<?php echo esc_url( $url_masuk ); ?>">Masuk</a><?php if ( $url_daftar ) : ?> atau <a href="<?php echo esc_url( $url_daftar ); ?>">daftarkan akun baru</a><?php endif; ?>.
            </p>
          </div>
        </div>
      </section>

      <section id="langkah" class="tk-panduan__bagian">
        <h2><?php echo esc_html( $bagian['langkah'] ); ?></h2>
        <ol class="tk-panduan__langkah">
          <li><strong>Siapkan bahan.</strong> Persiapkan tulisan, foto, sumber rujukan, serta keterangan lokasi (provinsi dan kabupaten/kota).</li>
          <li><strong>Buka halaman <a href="<?php echo esc_url( $url_kirim ); ?>">Kontribusi Koleksi</a>.</strong> Pengirim tanpa akun mengisi nama dan alamat email terlebih dahulu.</li>
          <li><strong>Pilih jenis, kemudian kategori.</strong> Pilihan kategori akan menyesuaikan jenis yang dipilih.</li>
          <li><strong>Isi nama, abstrak, dan isi artikel.</strong> Untuk tradisi, uraikan sejarah, makna, dan tata caranya. Untuk budaya material, uraikan asal-usul, wujud, dan maknanya.</li>
          <li><strong>Lengkapi lokasi.</strong> Isi provinsi dan kabupaten/kota (pilih dari saran yang muncul). Apabila diketahui, isi juga koordinat agar koleksi tampil di peta.</li>
          <li><strong>Unggah foto.</strong> Unggah satu foto utama dan, apabila ada, foto pendukung lainnya. Pastikan seluruh foto <a href="#lisensi">bebas digunakan</a>.</li>
          <li><strong>Cantumkan sumber.</strong> Tuliskan tautan rujukan utama pada isian Sumber Referensi, daftar pustaka atau narasumber di bagian akhir isi artikel, serta pembuat dan lisensi foto pada isian Kredit Foto.</li>
          <li><strong>Klik "Kirim untuk Dikurasi".</strong> Pengguna akun juga dapat memilih "Simpan Draf" untuk melanjutkannya di lain waktu. Setelah terkirim, Anda akan menerima email konfirmasi.</li>
        </ol>
      </section>

      <section id="isian" class="tk-panduan__bagian">
        <h2><?php echo esc_html( $bagian['isian'] ); ?></h2>
        <p>Kurator memeriksa kelengkapan setiap kiriman. Kiriman yang lengkap lebih mudah ditinjau dan lebih bermanfaat bagi pembaca. Isian berstatus <strong>wajib</strong> harus diisi agar kiriman dapat dikirim.</p>
        <div class="tk-panduan__tabel">
          <table>
            <thead><tr><th>Isian</th><th>Keterangan</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ( tk_panduan_isian() as $baris ) : ?>
                <tr>
                  <td><?php echo esc_html( $baris[0] ); ?></td>
                  <td><?php echo esc_html( $baris[1] ); ?></td>
                  <td><span class="tk-panduan__wajib tk-panduan__wajib--<?php echo esc_attr( sanitize_key( $baris[2] ) ); ?>"><?php echo esc_html( $baris[2] ); ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <section id="foto" class="tk-panduan__bagian">
        <h2><?php echo esc_html( $bagian['foto'] ); ?></h2>
        <ul>
          <li>Foto berformat <strong>JPG, PNG, atau WEBP</strong> dengan ukuran maksimal <strong><?php echo esc_html( TK_FOTO_MAKS_MB ); ?> MB</strong> per foto.</li>
          <li>Setiap koleksi memiliki satu foto utama (tampil pada kartu dan bagian atas halaman) dan dapat dilengkapi hingga <strong><?php echo esc_html( TK_GALERI_MAKS ); ?> foto</strong> pada Galeri Foto.</li>
          <li>Foto harus <strong>bebas digunakan</strong>, yaitu milik Anda sendiri atau berlisensi terbuka. Lihat bagian <a href="#lisensi">Hak cipta dan lisensi foto</a>.</li>
          <li>Mintalah izin terlebih dahulu sebelum memotret atau mengunggah foto yang menampilkan orang atau ritual tertentu.</li>
        </ul>
      </section>

      <section id="lisensi" class="tk-panduan__bagian">
        <h2><?php echo esc_html( $bagian['lisensi'] ); ?></h2>
        <p>Setiap foto yang diunggah akan ditampilkan kepada publik. Oleh karena itu, foto <strong>harus bebas digunakan</strong>, artinya Anda adalah pemegang hak ciptanya atau pemiliknya telah mengizinkan foto tersebut digunakan kembali. Perlu diingat, foto yang mudah diunduh dari internet <strong>belum tentu</strong> bebas digunakan.</p>
        <div class="tk-panduan__kartu-grup">
          <div class="tk-panduan__kartu tk-panduan__kartu--boleh">
            <h3>Dapat diunggah</h3>
            <ul>
              <li>Foto hasil pemotretan Anda sendiri.</li>
              <li>Foto milik orang atau lembaga lain yang <strong>telah memberikan izin</strong> untuk diterbitkan di WARISI. Simpanlah bukti izin tersebut, misalnya berupa pesan atau email.</li>
              <li>Foto berlisensi terbuka, seperti <strong>Creative Commons</strong> CC0, CC BY, atau CC BY-SA, misalnya dari Wikimedia Commons.</li>
              <li>Foto yang telah menjadi milik umum (domain publik).</li>
            </ul>
          </div>
          <div class="tk-panduan__kartu tk-panduan__kartu--larang">
            <h3>Tidak boleh diunggah</h3>
            <ul>
              <li>Foto dari mesin pencari, media sosial, situs berita, atau blog tanpa izin pemiliknya.</li>
              <li>Hasil pindaian atau foto halaman buku, majalah, dan katalog tanpa izin dari penerbitnya.</li>
              <li>Foto yang memuat tanda air (<em>watermark</em>) atau logo pihak lain.</li>
              <li>Foto yang asal-usul dan pemiliknya tidak diketahui.</li>
            </ul>
          </div>
        </div>
        <p><strong>Cantumkan pembuat dan lisensi setiap foto</strong> pada isian <strong>Kredit Foto</strong>, satu baris untuk setiap foto. Keterangan ini ditampilkan pada halaman koleksi, sebagaimana diwajibkan oleh lisensi seperti CC BY. Contoh penulisan:</p>
        <ul class="tk-panduan__contoh-kutip">
          <li>Foto utama: dokumentasi pribadi, Siti Aminah, 2024.</li>
          <li>Foto galeri 2: Budi Santoso, Wikimedia Commons, CC BY-SA 4.0, beserta tautannya.</li>
          <li>Foto galeri 3: Arsip Sanggar Budaya Situraja, digunakan dengan izin.</li>
        </ul>
        <p>Kurator dapat meminta Anda mengganti foto yang asal-usulnya tidak jelas. Apabila Anda ragu mengenai suatu lisensi, silakan bertanya terlebih dahulu melalui <a href="<?php echo esc_url( $url_kontak ); ?>">Hubungi Kami</a>. Pemilik hak cipta yang berkeberatan karyanya ditampilkan di WARISI juga dapat menghubungi kami. Foto tersebut akan ditinjau dan diturunkan apabila terbukti melanggar hak cipta.</p>
      </section>

      <section id="kurasi" class="tk-panduan__bagian">
        <h2><?php echo esc_html( $bagian['kurasi'] ); ?></h2>
        <p>Setiap kiriman ditinjau oleh kurator sebelum ditampilkan kepada publik. Pemberitahuan pada setiap tahap dikirimkan ke email Anda.</p>
        <ol class="tk-panduan__alur">
          <li><span class="tk-status tk-status--pending">Menunggu Kurasi</span> Kiriman telah diterima dan masuk ke antrean kurasi.</li>
          <li><span class="tk-status tk-status--revisi">Perlu Revisi</span> Kurator mengirimkan catatan perbaikan beserta tautan untuk merevisi. Setelah diperbaiki, kirimkan kembali; kiriman akan masuk lagi ke antrean kurasi.</li>
          <li><span class="tk-status tk-status--publish">Terbit</span> Koleksi ditampilkan di WARISI, dan Anda menerima tautan ke halamannya.</li>
          <li><span class="tk-status tk-status--trash">Ditolak</span> Apabila kiriman belum dapat diterbitkan, kurator akan menyampaikan alasannya.</li>
        </ol>
      </section>

      <section id="ubah" class="tk-panduan__bagian">
        <h2><?php echo esc_html( $bagian['ubah'] ); ?></h2>
        <ul>
          <li><strong>Koleksi milik Anda (pengguna akun):</strong> buka halaman koleksi tersebut, klik <strong>"Usulkan perubahan"</strong>, lalu kirimkan. Versi yang telah terbit tetap ditampilkan sampai usulan disetujui oleh kurator.</li>
          <li><strong>Koleksi milik pihak lain atau kiriman tanpa akun:</strong> sampaikan koreksi melalui <a href="<?php echo esc_url( $url_kontak ); ?>">Hubungi Kami</a> dengan perihal "Koreksi data koleksi".</li>
        </ul>
      </section>

      <section id="etika" class="tk-panduan__bagian">
        <h2><?php echo esc_html( $bagian['etika'] ); ?></h2>
        <ul>
          <li>Tuliskan uraian dengan kalimat Anda sendiri. Kutipan dapat digunakan dengan menyebutkan sumbernya.</li>
          <li>Pastikan informasi yang disampaikan akurat. Bedakan informasi yang berasal dari pengamatan langsung, wawancara, dan rujukan tertulis.</li>
          <li>Hormati komunitas pemilik tradisi. Pengetahuan yang dianggap sakral atau tertutup hendaknya tidak dipublikasikan tanpa persetujuan komunitas tersebut.</li>
          <li>Jangan mencantumkan data pribadi orang lain, seperti nomor telepon atau alamat rumah.</li>
        </ul>
      </section>

      <div class="tk-panduan__penutup">
        <h2>Mari Berkontribusi</h2>
        <p>Apabila Anda mengenal tradisi atau benda budaya keagamaan di daerah Anda, kami mengundang Anda untuk turut melengkapi khazanah keagamaan Nusantara.</p>
        <div class="tk-panduan__aksi">
          <a class="tk-btn tk-btn-utama" href="<?php echo esc_url( $url_kirim ); ?>">+ Mulai Berkontribusi</a>
          <a class="tk-btn tk-btn-garis" href="<?php echo esc_url( $url_kontak ); ?>">Hubungi Kami</a>
        </div>
      </div>

    </div>
    <?php
    return ob_get_clean();
}

/**
 * Baris tabel isian: nama, keterangan, status (Wajib / Dianjurkan / Opsional).
 * "Dianjurkan" = masuk checklist kelengkapan kurator (tk_kelengkapan()).
 *
 * @return array[]
 */
function tk_panduan_isian() {
    return array(
        array( 'Nama', 'Nama tradisi atau budaya material.', 'Wajib' ),
        array( 'Jenis dan kategori', 'Tradisi atau Budaya Material, kemudian satu kategori atau lebih.', 'Wajib' ),
        array( 'Provinsi', 'Provinsi asal koleksi.', 'Wajib' ),
        array( 'Foto utama', 'Ditampilkan pada kartu dan bagian atas halaman.', 'Wajib' ),
        array( 'Abstrak', 'Ringkasan sepanjang dua sampai tiga kalimat.', 'Dianjurkan' ),
        array( 'Isi artikel', 'Minimal ' . TK_MIN_KATA_ISI . ' kata.', 'Dianjurkan' ),
        array( 'Kabupaten/kota', 'Dipilih dari saran yang muncul saat mengetik.', 'Dianjurkan' ),
        array( 'Sumber referensi', 'Satu tautan (URL) rujukan utama. Rujukan berupa buku, artikel, atau wawancara dituliskan di bagian akhir isi artikel.', 'Dianjurkan' ),
        array( 'Kredit foto', 'Pembuat dan lisensi setiap foto, satu baris untuk setiap foto.', 'Dianjurkan' ),
        array( 'Koordinat', 'Garis lintang (latitude) dan garis bujur (longitude), agar koleksi tampil di peta.', 'Dianjurkan' ),
        array( 'Foto tambahan', 'Hingga ' . TK_GALERI_MAKS . ' foto pendukung untuk Galeri Foto.', 'Opsional' ),
        array( 'Kata kunci', 'Dipisahkan dengan koma, misalnya: kremasi, Hindu Bali.', 'Opsional' ),
    );
}
