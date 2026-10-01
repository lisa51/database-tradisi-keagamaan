<?php
/**
 * Pemrosesan form Hubungi Kami [tk_kontak].
 *
 * Alur:
 *   template_redirect  tk_kontak_proses()  nonce, honeypot, batas per IP,
 *                                          validasi, kirim email ke admin,
 *                                          lalu konfirmasi ke pengirim
 *   Berhasil  → redirect ke ?kontak=terkirim (mencegah kirim ganda saat refresh).
 *   Gagal     → form tampil lagi dengan pesan & isian sebelumnya
 *               (disimpan di $GLOBALS['tk_kontak'], dibaca shortcodes/kontak.php).
 *
 * Penerima diatur TK_KONTAK_EMAIL di config.php. Balasan (Reply-To) langsung
 * ke email pengirim.
 *
 * Lampiran (opsional, satu file): format & ukuran dicek terhadap
 * TK_KONTAK_LAMPIRAN_TIPE / TK_KONTAK_LAMPIRAN_MAKS_MB, lalu dikirim sebagai
 * lampiran email dan langsung dihapus. Tidak disimpan di Media Library.
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
        // Kiriman melebihi post_max_size: PHP mengosongkan $_POST tanpa pesan.
        if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' )
            && ! $_POST && ! empty( $_SERVER['CONTENT_LENGTH'] ) && is_singular()
            && has_shortcode( (string) get_post_field( 'post_content' ), 'tk_kontak' ) ) {
            $GLOBALS['tk_kontak'] = array(
                'data'  => array(),
                'galat' => array( 'Lampiran terlalu besar (maks. ' . TK_KONTAK_LAMPIRAN_MAKS_MB . ' MB).' ),
            );
        }
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

    if ( ! tk_turnstile_lolos( 'tk-kontak' ) ) {
        $GLOBALS['tk_kontak']['galat'][] = 'Verifikasi anti-bot gagal. Tunggu tanda centang muncul, lalu kirim lagi.';
        return;
    }

    $lampiran = tk_kontak_cek_lampiran();
    $galat    = tk_kontak_validasi( $data );
    if ( is_string( $lampiran ) ) {
        $galat[]  = $lampiran;
        $lampiran = null;
    }
    if ( ! $galat && ! tk_batas_per_ip( 'tk_kontak', TK_KONTAK_BATAS_PER_JAM ) ) {
        $galat[] = 'Terlalu banyak pesan dari jaringan Anda. Silakan coba lagi dalam satu jam.';
    }
    if ( ! $galat && ! tk_kontak_kirim( $data, $lampiran ) ) {
        $galat[] = 'Pesan gagal dikirim karena gangguan server. Silakan coba lagi nanti.';
    }

    if ( $galat ) {
        if ( $lampiran ) {
            $galat[] = 'Browser tidak menyimpan file yang sudah dipilih. Pilih ulang lampiran sebelum mengirim.';
        }
        $GLOBALS['tk_kontak']['galat'] = $galat;
        return;
    }
    tk_kontak_kirim_konfirmasi( $data );
    tk_kontak_selesai();
}

/**
 * Periksa lampiran yang diunggah (tanpa memindahkannya).
 *
 * @return array|string|null Data file dari $_FILES bila valid, pesan galat
 *                           bila tidak valid, null bila tidak ada lampiran.
 */
function tk_kontak_cek_lampiran() {
    // phpcs:ignore WordPress.Security.NonceVerification -- nonce sudah dicek tk_kontak_proses().
    $file = isset( $_FILES['tk_lampiran'] ) ? $_FILES['tk_lampiran'] : null;
    if ( ! $file || ! is_array( $file ) || ! isset( $file['error'] ) || is_array( $file['error'] ) || UPLOAD_ERR_NO_FILE === $file['error'] ) {
        return null;
    }

    $maks_mb = TK_KONTAK_LAMPIRAN_MAKS_MB;
    if ( in_array( $file['error'], array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true )
        || ( UPLOAD_ERR_OK === $file['error'] && $file['size'] > $maks_mb * MB_IN_BYTES ) ) {
        return "Lampiran terlalu besar (maks. $maks_mb MB).";
    }
    if ( UPLOAD_ERR_OK !== $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) ) {
        return 'Lampiran gagal diunggah. Silakan coba lagi.';
    }

    // Cek isi file, bukan hanya nama: ekstensi & MIME harus cocok dengan daftar.
    $cek = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], TK_KONTAK_LAMPIRAN_TIPE );
    if ( ! $cek['ext'] || ! $cek['type'] ) {
        return 'Format lampiran tidak didukung. Gunakan ' . tk_kontak_lampiran_label() . '.';
    }

    $file['ext'] = $cek['ext'];
    return $file;
}

