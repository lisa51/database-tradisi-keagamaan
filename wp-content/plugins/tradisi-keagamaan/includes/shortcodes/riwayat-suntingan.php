<?php
/**
 * Shortcode [tk_riwayat_suntingan]: halaman Riwayat Suntingan satu koleksi.
 *
 * Pemakaian: taruh di halaman Riwayat Suntingan (slug TK_SLUG_RIWAYAT),
 * dibuka dengan ?id=<ID koleksi>. Khusus kurator & admin (hak tk_kurasi +
 * boleh menyunting koleksi itu). Tautan ada di Panel Kurator.
 *
 * Isi: daftar revisi WordPress (terbaru di atas). Tiap revisi dibandingkan
 * dengan revisi sebelumnya:
 *   - Judul, Ringkasan, Isi artikel.
 *   - Field ACF grup Detail Tradisi, bila revisi menyimpannya (ACF menyalin
 *     nilai field ke revisi). Revisi tanpa data field ditandai.
 * Taxonomy (Jenis, Agama, Provinsi, Kategori, Kata kunci) TIDAK tersimpan di
 * revisi WordPress, jadi tidak ikut dibandingkan.
 *
 * Teks panjang memakai wp_text_diff() (bagian dihapus/ditambah ditandai).
 * Gaya: .tk-riwayat-sunting di assets/css/kurasi.css.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'tk_riwayat_suntingan', 'tk_riwayat_sunting_shortcode' );

/**
 * Render shortcode.
 *
 * @return string
 */
