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
 * Mengubah koleksi (halaman Ubah Tradisi, ?edit=ID): lihat tk_form_boleh_edit().
 * Koleksi terbit milik kontributor diubah lewat usulan (includes/kontribusi/usulan.php).
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
 * Pengguna login boleh membuka koleksi di form bila:
 *   Kurator/admin  koleksi apa pun berstatus draf, menunggu, atau terbit.
 *   Kontributor    miliknya sendiri berstatus draf (termasuk perlu revisi)
 *                  atau menunggu kurasi. Koleksi terbit miliknya diubah lewat
 *                  usulan perubahan (includes/kontribusi/usulan.php).
 *
 * @param int $post_id
 * @return bool
 */
function tk_form_boleh_edit( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post || 'tradisi' !== $post->post_type ) {
        return false;
    }
    if ( current_user_can( 'tk_kurasi' ) && current_user_can( 'edit_post', $post_id ) ) {
        return in_array( $post->post_status, array( 'draft', 'pending', 'publish' ), true );
    }
    return in_array( $post->post_status, array( 'draft', 'pending' ), true )
        && (int) $post->post_author === get_current_user_id();
}

/**
 * Kurator menyunting langsung (tanpa mengubah status): koleksi yang sudah
 * terbit/menunggu, atau milik orang lain. Tombolnya "Simpan Perubahan".
 *
 * @param int $post_id
 * @return bool
 */
function tk_form_ubah_langsung( $post_id ) {
    $post = get_post( $post_id );
    return $post
        && current_user_can( 'tk_kurasi' )
        && current_user_can( 'edit_post', $post_id )
        && ( 'draft' !== $post->post_status || (int) $post->post_author !== get_current_user_id() );
}

/**
 * Kontributor boleh mengusulkan perubahan (usulan, includes/kontribusi/usulan.php)
 * untuk koleksi terbit miliknya. Kurator tidak: ia menyunting langsung.
 *
 * @param int $post_id
 * @return bool
 */
function tk_form_boleh_usul( $post_id ) {
    return is_user_logged_in()
        && current_user_can( 'tk_kirim' )
        && 'tradisi' === get_post_type( $post_id )
        && 'publish' === get_post_status( $post_id )
        && (int) get_post_field( 'post_author', $post_id ) === get_current_user_id()
        && ! tk_form_boleh_edit( $post_id );
}

/**
 * Status yang diminta tombol yang diklik:
 *   'draft'    Simpan Draf
 *   'pending'  Kirim untuk Dikurasi / Simpan Perubahan (kiriman yang menunggu)
 *   'tetap'    Simpan Perubahan oleh kurator, status tidak diubah
 * Tamu selalu 'pending' (tidak punya draf).
 *
 * @return string
 */
