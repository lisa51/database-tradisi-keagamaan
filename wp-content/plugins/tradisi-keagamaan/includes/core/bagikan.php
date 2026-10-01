<?php
/**
 * Tombol "Bagikan" di halaman detail koleksi (templates/single-tradisi.php).
 * Tombol berupa ikon saja; nama tombol ada di aria-label & title (tooltip).
 *
 *   WhatsApp       tautan wa.me berisi judul + URL
 *   Facebook       facebook.com/sharer (pratinjau dari Open Graph)
 *   X              x.com/intent/tweet berisi judul + URL
 *   Email          mailto: berisi judul, abstrak, dan URL
 *   Instagram      Instagram tidak punya tautan berbagi untuk web: di ponsel
 *                  membuka menu bagikan bawaan (Web Share API, ada pilihan
 *                  Instagram); di komputer menyalin tautan + petunjuk.
 *   Salin tautan   menyalin URL ke clipboard
 *   Lainnya        menu bagikan bawaan perangkat (Telegram, Line, dll.); tersembunyi
 *                  sampai bagikan.js memastikan browser mendukungnya (umumnya ponsel).
 *
 * Perilaku tombol Instagram & Salin: assets/js/bagikan.js. Gaya: single.css
 * (warna merek: token --tk-wa, --tk-fb, --tk-x, --tk-ig-* di base.css).
 * Pratinjau tautan (judul, gambar) di WhatsApp dll. diambil dari tag Open Graph Rank Math.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * HTML bilah bagikan untuk satu koleksi.
 *
 * @param int $id ID koleksi.
 * @return string HTML.
 */
function tk_bagikan_render( $id ) {
    $url     = get_permalink( $id );
    $judul   = html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
    $situs   = get_bloginfo( 'name' );
    $abstrak = wp_trim_words( (string) get_post_meta( $id, 'deskripsi_singkat', true ), 40 );

    $wa    = 'https://wa.me/?text=' . rawurlencode( $judul . ' | ' . $situs . "\n" . $url );
    $fb    = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url );
    $x     = 'https://x.com/intent/tweet?text=' . rawurlencode( $judul . ' | ' . $situs ) . '&url=' . rawurlencode( $url );
    $email = 'mailto:?subject=' . rawurlencode( $judul . ' | ' . $situs )
        . '&body=' . rawurlencode( ( $abstrak ? $abstrak . "\n\n" : '' ) . 'Selengkapnya: ' . $url );

    ob_start();
    ?>
    <div class="tk-bagikan" data-tk-bagikan data-url="<?php echo esc_url( $url ); ?>" data-judul="<?php echo esc_attr( $judul ); ?>">
      <span class="tk-bagikan__label">Bagikan</span>
      <a class="tk-bagikan__tombol tk-bagikan__tombol--wa" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener"
         aria-label="Bagikan lewat WhatsApp" title="WhatsApp">
        <?php echo tk_bagikan_ikon( 'wa' ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG statis. ?>
      </a>
      <a class="tk-bagikan__tombol tk-bagikan__tombol--fb" href="<?php echo esc_url( $fb ); ?>" target="_blank" rel="noopener"
         aria-label="Bagikan ke Facebook" title="Facebook">
        <?php echo tk_bagikan_ikon( 'fb' ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG statis. ?>
      </a>
      <a class="tk-bagikan__tombol tk-bagikan__tombol--x" href="<?php echo esc_url( $x ); ?>" target="_blank" rel="noopener"
         aria-label="Bagikan ke X (Twitter)" title="X (Twitter)">
        <?php echo tk_bagikan_ikon( 'x', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG statis. ?>
      </a>
      <button type="button" class="tk-bagikan__tombol tk-bagikan__tombol--ig" data-tk-bagikan-aksi="instagram"
              aria-label="Bagikan ke Instagram" title="Instagram">
        <?php echo tk_icon( 'instagram', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG statis. ?>
      </button>
      <a class="tk-bagikan__tombol tk-bagikan__tombol--email" href="<?php echo esc_url( $email, array( 'mailto' ) ); ?>"
         aria-label="Bagikan lewat email" title="Email">
        <?php echo tk_icon( 'surat', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG statis. ?>
      </a>
      <button type="button" class="tk-bagikan__tombol tk-bagikan__tombol--salin" data-tk-bagikan-aksi="salin"
              aria-label="Salin tautan" title="Salin tautan">
        <span class="tk-bagikan__ikon"><?php echo tk_icon( 'link', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG statis. ?></span>
        <span class="tk-bagikan__ikon tk-bagikan__ikon--ok"><?php echo tk_icon( 'centang', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG statis. ?></span>
      </button>
      <button type="button" class="tk-bagikan__tombol tk-bagikan__tombol--lainnya" data-tk-bagikan-aksi="lainnya" hidden
              aria-label="Bagikan lewat aplikasi lain" title="Lainnya">
        <?php echo tk_icon( 'bagikan', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG statis. ?>
      </button>
      <p class="tk-bagikan__pesan" data-tk-bagikan-pesan role="status" aria-live="polite"></p>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Logo merek (isi penuh, warna mengikuti teks). WhatsApp & X dari Simple Icons (CC0).
 *
 * @param string $nama 'wa' | 'fb' | 'x'.
 * @param int    $size Ukuran (px).
 * @return string SVG.
 */
function tk_bagikan_ikon( $nama, $size = 18 ) {
    $paths = array(
        'fb' => 'M14 8h2.5V4.5H14c-2.5 0-4 1.6-4 4.2V11H7.5v3.5H10V22h3.5v-7.5H16l.5-3.5h-3V9c0-.6.4-1 1-1z',
        'x'  => 'M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z',
        'wa' => 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z',
    );
    return '<svg width="' . absint( $size ) . '" height="' . absint( $size ) . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="' . $paths[ $nama ] . '"/></svg>';
}
