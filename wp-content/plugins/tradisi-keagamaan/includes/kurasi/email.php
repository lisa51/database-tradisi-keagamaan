<?php
/**
 * Email pemberitahuan alur kurasi (lewat wp_mail()).
 *
 *   Ke kurator   kiriman baru & kiriman ulang setelah revisi.
 *   Ke pengirim  diterima, terbit, diminta revisi (dengan link), ditolak (dengan alasan).
 *
 * Di LocalWP email tidak terkirim sungguhan; lihat tab "Mailpit".
 * Di server, pastikan SMTP berfungsi (mis. plugin WP Mail SMTP).
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Kirim email sederhana berawalan nama situs.
 *
 * @param string|string[] $ke
 * @param string          $judul
 * @param string          $isi
 * @param string[]        $headers  Header tambahan (mis. Reply-To).
 * @param string[]        $lampiran Path file lampiran.
 * @return bool true bila wp_mail() berhasil.
 */
function tk_kirim_email( $ke, $judul, $isi, $headers = array(), $lampiran = array() ) {
    if ( ! $ke ) {
        return false;
    }
    return wp_mail( $ke, sprintf( '[%s] %s', get_bloginfo( 'name' ), $judul ), $isi, $headers, $lampiran );
}

/**
 * Beri tahu semua Kurator (atau admin bila belum ada kurator).
 *
 * @param int  $post_id
 * @param bool $ulang   true = kiriman ulang setelah revisi.
 */
function tk_email_ke_kurator( $post_id, $ulang = false ) {
    $emails = get_users( array( 'role__in' => array( 'kurator' ), 'fields' => 'user_email' ) );
    if ( ! $emails ) {
        $emails = array( get_option( 'admin_email' ) );
    }

    tk_kirim_email(
        $emails,
        ( $ulang ? 'Revisi masuk: ' : 'Kiriman baru: ' ) . get_the_title( $post_id ),
        sprintf(
            "%s\n\nJudul    : %s\nPengirim : %s%s\n\nTinjau di Dashboard Kurasi:\n%s\n",
            $ulang ? 'Kontributor sudah mengirim ulang tradisi yang diminta revisi.' : 'Ada tradisi baru yang menunggu kurasi.',
            get_the_title( $post_id ),
            tk_nama_pengirim( $post_id ),
            tk_is_kiriman_tamu( $post_id ) ? ' (tamu, ' . tk_email_pengirim( $post_id ) . ')' : '',
            tk_url_kurasi()
        )
    );
}

/**
 * Beri tahu pengirim tentang hasil kurasi atau penerimaan kiriman.
 *
 * @param int    $post_id
 * @param string $jenis   'diterima' | 'terbitkan' | 'revisi' | 'tolak'
 * @param string $catatan Catatan kurator (untuk revisi/tolak).
 */
function tk_email_ke_pengirim( $post_id, $jenis, $catatan = '' ) {
    $judul = get_the_title( $post_id );
    $halo  = 'Halo ' . tk_nama_pengirim( $post_id ) . ",\n\n";

    switch ( $jenis ) {
        case 'diterima':
            $subjek = 'Kiriman Anda kami terima: ' . $judul;
            $isi    = $halo . "Terima kasih. Tradisi \"$judul\" sudah kami terima dan akan ditinjau kurator. Kami akan mengabari Anda lewat email ini.\n";
            break;

        case 'terbitkan':
            $subjek = 'Tradisi Anda sudah terbit: ' . $judul;
            $isi    = $halo . "Tradisi \"$judul\" sudah ditinjau kurator dan kini terbit di:\n" . get_permalink( $post_id ) . "\n\nTerima kasih atas kontribusi Anda.\n";
            break;

        case 'revisi':
            $subjek = 'Mohon revisi: ' . $judul;
            $isi    = $halo . "Kurator meminta beberapa perbaikan untuk tradisi \"$judul\":\n\n$catatan\n\n"
                . "Silakan perbaiki lalu kirim ulang melalui link berikut:\n" . tk_url_revisi( $post_id ) . "\n"
                . ( tk_is_kiriman_tamu( $post_id ) ? "\nLink ini bersifat pribadi, jangan dibagikan.\n" : '' );
            break;

        case 'tolak':
            $subjek = 'Hasil kurasi: ' . $judul;
            $isi    = $halo . "Mohon maaf, tradisi \"$judul\" belum dapat kami terbitkan.\n\nAlasan kurator:\n$catatan\n\nTerima kasih atas partisipasi Anda.\n";
            break;

        default:
            return;
    }

    tk_kirim_email( tk_email_pengirim( $post_id ), $subjek, $isi );
}
