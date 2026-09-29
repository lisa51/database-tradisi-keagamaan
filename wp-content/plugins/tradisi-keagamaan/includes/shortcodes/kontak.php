<?php
/**
 * Shortcode [tk_kontak]: form Hubungi Kami.
 *
 * Pemakaian: taruh di halaman "Hubungi Kami" (slug bebas), lalu jadikan
 * sub-menu di bawah "Tentang" (lihat README → Menu).
 *
 *   [tk_kontak]
 *   [tk_kontak judul="Hubungi Kami" deskripsi="..."]
 *
 * File ini hanya berisi TAMPILAN. Validasi, anti-spam, dan email ada di
 * includes/kontak/proses.php.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'tk_kontak', 'tk_kontak_shortcode' );

/**
 * Render shortcode [tk_kontak].
 *
 * @param array $atts
 * @return string HTML.
 */
function tk_kontak_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'judul'     => 'Kirim Pesan',
        'deskripsi' => 'Punya pertanyaan, koreksi data, atau tawaran kerja sama? Tulis pesan Anda dan tim WARISI akan membalas lewat email.',
    ), $atts, 'tk_kontak' );

    $state = isset( $GLOBALS['tk_kontak'] ) ? $GLOBALS['tk_kontak'] : array( 'data' => array(), 'galat' => array() );
    $data  = wp_parse_args( $state['data'], tk_kontak_isian_awal() );

    wp_enqueue_script( 'tk-kontak', TK_URL . 'assets/js/kontak.js', array(), filemtime( TK_PATH . 'assets/js/kontak.js' ), true );

    ob_start();
    ?>
    <div class="tk-kontak" id="tk-kontak">
        <?php if ( isset( $_GET['kontak'] ) && 'terkirim' === $_GET['kontak'] && ! $state['galat'] ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
            <div class="tk-notice tk-notice--sukses" role="status">
                <strong>Terima kasih, pesan Anda sudah terkirim.</strong> Kami akan membalas ke email Anda secepatnya.
            </div>
        <?php endif; ?>

        <?php if ( $state['galat'] ) : ?>
            <div class="tk-notice tk-notice--gagal" role="alert">
                <strong>Pesan belum terkirim:</strong>
                <ul>
                    <?php foreach ( $state['galat'] as $galat ) : ?>
                        <li><?php echo esc_html( $galat ); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form class="tk-form tk-kontak-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( get_permalink() . '#tk-kontak' ); ?>">
            <?php if ( $atts['judul'] ) : ?>
                <h2 class="tk-kontak-judul"><?php echo esc_html( $atts['judul'] ); ?></h2>
            <?php endif; ?>
            <?php if ( $atts['deskripsi'] ) : ?>
                <p class="tk-kontak-deskripsi"><?php echo esc_html( $atts['deskripsi'] ); ?></p>
            <?php endif; ?>

            <?php wp_nonce_field( 'tk_kontak', 'tk_kontak_nonce' ); ?>

            <div class="tk-kontak-baris">
                <p class="tk-kontak-field">
                    <label for="tk_nama">Nama <span class="tk-wajib">*</span></label>
                    <input type="text" id="tk_nama" name="tk_nama" required maxlength="100" autocomplete="name"
                        value="<?php echo esc_attr( $data['nama'] ); ?>">
                </p>
                <p class="tk-kontak-field">
                    <label for="tk_email">Email <span class="tk-wajib">*</span></label>
                    <input type="email" id="tk_email" name="tk_email" required maxlength="100" autocomplete="email"
                        value="<?php echo esc_attr( $data['email'] ); ?>">
                </p>
            </div>

            <p class="tk-kontak-field">
                <label for="tk_perihal">Perihal <span class="tk-wajib">*</span></label>
                <select id="tk_perihal" name="tk_perihal" required>
                    <option value="">Pilih perihal</option>
                    <?php foreach ( tk_kontak_perihal() as $kunci => $label ) : ?>
                        <option value="<?php echo esc_attr( $kunci ); ?>" <?php selected( $data['perihal'], $kunci ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p class="tk-kontak-field">
                <label for="tk_pesan">Pesan <span class="tk-wajib">*</span></label>
                <textarea id="tk_pesan" name="tk_pesan" rows="7" required minlength="10" maxlength="5000"><?php echo esc_textarea( $data['pesan'] ); ?></textarea>
            </p>

            <p class="tk-kontak-field">
                <label for="tk_lampiran">Lampiran <span class="tk-opsional">(opsional)</span></label>
                <input type="file" id="tk_lampiran" name="tk_lampiran" accept="<?php echo esc_attr( tk_kontak_lampiran_accept() ); ?>"
                    data-maks="<?php echo esc_attr( TK_KONTAK_LAMPIRAN_MAKS_MB * MB_IN_BYTES ); ?>" aria-describedby="tk_lampiran_ket">
                <span class="tk-kontak-ket" id="tk_lampiran_ket">
                    Satu file <?php echo esc_html( tk_kontak_lampiran_label() ); ?>, maks. <?php echo esc_html( TK_KONTAK_LAMPIRAN_MAKS_MB ); ?> MB.
                    Misalnya foto atau dokumen pendukung koreksi data.
                </span>
            </p>

            <?php // Honeypot: disembunyikan dari manusia, diisi oleh bot. ?>
            <p class="tk-kontak-hp" aria-hidden="true">
                <label for="tk_situs">Jangan diisi</label>
                <input type="text" id="tk_situs" name="tk_situs" tabindex="-1" autocomplete="off">
            </p>

            <div class="tk-form-tombol">
                <button type="submit" class="tk-btn tk-btn-utama">Kirim Pesan</button>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Isian awal: nama & email pengguna yang sedang login.
 *
 * @return array
 */
function tk_kontak_isian_awal() {
    $user = wp_get_current_user();

    return array(
        'nama'    => $user->exists() ? $user->display_name : '',
        'email'   => $user->exists() ? $user->user_email : '',
        'perihal' => '',
        'pesan'   => '',
    );
}