function tk_form_status_diminta() {
    if ( ! is_user_logged_in() ) {
        return 'pending';
    }
    $status = isset( $_POST['tk_status'] ) ? sanitize_key( $_POST['tk_status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- nonce dicek ACF.
    return in_array( $status, array( 'draft', 'tetap' ), true ) ? $status : 'pending';
}

/* =============================================================================
 * Sebelum disimpan
 * ========================================================================== */

add_action( 'template_redirect', 'tk_form_head' );

/**
 * Di halaman yang berisi [tk_form_tradisi]:
 *   1. Tambah Tradisi?edit=ID → dialihkan ke Ubah Tradisi (link lama tetap jalan).
 *   2. Kontributor membuka Ubah pada koleksi terbit miliknya → dibuatkan /
 *      dibuka usulan perubahan, lalu dialihkan ke ?edit=<usulan>.
 *   3. acf_form_head() (wajib sebelum header halaman dicetak).
 */
function tk_form_head() {
    if ( ! function_exists( 'acf_form_head' ) || ! is_singular() ) {
        return;
    }
    $post = get_post();
    if ( ! $post || ! has_shortcode( $post->post_content, 'tk_form_tradisi' ) ) {
        return;
    }

    // phpcs:disable WordPress.Security.NonceVerification -- hanya memilih halaman; izin dicek di bawah & di tk_form_guard().
    $edit = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
    if ( $edit && empty( $_POST ) ) {
        if ( TK_SLUG_TAMBAH === $post->post_name ) {
            $args = array_intersect_key( wp_unslash( $_GET ), array_flip( array( 'edit', 'token' ) ) );
            wp_safe_redirect( add_query_arg( array_map( 'rawurlencode', $args ), tk_url_ubah() ) );
            exit;
        }

        if ( tk_form_boleh_usul( $edit ) ) {
            $usulan = tk_usulan_cari( $edit, get_current_user_id() );
            if ( ! $usulan ) {
                $usulan = tk_usulan_buat( $edit );
            }
            if ( $usulan ) {
                wp_safe_redirect( tk_url_ubah( $usulan ) );
                exit;
            }
        }
    }
    // phpcs:enable

    acf_form_head();
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
            wp_die( 'Akun Anda tidak memiliki izin untuk berkontribusi.', 403 );
        }
        if ( ! $baru && ! tk_form_boleh_edit( $post_id ) ) {
            wp_die( 'Anda tidak berhak mengubah koleksi ini.', 403 );
        }
        if ( 'tetap' === tk_form_status_diminta() && ( $baru || ! tk_form_ubah_langsung( $post_id ) ) ) {
            wp_die( 'Aksi simpan ini hanya untuk kurator.', 403 );
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
    if ( ! tk_batas_per_ip( 'tk_tamu', TK_TAMU_BATAS_PER_JAM ) ) {
        wp_die( 'Terlalu banyak kiriman dari jaringan Anda. Silakan coba lagi dalam satu jam.', 429 );
    }
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

    // Status sesuai tombol. Tradisi yang sudah terbit, dan "tetap" (kurator), tidak disentuh.
    if ( 'tetap' !== $diminta && in_array( $sebelum, array( 'draft', 'pending' ), true ) && $sebelum !== $diminta ) {
        wp_update_post( array( 'ID' => $post_id, 'post_status' => $diminta ) );
    }

    // Foto utama → featured image.
    $foto = absint( get_post_meta( $post_id, 'foto_utama', true ) );
    if ( $foto ) {
        set_post_thumbnail( $post_id, $foto );
    }

    // Foto Tambahan → Galeri Foto.
    tk_form_pindahkan_galeri( $post_id );

    // Kata kunci "a, b, c" → Tags. Dikosongkan di form = Tags dihapus.
    $tags = array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $post_id, 'kata_kunci', true ) ) ) );
    if ( $tags || ( ! $baru && isset( $_POST['acf']['field_tk_form_kata_kunci'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- nonce dicek ACF.
        wp_set_post_terms( $post_id, $tags, 'post_tag', false );
    }

    // Disunting tanpa pindah status (kurator, atau kontributor pada kiriman yang menunggu).
    if ( ! $baru && ( 'tetap' === $diminta || ( 'pending' === $diminta && 'pending' === $sebelum ) ) ) {
        tk_log_tambah( $post_id, 'ubah' );
        return;
    }

    // Baru dikirim (atau dikirim ulang setelah draf/revisi) → catat & beri tahu.
    if ( 'pending' === $diminta && ( $baru || 'draft' === $sebelum ) ) {
        $ulang = (bool) get_post_meta( $post_id, '_tk_perlu_revisi', true );

        delete_post_meta( $post_id, '_tk_perlu_revisi' );
        delete_post_meta( $post_id, '_tk_token' ); // Link revisi lama tidak berlaku lagi.

        tk_log_tambah( $post_id, $ulang ? 'kirim_ulang' : 'kirim', '', tk_nama_pengirim( $post_id ) );
        tk_email_ke_kurator( $post_id, $ulang );
        tk_email_ke_pengirim( $post_id, $ulang ? 'diterima_ulang' : 'diterima' ); // Salinan untuk pengirim.
    }
}

/**
 * Pindahkan foto dari "Foto Tambahan" (galeri_unggah, form depan) ke
 * "Galeri Foto" (galeri_foto), lalu kosongkan galeri_unggah.
 * Foto ditambahkan di belakang foto yang sudah ada, maks. TK_GALERI_MAKS.
 *
 * Hanya foto yang terhubung ke tradisi ini (post_parent) yang diterima. ACF
 * menghubungkan foto yang baru diunggah secara otomatis, sehingga ID lampiran
 * lain yang disisipkan ke form diabaikan.
 *
 * @param int $post_id
 */
function tk_form_pindahkan_galeri( $post_id ) {
    $baris = get_field( 'galeri_unggah', $post_id, false ); // Nilai mentah: array baris [ field_key => ID ].
    delete_field( 'galeri_unggah', $post_id );
    if ( ! is_array( $baris ) || ! $baris ) {
        return;
    }

    $baru = array();
    foreach ( $baris as $row ) {
        $id = is_array( $row ) ? absint( reset( $row ) ) : 0;
        if ( $id && wp_attachment_is_image( $id ) && (int) get_post_field( 'post_parent', $id ) === (int) $post_id ) {
            $baru[] = $id;
        }
    }
    if ( ! $baru ) {
        return;
    }

    $ada    = tk_get_galeri_ids( get_post_meta( $post_id, 'galeri_foto', true ) );
    $galeri = array_slice( array_values( array_unique( array_merge( $ada, $baru ) ) ), 0, TK_GALERI_MAKS );
    update_field( 'galeri_foto', array_map( 'strval', $galeri ), $post_id );
}
