<?php
/**
 * Template halaman detail tradisi (/tradisi/nama-tradisi/).
 *
 * Dipasang otomatis oleh includes/core/post-types.php.
 * Logika data ada di includes/core/single-data.php → file ini hanya HTML.
 * Gaya ada di assets/css/single.css.
 *
 * Urutan bagian:
 *   1. Link kembali
 *   2. Badge jenis + kategori + status, judul, tanggal terbit
 *   3. Baris meta: penulis, kabupaten/kota, provinsi, pembaca; tombol
 *      "Usulkan perubahan" untuk pemiliknya (kontributor)
 *   4. Gambar utama
 *   5. Abstrak (deskripsi_singkat)
 *   6. Kotak info, sesuai jenis:
 *        Tradisi          waktu pelaksanaan, tanggal terdekat, agama
 *        Budaya Material  bahan, lokasi penyimpanan, agama; lalu 6b. fungsi
 *   7. Isi artikel
 *   8. Galeri foto
 *   9. Lokasi di peta (kalau koordinat diisi)
 *  10. Kata kunci + sumber referensi + kredit foto
 *  10b. Panel Kurator (khusus kurator/admin, lihat includes/kurasi/panel-kurator.php)
 *  11. Tautan "Terkait dengan": "Digunakan dalam Tradisi" / "Budaya Material Terkait"
 *  12. Lihat Juga (otomatis: kategori/provinsi sama)
 *
 * Bagian yang datanya kosong otomatis tidak ditampilkan.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

while ( have_posts() ) :
    the_post();

    $id = get_the_ID();
    $d  = tk_single_get_data( $id );

    $material = ( 'budaya-material' === $d['jenis'] );

    // Kotak info (label => nilai), sesuai jenis. Baris kosong dilewati.
    // Kabupaten/kota & provinsi tidak di sini: sudah tampil di baris meta.
    $info = array_filter( $material
        ? array(
            'Bahan'                         => $d['bahan'],
            'Lokasi Penyimpanan/Keberadaan' => $d['lokasi'],
            'Agama'                         => $d['agama'],
        )
        : array(
            'Waktu Pelaksanaan' => $d['waktu'],
            'Tanggal Terdekat'  => $d['tanggal'],
            'Agama'             => $d['agama'],
        )
    );

    $tautan = tk_single_get_tautan( $id );
    ?>

<div class="container">
  <article class="tk-single">

    <?php /* 1. Link kembali */ ?>
    <a class="tk-single__kembali" href="<?php echo esc_url( tk_url_jelajahi() ); ?>">← Kembali ke koleksi</a>

    <?php /* 2. Badge, judul, tanggal terbit */ ?>
    <header class="tk-single__head">
      <div class="tk-single__badges">
        <span class="tk-pill tk-pill--<?php echo esc_attr( $d['jenis'] ); ?>"><?php echo esc_html( TK_JENIS[ $d['jenis'] ] ); ?></span>
        <?php if ( $d['kategori'] ) : ?>
          <span class="tk-pill tk-pill--kat"><?php echo esc_html( $d['kategori'] ); ?></span>
        <?php endif; ?>
        <span class="tk-pill tk-pill--status">Terpublikasi</span>
        <span class="tk-single__tgl"><?php echo esc_html( get_the_date() ); ?></span>
      </div>

      <h1 class="tk-single__title"><?php the_title(); ?></h1>

      <?php /* 3. Baris meta */ ?>
      <ul class="tk-single__meta">
        <li><?php echo tk_icon( 'user' ); ?><?php the_author(); ?></li>
        <?php if ( $d['asal'] ) : ?>
          <li title="Kabupaten/Kota"><?php echo tk_icon( 'pin' ); ?><?php echo esc_html( $d['asal'] ); ?></li>
        <?php endif; ?>
        <?php if ( $d['wilayah'] ) : ?>
          <li title="Provinsi"><?php echo tk_icon( 'gedung' ); ?><?php echo esc_html( $d['wilayah'] ); ?></li>
        <?php endif; ?>
        <li><?php echo tk_icon( 'mata' ); ?><?php echo esc_html( number_format_i18n( $d['pembaca'] ) ); ?> pembaca</li>
      </ul>

      <?php /* Pemilik (kontributor) bisa mengusulkan perubahan; kurator memakai Panel Kurator. */ ?>
      <?php if ( tk_form_boleh_usul( $id ) ) : ?>
        <a class="tk-btn-kecil tk-single__ubah" href="<?php echo esc_url( tk_url_ubah( $id ) ); ?>">Usulkan perubahan</a>
      <?php endif; ?>
    </header>

    <?php /* 4. Gambar utama */ ?>
    <?php if ( has_post_thumbnail() ) : ?>
      <figure class="tk-single__featured-img"><?php the_post_thumbnail( 'large' ); ?></figure>
    <?php endif; ?>

    <?php /* 5. Abstrak */ ?>
    <?php if ( $d['abstrak'] ) : ?>
      <div class="tk-single__abstract">
        <span class="tk-single__abstract-label">Abstrak / Ringkasan</span>
        <?php echo esc_html( $d['abstrak'] ); ?>
      </div>
    <?php endif; ?>

    <?php /* 6. Kotak info */ ?>
    <?php if ( $info ) : ?>
      <dl class="tk-single__info">
        <?php foreach ( $info as $label => $nilai ) : ?>
          <div>
            <dt><?php echo esc_html( $label ); ?></dt>
            <dd><?php echo esc_html( $nilai ); ?></dd>
          </div>
        <?php endforeach; ?>
      </dl>
    <?php endif; ?>

    <?php /* 6b. Fungsi (budaya material): teks panjang, di luar kotak info */ ?>
    <?php if ( $material && $d['fungsi'] ) : ?>
      <div class="tk-single__fungsi">
        <span class="tk-single__abstract-label">Fungsi/Kegunaan</span>
        <?php echo nl2br( esc_html( $d['fungsi'] ) ); ?>
      </div>
    <?php endif; ?>

    <?php /* 7. Isi artikel */ ?>
    <div class="tk-single__content"><?php the_content(); ?></div>

    <?php /* 8. Galeri foto */ ?>
    <?php if ( $d['galeri'] ) : ?>
      <section class="tk-single__section">
        <h2 class="tk-single__section-title">Galeri Foto <span class="tk-galeri__jumlah"><?php echo count( $d['galeri'] ); ?> foto</span></h2>
        <?php /* Klik foto → lightbox (assets/js/galeri.js). Tanpa JS, link membuka foto besar. */ ?>
        <div class="tk-galeri" data-tk-galeri>
          <?php foreach ( $d['galeri'] as $foto_id ) : ?>
            <a href="<?php echo esc_url( wp_get_attachment_image_url( $foto_id, 'large' ) ); ?>"
               data-keterangan="<?php echo esc_attr( wp_get_attachment_caption( $foto_id ) ); ?>">
              <?php echo wp_get_attachment_image( $foto_id, 'medium', false, array( 'loading' => 'lazy' ) ); ?>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <?php /* 9. Lokasi di peta */ ?>
    <?php $titik = tk_peta_get_titik( array( $id ) ); ?>
    <?php if ( $titik ) : ?>
      <section class="tk-single__section">
        <h2 class="tk-single__section-title">Lokasi</h2>
        <?php echo tk_peta_render( $titik, array( 'tinggi' => 320, 'panel' => false ) ); ?>
      </section>
    <?php endif; ?>

    <?php /* 10. Kata kunci + sumber + kredit foto */ ?>
    <?php if ( $d['tags'] || $d['sumber'] || $d['kredit'] ) : ?>
      <footer class="tk-single__kaki">
        <?php if ( $d['tags'] ) : ?>
          <div class="tk-single__tags">
            <span class="tk-label">Kata Kunci</span>
            <?php foreach ( $d['tags'] as $tag ) : ?>
              <a class="tag-pill" href="<?php echo esc_url( get_term_link( $tag ) ); ?>">#<?php echo esc_html( $tag->name ); ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ( $d['sumber'] ) : ?>
          <p class="tk-single__sumber">
            <?php echo tk_icon( 'link' ); ?>
            <span>Sumber: <a href="<?php echo esc_url( $d['sumber'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $d['sumber'] ); ?></a></span>
          </p>
        <?php endif; ?>

        <?php if ( $d['kredit'] ) : ?>
          <p class="tk-single__sumber">
            <?php echo tk_icon( 'kamera' ); ?>
            <span>Kredit foto:<br><?php echo nl2br( esc_html( $d['kredit'] ) ); ?></span>
          </p>
        <?php endif; ?>
      </footer>
    <?php endif; ?>

    <?php /* 10b. Panel Kurator (hanya terlihat oleh kurator/admin), setelah isi koleksi */ ?>
    <?php echo tk_panel_kurator( $id ); // phpcs:ignore WordPress.Security.EscapeOutput -- di-escape di dalam fungsi. ?>

  </article>

  <?php /* 11. Tautan pilihan (field "Terkait dengan"), dikelompokkan per jenis */ ?>
  <?php foreach ( $tautan as $jenis_tautan => $ids ) : ?>
    <section class="tk-single__terkait">
      <div class="tk-koleksi-head"><h2><?php echo esc_html( tk_single_judul_tautan( $jenis_tautan, $d['jenis'] ) ); ?></h2></div>
      <div class="tk-grid">
        <?php foreach ( $ids as $tautan_id ) { echo tk_koleksi_render_kartu( $tautan_id ); } ?>
      </div>
    </section>
  <?php endforeach; ?>

  <?php /* 12. Lihat juga: koleksi mirip (otomatis), tanpa mengulang tautan */ ?>
  <?php $terkait = tk_single_get_terkait( $id, 3, $tautan ? array_merge( ...array_values( $tautan ) ) : array() ); ?>
  <?php if ( $terkait ) : ?>
    <section class="tk-single__terkait">
      <div class="tk-koleksi-head"><h2>Lihat Juga</h2></div>
      <div class="tk-grid">
        <?php foreach ( $terkait as $terkait_id ) { echo tk_koleksi_render_kartu( $terkait_id ); } ?>
      </div>
    </section>
  <?php endif; ?>
</div>

    <?php
endwhile;

get_footer();
