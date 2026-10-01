<?php
/**
 * Footer situs (tema GeneratePress).
 *
 *   generate_before_footer_content  tk_footer_render()     kolom: identitas, lembaga, tautan
 *   generate_copyright              tk_footer_hak_cipta()  baris bawah (menggantikan "Built with GeneratePress")
 *
 * Isi diatur di config.php: TK_LEMBAGA, TK_SLOGAN, TK_FOOTER_TAUTAN.
 * Gaya: bagian "6. Footer" di assets/css/base.css.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'generate_before_footer_content', 'tk_footer_render' );

/**
 * Kolom footer: nama situs & deskripsi, lembaga pengelola, dan tautan.
 */
function tk_footer_render() {
    $lembaga = TK_LEMBAGA;
    ?>
    <div class="tk-footer">
      <div class="tk-footer__isi">
        <div class="tk-footer__identitas">
          <a class="tk-footer__nama" href="<?php echo esc_url( home_url( '/' ) ); ?>">WARISI</a>
          <p class="tk-footer__slogan"><?php echo esc_html( TK_SLOGAN ); ?></p>
          <p class="tk-footer__desk">Basis data digital tradisi dan budaya material keagamaan Indonesia, dihimpun bersama masyarakat dan dikurasi oleh peneliti.</p>
        </div>

        <div class="tk-footer__lembaga">
          <h2 class="tk-footer__judul">Dikelola oleh</h2>
          <div class="tk-footer__lembaga-isi">
            <?php if ( TK_LOGO_LEMBAGA && file_exists( TK_PATH . TK_LOGO_LEMBAGA ) ) : ?>
              <?php $ukuran = getimagesize( TK_PATH . TK_LOGO_LEMBAGA ); ?>
              <img class="tk-footer__logo" src="<?php echo esc_url( add_query_arg( 'ver', filemtime( TK_PATH . TK_LOGO_LEMBAGA ), TK_URL . TK_LOGO_LEMBAGA ) ); ?>"
                   alt="<?php echo esc_attr( TK_LOGO_LEMBAGA_ALT ); ?>"
                   width="<?php echo (int) $ukuran[0]; ?>" height="<?php echo (int) $ukuran[1]; ?>" loading="lazy">
            <?php endif; ?>
            <div>
              <p class="tk-footer__lembaga-utama"><?php echo esc_html( array_shift( $lembaga ) ); ?></p>
              <?php foreach ( $lembaga as $induk ) : ?>
                <p class="tk-footer__lembaga-induk"><?php echo esc_html( $induk ); ?></p>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <?php foreach ( TK_FOOTER_TAUTAN as $judul => $tautan ) : ?>
          <?php $daftar = tk_footer_daftar_tautan( $tautan ); ?>
          <?php if ( $daftar ) : ?>
            <nav class="tk-footer__tautan" aria-label="<?php echo esc_attr( $judul ); ?>">
              <h2 class="tk-footer__judul"><?php echo esc_html( $judul ); ?></h2>
              <ul>
                <?php foreach ( $daftar as $url => $label ) : ?>
                  <li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a></li>
                <?php endforeach; ?>
              </ul>
            </nav>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
}

/**
 * Ubah daftar slug => label menjadi URL => label. Halaman yang belum dibuat dilewati.
 *
 * @param string[] $tautan Slug halaman (atau path berawalan "/") => label.
 * @return string[] URL => label.
 */
function tk_footer_daftar_tautan( $tautan ) {
    $hasil = array();
    foreach ( $tautan as $slug => $label ) {
        if ( 0 === strpos( $slug, '/' ) ) {
            $hasil[ home_url( $slug ) ] = $label;
            continue;
        }
        $page = get_page_by_path( $slug );
        if ( $page && 'publish' === $page->post_status ) {
            $hasil[ get_permalink( $page ) ] = $label;
        }
    }
    return $hasil;
}

add_filter( 'generate_copyright', 'tk_footer_hak_cipta' );

/**
 * Baris hak cipta di bagian paling bawah.
 *
 * @return string HTML.
 */
function tk_footer_hak_cipta() {
    // Singkatan lembaga induk dari kurung di akhir namanya, mis. "(BRIN)".
    $lembaga = TK_LEMBAGA;
    $induk   = end( $lembaga );
    if ( preg_match( '/\(([^)]+)\)\s*$/', $induk, $m ) ) {
        $induk = $m[1];
    }

    return sprintf(
        '<span class="copyright">&copy; %1$s WARISI &middot; %2$s, %3$s</span><span class="tk-footer__catatan">Konten koleksi disusun oleh kontributor dan dikurasi oleh tim WARISI.</span>',
        esc_html( wp_date( 'Y' ) ),
        esc_html( TK_LEMBAGA[0] ),
        esc_html( $induk )
    );
}