/**
 * Daftar format lampiran untuk ditampilkan, mis. "JPG, PNG, PDF, atau DOCX".
 *
 * @return string
 */
function tk_kontak_lampiran_label() {
    $ext = array();
    foreach ( array_keys( TK_KONTAK_LAMPIRAN_TIPE ) as $kunci ) {
        $ext[] = strtoupper( strtok( $kunci, '|' ) );
    }
    $akhir = array_pop( $ext );
    return $ext ? implode( ', ', $ext ) . ', atau ' . $akhir : $akhir;
}

/**
 * Nilai atribut accept untuk input file, mis. ".jpg,.jpeg,.png,.pdf,.docx".
 *
 * @return string
 */
function tk_kontak_lampiran_accept() {
    return '.' . str_replace( '|', ',.', implode( '|', array_keys( TK_KONTAK_LAMPIRAN_TIPE ) ) );
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
 * Kirim pesan ke admin, dengan Reply-To ke pengirim dan lampiran bila ada.
 *
 * Lampiran dipindah ke folder sementara dengan nama aslinya (supaya nama file
 * di email tetap terbaca), lalu dihapus setelah email dikirim.
 *
 * @param array      $data
 * @param array|null $lampiran Hasil tk_kontak_cek_lampiran().
 * @return bool
 */
function tk_kontak_kirim( $data, $lampiran = null ) {
    $ke      = tk_email_tim();
    $perihal = tk_kontak_perihal()[ $data['perihal'] ];
    $nama    = str_replace( array( '"', '<', '>', ',', "\r", "\n" ), '', $data['nama'] ); // Aman untuk header.

    $folder = '';
    $path   = '';
    if ( $lampiran ) {
        $folder = trailingslashit( get_temp_dir() ) . 'tk-kontak-' . wp_generate_password( 12, false );
        $dasar  = sanitize_file_name( pathinfo( $lampiran['name'], PATHINFO_FILENAME ) );
        $path   = $folder . '/' . ( $dasar ? $dasar : 'lampiran' ) . '.' . $lampiran['ext'];

        if ( ! wp_mkdir_p( $folder ) || ! move_uploaded_file( $lampiran['tmp_name'], $path ) ) {
            if ( $folder && is_dir( $folder ) ) {
                rmdir( $folder );
            }
            return false;
        }
    }

    $terkirim = tk_kirim_email(
        $ke,
        'Hubungi Kami: ' . $perihal,
        sprintf(
            "Pesan baru dari form Hubungi Kami.\n\nNama     : %s\nEmail    : %s\nPerihal  : %s\nLampiran : %s\n\n%s\n\n--\nBalas email ini untuk menjawab langsung ke pengirim.\n",
            $data['nama'],
            $data['email'],
            $perihal,
            $path ? wp_basename( $path ) . ' (' . size_format( filesize( $path ) ) . ')' : '-',
            $data['pesan']
        ),
        array( sprintf( 'Reply-To: %s <%s>', $nama, $data['email'] ) ),
        $path ? array( $path ) : array()
    );

    if ( $path ) {
        wp_delete_file( $path );
        rmdir( $folder );
    }
    return $terkirim;
}

/**
 * Kirim konfirmasi singkat ke pengirim. Kegagalan di sini tidak membatalkan
 * pesan ke admin.
 *
 * Sengaja tanpa isian pengunjung (nama, pesan, nama lampiran): alamat tujuan
 * diketik pengunjung, jadi teks bebas di sini bisa dipakai mengirim spam ke
 * alamat orang lain atas nama situs. Perihal aman karena dari daftar tetap.
 *
 * @param array $data
 */
function tk_kontak_kirim_konfirmasi( $data ) {
    tk_kirim_email(
        $data['email'],
        'Pesan Anda sudah kami terima',
        sprintf(
            "Halo,\n\nTerima kasih telah menghubungi kami. Pesan Anda dengan perihal \"%s\" sudah kami terima dan akan kami balas melalui email ini.\n\n--\nEmail ini dikirim otomatis. Bila Anda tidak merasa mengirim pesan, abaikan saja.\n",
            tk_kontak_perihal()[ $data['perihal'] ]
        )
    );
}

/**
 * Kembali ke halaman form dengan tanda terkirim.
 */
function tk_kontak_selesai() {
    wp_safe_redirect( add_query_arg( 'kontak', 'terkirim', get_permalink() ) . '#tk-kontak' );
    exit;
}
