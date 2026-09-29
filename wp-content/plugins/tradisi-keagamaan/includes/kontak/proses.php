<?php
/**
 * Pemrosesan form Hubungi Kami [tk_kontak].
 *
 * Alur:
 *   template_redirect  tk_kontak_proses()  nonce, honeypot, batas per IP,
 *                                          validasi, kirim email ke admin
 *   Berhasil  → redirect ke ?kontak=terkirim (mencegah kirim ganda saat refresh).
 *   Gagal     → form tampil lagi dengan pesan & isian sebelumnya
 *               (disimpan di $GLOBALS['tk_kontak'], dibaca shortcodes/kontak.php).
 *
 * Penerima diatur TK_KONTAK_EMAIL di config.php. Balasan (Reply-To) langsung
 * ke email pengirim.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Pilihan "Perihal" di form.
 *
 * @return array Kunci => label.
 */
function tk_kontak_perihal() {
    return array(
        'umum'       => 'Pertanyaan umum',
        'koreksi'    => 'Koreksi data tradisi',
        'kontribusi' => 'Bantuan kontribusi',
        'kerja_sama' => 'Kerja sama',
        'lainnya'    => 'Lainnya',
    );
}

add_action( 'template_redirect', 'tk_kontak_proses' );

/**
 * Proses kiriman form Hubungi Kami.
 */
function tk_kontak_proses() {
    if ( ! isset( $_POST['tk_kontak_nonce'] ) ) {
        return;
    }

    $data = array(
        'nama'    => isset( $_POST['tk_nama'] ) ? sanitize_text_field( wp_unslash( $_POST['tk_nama'] ) ) : '',
        'email'   => isset( $_POST['tk_email'] ) ? sanitize_email( wp_unslash( $_POST['tk_email'] ) ) : '',
        'perihal' => isset( $_POST['tk_perihal'] ) ? sanitize_key( $_POST['tk_perihal'] ) : '',
        'pesan'   => isset( $_POST['tk_pesan'] ) ? sanitize_textarea_field( wp_unslash( $_POST['tk_pesan'] ) ) : '',
    );
    $GLOBALS['tk_kontak'] = array( 'data' => $data, 'galat' => array() );

    if ( ! wp_verify_nonce( sanitize_key( $_POST['tk_kontak_nonce'] ), 'tk_kontak' ) ) {
        $GLOBALS['tk_kontak']['galat'][] = 'Sesi form kedaluwarsa. Muat ulang halaman lalu coba lagi.';
        return;
    }

    // Honeypot: kolom tersembunyi yang hanya diisi bot. Pura-pura berhasil.
    if ( ! empty( $_POST['tk_situs'] ) ) {
        tk_kontak_selesai();
    }

    $galat = tk_kontak_validasi( $data );
    if ( ! $galat && ! tk_kontak_cek_batas() ) {
        $galat[] = 'Terlalu banyak pesan dari jaringan Anda. Silakan coba lagi dalam satu jam.';
    }
    if ( ! $galat && ! tk_kontak_kirim( $data ) ) {
        $galat[] = 'Pesan gagal dikirim karena gangguan server. Silakan coba lagi nanti.';
    }

    if ( $galat ) {
        $GLOBALS['tk_kontak']['galat'] = $galat;
        return;
    }
    tk_kontak_selesai();
}

/**
 * @param array $data Isian yang sudah disanitasi.
 * @return string[] Pesan galat; kosong = valid.
 */
function tk_kontak_validasi( $data ) {
    $galat = array();

    if ( '' === $data['nama'] ) {
        $galat[] = 'Nama wajib diisi.';
    }
    if ( ! is_email( $data['email'] ) ) {
        $galat[] = 'Alamat email tidak valid.';
    }
    if ( ! isset( tk_kontak_perihal()[ $data['perihal'] ] ) ) {
        $galat[] = 'Pilih perihal pesan.';
    }
    if ( mb_strlen( $data['pesan'] ) < 10 ) {
        $galat[] = 'Pesan terlalu pendek (minimal 10 karakter).';
    }
    return $galat;
}

/**
 * Batasi jumlah pesan per jam per alamat IP.
 *
 * @return bool false bila batas terlampaui.
 */
function tk_kontak_cek_batas() {
    $ip     = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
    $kunci  = 'tk_kontak_' . md5( $ip );
    $hitung = (int) get_transient( $kunci );

    if ( $hitung >= TK_KONTAK_BATAS_PER_JAM ) {
        return false;
    }
    set_transient( $kunci, $hitung + 1, HOUR_IN_SECONDS );
    return true;
}

/**
 * Kirim pesan ke admin, dengan Reply-To ke pengirim.
 *
 * @param array $data
 * @return bool
 */
function tk_kontak_kirim( $data ) {
    $ke      = TK_KONTAK_EMAIL ? TK_KONTAK_EMAIL : get_option( 'admin_email' );
    $perihal = tk_kontak_perihal()[ $data['perihal'] ];
    $nama    = str_replace( array( '"', '<', '>', ',', "\r", "\n" ), '', $data['nama'] ); // Aman untuk header.

    return tk_kirim_email(
        $ke,
        'Hubungi Kami: ' . $perihal,
        sprintf(
            "Pesan baru dari form Hubungi Kami.\n\nNama    : %s\nEmail   : %s\nPerihal : %s\n\n%s\n\n--\nBalas email ini untuk menjawab langsung ke pengirim.\n",
            $data['nama'],
            $data['email'],
            $perihal,
            $data['pesan']
        ),
        array( sprintf( 'Reply-To: %s <%s>', $nama, $data['email'] ) )
    );
}

/**
 * Kembali ke halaman form dengan tanda terkirim.
 */
function tk_kontak_selesai() {
    wp_safe_redirect( add_query_arg( 'kontak', 'terkirim', get_permalink() ) . '#tk-kontak' );
    exit;
}
