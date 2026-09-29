<?php
/**
 * Pemrosesan form depan [tk_form_tradisi]: siapa boleh mengirim, validasi,
 * dan apa yang terjadi setelah tersimpan.
 *
 * Alur penyimpanan:
 *   template_redirect  tk_form_head()          acf_form_head() di halaman form
 *   acf/pre_save_post  tk_form_guard()         izin (akun / tamu + token), batas kiriman tamu
 *   acf/validate_value tk_form_validasi_draf() "Simpan Draf" boleh tanpa field wajib
 *   acf/save_post      tk_form_after_save()    status, foto utama, tags, riwayat, email
 *
 * Mode form (tk_form_konteks()):
 *   akun        pengguna login dengan hak tk_kirim; bisa draf & lanjutkan (?edit=ID)
 *   tamu        pengunjung tanpa login; revisi lewat ?edit=ID&token=...
 *   tanpa_izin  login tapi tanpa hak tk_kirim
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================================================
 * Konteks & izin
 * ========================================================================== */

/**
 * Tentukan mode form dari pengguna & parameter URL.
 *
 * @return array {
 *     @type string $mode    'akun' | 'tamu' | 'tanpa_izin'
 *     @type int    $edit_id ID kiriman yang dilanjutkan/direvisi, 0 = kiriman baru.
 * }
 */
function tk_form_konteks() {
    // phpcs:disable WordPress.Security.NonceVerification -- hanya memilih data; izin dicek di bawah & di tk_form_guard().
    $edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
    $token   = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
    // phpcs:enable

    if ( is_user_logged_in() ) {
        if ( ! current_user_can( 'tk_kirim' ) ) {
            return array( 'mode' => 'tanpa_izin', 'edit_id' => 0 );
        }
        return array(
            'mode'    => 'akun',
            'edit_id' => ( $edit_id && tk_form_boleh_edit( $edit_id ) ) ? $edit_id : 0,
        );
    }

    return array(
        'mode'    => 'tamu',
        'edit_id' => ( $edit_id && tk_token_cocok( $edit_id, $token ) ) ? $edit_id : 0,
    );
}

/**
 * Pengguna login boleh membuka kiriman di form bila: tradisi, berstatus
 * draf (draf biasa atau perlu revisi), dan miliknya sendiri.
 *
 * @param int $post_id
 * @return bool
 */
function tk_form_boleh_edit( $post_id ) {
    $post = get_post( $post_id );

    return $post
        && 'tradisi' === $post->post_type
        && 'draft' === $post->post_status
        && (int) $post->post_author === get_current_user_id();
}

/**
 * Status yang diminta tombol yang diklik: 'draft' atau 'pending'.
 * Tamu selalu 'pending' (tidak punya draf).
 *
 * @return string
 */