function tk_riwayat_sunting_shortcode() {
    $id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification -- hanya menampilkan.
    $post = $id ? get_post( $id ) : null;

    if ( ! current_user_can( 'tk_kurasi' ) ) {
        return '<p class="tk-kosong">Halaman ini khusus untuk kurator WARISI.</p>';
    }
    if ( ! $post || 'tradisi' !== $post->post_type || ! current_user_can( 'edit_post', $id ) ) {
        return '<p class="tk-kosong">Koleksi tidak ditemukan. Buka halaman ini dari tautan "Riwayat suntingan" di Panel Kurator.</p>';
    }

    $revisi = tk_panel_get_revisi( $id ); // Terbaru dulu; autosave diabaikan (sama dengan Panel Kurator).

    ob_start();
    ?>
    <div class="tk-riwayat-sunting">
      <a class="tk-single__kembali" href="<?php echo esc_url( tk_url_tinjau( $id ) ); ?>#panel-kurator">← Kembali ke <?php echo esc_html( get_the_title( $id ) ); ?></a>
      <p class="tk-riwayat-sunting__info">
        <?php echo esc_html( count( $revisi ) ); ?> revisi tersimpan. Setiap revisi dibandingkan dengan revisi sebelumnya.
        Perubahan Jenis, Agama, Provinsi, Kategori, dan Kata kunci tidak tercatat di revisi.
      </p>

      <?php if ( ! $revisi ) : ?>
        <p class="tk-kosong">Belum ada revisi tersimpan untuk koleksi ini.</p>
      <?php else : ?>
        <ol class="tk-riwayat-sunting__daftar">
          <?php foreach ( $revisi as $i => $r ) :
              $lama   = isset( $revisi[ $i + 1 ] ) ? $revisi[ $i + 1 ] : null;
              $beda   = tk_riwayat_sunting_beda( $lama, $r );
              $judul  = $lama ? ( $beda['baris'] ? implode( ', ', array_column( $beda['baris'], 'label' ) ) : 'Tidak ada perubahan yang tercatat' ) : 'Versi awal';
              ?>
            <li id="rev-<?php echo absint( $r->ID ); ?>">
              <details<?php echo 0 === $i ? ' open' : ''; ?>>
                <summary>
                  <strong><?php echo esc_html( get_the_author_meta( 'display_name', $r->post_author ) ); ?></strong>
                  <span class="tk-log-waktu"><?php echo esc_html( mysql2date( 'j M Y, H:i', $r->post_date ) ); ?></span>
                  <span class="tk-riwayat-sunting__ringkas"><?php echo esc_html( $judul ); ?></span>
                  <?php if ( 0 === $i ) : ?><span class="tk-status tk-status--publish">Terbaru</span><?php endif; ?>
                </summary>

                <?php if ( $lama && ! $beda['field_tercatat'] ) : ?>
                  <p class="tk-riwayat-sunting__catatan">Revisi ini tidak menyimpan nilai field (mis. disimpan dari luar form), jadi hanya judul, ringkasan, dan isi yang dibandingkan.</p>
                <?php endif; ?>

                <?php if ( $beda['baris'] ) : ?>
                  <table class="tk-riwayat-sunting__tabel">
                    <thead><tr><th>Bagian</th><?php echo $lama ? '<th>Sebelum</th><th>Sesudah</th>' : '<th colspan="2">Isi</th>'; ?></tr></thead>
                    <tbody>
                      <?php foreach ( $beda['baris'] as $b ) : ?>
                        <tr>
                          <th scope="row"><?php echo esc_html( $b['label'] ); ?></th>
                          <?php if ( isset( $b['diff'] ) ) : ?>
                            <td colspan="2" class="tk-riwayat-sunting__diff"><?php echo $b['diff']; // phpcs:ignore WordPress.Security.EscapeOutput -- keluaran wp_text_diff(). ?></td>
                          <?php elseif ( ! $lama ) : // Versi awal: hanya isi. ?>
                            <td colspan="2"><?php echo esc_html( $b['baru'] ); ?></td>
                          <?php else : ?>
                            <td class="tk-riwayat-sunting__lama"><?php echo esc_html( $b['lama'] ); ?></td>
                            <td class="tk-riwayat-sunting__baru"><?php echo esc_html( $b['baru'] ); ?></td>
                          <?php endif; ?>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                <?php endif; ?>
              </details>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Bandingkan dua revisi.
 *
 * @param WP_Post|null $lama Revisi sebelumnya (null = versi awal: tampilkan isi terisi).
 * @param WP_Post      $baru
 * @return array { baris: array[] (label, lama, baru | diff), field_tercatat: bool }
 */
function tk_riwayat_sunting_beda( $lama, $baru ) {
    $baris = array();

    // Judul, ringkasan, isi.
    $teks = array( 'post_title' => array( 'Judul', false ), 'post_excerpt' => array( 'Ringkasan', true ), 'post_content' => array( 'Isi artikel', true ) );
    foreach ( $teks as $kolom => $conf ) {
        $a = $lama ? trim( wp_strip_all_tags( $lama->$kolom ) ) : '';
        $b = trim( wp_strip_all_tags( $baru->$kolom ) );
        if ( $a !== $b && ( $lama || '' !== $b ) ) {
            $baris[] = tk_riwayat_sunting_baris( $conf[0], $a, $b, $conf[1] && $lama );
        }
    }

    // Field ACF, bila kedua revisi menyimpannya.
    $meta_baru = get_metadata( 'post', $baru->ID );
    $meta_lama = $lama ? get_metadata( 'post', $lama->ID ) : array();
    $tercatat  = $meta_baru && ( ! $lama || $meta_lama );

    if ( $tercatat && function_exists( 'acf_get_fields' ) ) {
        foreach ( (array) acf_get_fields( TK_DETAIL_GROUP ) as $field ) {
            if ( in_array( $field['type'], array( 'taxonomy', 'button_group' ), true ) ) {
                continue; // Disimpan sebagai term, tidak tercatat di revisi.
            }
            $a = $lama ? tk_riwayat_sunting_nilai( $field, $meta_lama[ $field['name'] ][0] ?? '' ) : '';
            $b = tk_riwayat_sunting_nilai( $field, $meta_baru[ $field['name'] ][0] ?? '' );
            if ( $a !== $b && ( $lama || '' !== $b ) ) {
                $baris[] = tk_riwayat_sunting_baris( $field['label'], $a, $b, $lama && 'textarea' === $field['type'] );
            }
        }
    }

    return array( 'baris' => $baris, 'field_tercatat' => (bool) $tercatat );
}

/**
 * Satu baris perbandingan. Teks panjang memakai diff kata per kata.
 *
 * @param string $label
 * @param string $lama
 * @param string $baru
 * @param bool   $diff
 * @return array
 */
function tk_riwayat_sunting_baris( $label, $lama, $baru, $diff ) {
    if ( $diff ) {
        $html = wp_text_diff( $lama, $baru, array( 'show_split_view' => true ) );
        if ( $html ) {
            return array( 'label' => $label, 'diff' => $html );
        }
    }
    return array( 'label' => $label, 'lama' => '' === $lama ? '(kosong)' : $lama, 'baru' => '' === $baru ? '(kosong)' : $baru );
}

/**
 * Nilai mentah field (dari meta revisi) → teks yang mudah dibaca.
 *
 * @param array  $field Field ACF.
 * @param string $mentah
 * @return string
 */
function tk_riwayat_sunting_nilai( $field, $mentah ) {
    $nilai = maybe_unserialize( $mentah );
    if ( '' === $nilai || null === $nilai || array() === $nilai ) {
        return '';
    }
    switch ( $field['type'] ) {
        case 'date_picker':
            return tk_format_tanggal_acf( $nilai );
        case 'select':
            return isset( $field['choices'][ $nilai ] ) ? $field['choices'][ $nilai ] : (string) $nilai;
        case 'gallery':
            return count( (array) $nilai ) . ' foto';
        case 'relationship':
            return implode( ', ', array_map( 'get_the_title', array_filter( array_map( 'absint', (array) $nilai ) ) ) );
        default:
            return is_array( $nilai ) ? implode( ', ', $nilai ) : trim( (string) $nilai );
    }
}