function tk_form_status_diminta() {
    if ( ! is_user_logged_in() ) {
        return 'pending';
    }
    $status = isset( $_POST['tk_status'] ) ? sanitize_key( $_POST['tk_status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- nonce dicek ACF.
    return 'draft' === $status ? 'draft' : 'pending';
}

/* =============================================================================
 * Sebelum disimpan
 * ========================================================================== */

add_action( 'template_redirect', 'tk_form_head' );

/**
 * acf_form_head() wajib dipanggil sebelum header halaman dicetak.
 * Hanya di halaman yang berisi [tk_form_tradisi].
 */
function tk_form_head() {
    if ( ! function_exists( 'acf_form_head' ) || ! is_singular() ) {
        return;
    }
    $post = get_post();
    if ( $post && has_shortcode( $post->post_content, 'tk_form_tradisi' ) ) {
        acf_form_head();
    }
}

add_filter( 'acf/pre_save_post', 'tk_form_guard', 1 );

/**
 * Pengaman terakhir sebelum ACF menyimpan kiriman dari form depan.
 *
 * @param int|string $post_id 'new_post' atau ID kiriman yang diedit.
 * @return int|string
 */
function tk_form_guard( $post_id ) {
    if ( is_admin() ) {
        return $post_id; // Penyimpanan dari wp-admin tidak diubah.
    }

    $baru = ( 'new_post' === $post_id );

    if ( is_user_logged_in() ) {
        if ( ! current_user_can( 'tk_kirim' ) ) {
            wp_die( 'Akun Anda tidak memiliki izin untuk mengirim tradisi.', 403 );
        }
        if ( ! $baru && ! tk_form_boleh_edit( $post_id ) ) {
            wp_die( 'Anda tidak berhak mengubah tradisi ini.', 403 );
        }
    } else {
        $token = isset( $_REQUEST['token'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        if ( ! $baru && ! tk_token_cocok( $post_id, $token ) ) {
            wp_die( 'Link revisi tidak valid atau sudah kedaluwarsa.', 403 );
        }
        if ( $baru ) {
            if ( ! tk_turnstile_lolos( 'tk-tradisi' ) ) {
                wp_die( 'Verifikasi anti-bot gagal. Kembali ke form, tunggu tanda centang muncul, lalu kirim lagi.', 'Verifikasi gagal', array( 'response' => 403, 'back_link' => true ) );
            }
            tk_form_batasi_tamu();
        }
    }

    $GLOBALS['tk_form_baru'] = $baru; // Dipakai tk_form_after_save().
    return $post_id;
}

/**
 * Batasi jumlah kiriman tamu per jam per alamat IP.
 */
function tk_form_batasi_tamu() {
    $ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
    $kunci = 'tk_tamu_' . md5( $ip );
    $hitung = (int) get_transient( $kunci );

    if ( $hitung >= TK_TAMU_BATAS_PER_JAM ) {
        wp_die( 'Terlalu banyak kiriman dari jaringan Anda. Silakan coba lagi dalam satu jam.', 429 );
    }
    set_transient( $kunci, $hitung + 1, HOUR_IN_SECONDS );
}

add_filter( 'acf/validate_value', 'tk_form_validasi_draf', 20, 3 );

/**
 * "Simpan Draf" (khusus akun): field wajib boleh kosong kecuali Nama Tradisi.
 * Validasi ACF di browser dimatikan oleh assets/js/form.js.
 *
 * @param bool|string $valid
 * @param mixed       $value
 * @param array       $field
 * @return bool|string
 */
function tk_form_validasi_draf( $valid, $value, $field ) {
    if ( 'draft' === tk_form_status_diminta() && '_post_title' !== $field['name'] ) {
        return true;
    }
    return $valid;
}

/* =============================================================================
 * Setelah disimpan
 * ========================================================================== */

add_action( 'acf/save_post', 'tk_form_after_save', 20 );

/**
 * Setelah ACF menyimpan: atur status, foto, tags, riwayat, dan email.
 *
 * @param int|string $post_id
 */
function tk_form_after_save( $post_id ) {
    if ( is_admin() || ! is_numeric( $post_id ) || 'tradisi' !== get_post_type( $post_id ) ) {
        return;
    }

    $baru    = ! empty( $GLOBALS['tk_form_baru'] );
    $sebelum = get_post_status( $post_id );
    $diminta = tk_form_status_diminta();

    // Status sesuai tombol. Tradisi yang sudah terbit tidak disentuh.
    if ( in_array( $sebelum, array( 'draft', 'pending' ), true ) && $sebelum !== $diminta ) {
        wp_update_post( array( 'ID' => $post_id, 'post_status' => $diminta ) );
    }

    // Foto utama → featured image.
    $foto = absint( get_post_meta( $post_id, 'foto_utama', true ) );
    if ( $foto ) {
        set_post_thumbnail( $post_id, $foto );
    }

    // Kata kunci "a, b, c" → Tags.
    $tags = array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $post_id, 'kata_kunci', true ) ) ) );
    if ( $tags ) {
        wp_set_post_terms( $post_id, $tags, 'post_tag', false );
    }

    // Baru dikirim (atau dikirim ulang setelah draf/revisi) → catat & beri tahu.
    if ( 'pending' === $diminta && ( $baru || 'draft' === $sebelum ) ) {
        $ulang = (bool) get_post_meta( $post_id, '_tk_perlu_revisi', true );

        delete_post_meta( $post_id, '_tk_perlu_revisi' );
        delete_post_meta( $post_id, '_tk_token' ); // Link revisi lama tidak berlaku lagi.

        tk_log_tambah( $post_id, $ulang ? 'kirim_ulang' : 'kirim', '', tk_nama_pengirim( $post_id ) );
        tk_email_ke_kurator( $post_id, $ulang );

        if ( $baru ) {
            tk_email_ke_pengirim( $post_id, 'diterima' );
        }
    }
}
