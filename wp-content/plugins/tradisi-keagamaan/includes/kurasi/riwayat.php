<?php
/**
 * Riwayat kurasi: siapa melakukan apa, kapan, dan catatannya.
 *
 * Disimpan di post meta _tk_log (array). Setiap keputusan kurator juga
 * menandai _tk_dikurasi_oleh (satu baris meta per kurator) untuk daftar
 * "Riwayat Kurasi Saya".
 *
 * Hook otomatis:
 *   transition_post_status  terbit dari editor wp-admin tetap tercatat & pengirim diberi tahu
 *   untrashed_post          pemulihan dari Trash tercatat
 *   init (sekali jalan)     riwayat lama diberi penanda kurator lewat pencocokan nama
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================================================
 * Mencatat & membaca
 * ========================================================================== */

/**
 * Label untuk setiap jenis aksi di riwayat.
 *
 * @return string[]
 */
function tk_log_label() {
    return array(
        'kirim'      => 'Dikirim',
        'kirim_ulang'=> 'Dikirim ulang setelah revisi',
        'revisi'     => 'Diminta revisi',
        'tolak'      => 'Ditolak',
        'terbitkan'  => 'Diterbitkan',
        'pulihkan'   => 'Dipulihkan dari Trash',
        'antrean'    => 'Dikembalikan ke antrean kurasi',
    );
}

/**
 * Jenis aksi yang merupakan keputusan kurator.
 *
 * @return string[]
 */
function tk_log_aksi_kurator() {
    return array( 'terbitkan', 'revisi', 'tolak', 'antrean' );
}

/**
 * Tambah satu baris riwayat.
 *
 * @param int    $post_id
 * @param string $aksi    Kunci dari tk_log_label().
 * @param string $catatan Catatan kurator (opsional).
 * @param string $oleh    Nama pelaku. Kosong = pengguna yang sedang login.
 */
function tk_log_tambah( $post_id, $aksi, $catatan = '', $oleh = '' ) {
    if ( '' === $oleh ) {
        $user = wp_get_current_user();
        $oleh = $user->exists() ? $user->display_name : tk_nama_pengirim( $post_id );
    }

    $user_id = get_current_user_id();

    $log   = tk_log_get( $post_id );
    $log[] = array(
        'waktu'   => current_time( 'mysql' ),
        'aksi'    => $aksi,
        'oleh'    => $oleh,
        'user_id' => $user_id,
        'catatan' => $catatan,
    );
    update_post_meta( $post_id, '_tk_log', $log );

    // Keputusan kurator → tandai supaya muncul di "Riwayat Kurasi Saya".
    if ( $user_id && in_array( $aksi, tk_log_aksi_kurator(), true ) ) {
        tk_tandai_kurator( $post_id, $user_id );
    }
}

/**
 * Ambil seluruh riwayat, urut dari yang terlama.
 *
 * @param int $post_id
 * @return array[]
 */
function tk_log_get( $post_id ) {
    $log = get_post_meta( $post_id, '_tk_log', true );
    return is_array( $log ) ? $log : array();
}

/**
 * Catat bahwa kurator ini pernah memutuskan tradisi tersebut (sekali saja).
 *
 * @param int $post_id
 * @param int $user_id
 */
function tk_tandai_kurator( $post_id, $user_id ) {
    $sudah = array_map( 'intval', (array) get_post_meta( $post_id, '_tk_dikurasi_oleh', false ) );
    if ( ! in_array( (int) $user_id, $sudah, true ) ) {
        add_post_meta( $post_id, '_tk_dikurasi_oleh', (int) $user_id );
    }
}

/**
 * Keputusan terakhir seorang kurator pada sebuah tradisi.
 *
 * @param int $post_id
 * @param int $user_id
 * @return array|null Baris riwayat, atau null bila tidak ada.
 */
function tk_log_terakhir_oleh( $post_id, $user_id ) {
    foreach ( array_reverse( tk_log_get( $post_id ) ) as $baris ) {
        if ( in_array( $baris['aksi'], tk_log_aksi_kurator(), true ) && tk_log_milik( $baris, $user_id ) ) {
            return $baris;
        }
    }
    return null;
}

/**
 * Apakah baris riwayat ini dibuat oleh pengguna tersebut?
 * Riwayat lama (sebelum ada user_id) dicocokkan lewat nama tampilan.
 *
 * @param array $baris
 * @param int   $user_id
 * @return bool
 */
function tk_log_milik( $baris, $user_id ) {
    if ( ! empty( $baris['user_id'] ) ) {
        return (int) $baris['user_id'] === (int) $user_id;
    }
    $user = get_userdata( $user_id );
    return $user && $user->display_name === $baris['oleh'];
}

/* =============================================================================
 * Pencatatan otomatis
 * ========================================================================== */

add_action( 'transition_post_status', 'tk_catat_terbit_dari_editor', 10, 3 );

/**
 * Kalau kurator menerbitkan kiriman langsung dari editor wp-admin (bukan dari
 * dashboard), tetap catat di riwayat dan beri tahu pengirim.
 * Hanya untuk tradisi yang berasal dari form (punya riwayat).
 *
 * @param string  $baru
 * @param string  $lama
 * @param WP_Post $post
 */
function tk_catat_terbit_dari_editor( $baru, $lama, $post ) {
    if ( 'tradisi' !== $post->post_type || 'publish' !== $baru || 'publish' === $lama ) {
        return;
    }
    if ( ! empty( $GLOBALS['tk_aksi_dashboard'] ) || ! tk_log_get( $post->ID ) ) {
        return; // Sudah ditangani dashboard, atau bukan kiriman form.
    }
    tk_log_tambah( $post->ID, 'terbitkan', 'Diterbitkan dari editor.' );
    delete_post_meta( $post->ID, '_tk_perlu_revisi' );
    tk_email_ke_pengirim( $post->ID, 'terbitkan' );
}

add_action( 'untrashed_post', 'tk_catat_pulihkan' );

/**
 * Catat di riwayat bila kiriman yang ditolak dipulihkan dari Trash.
 *
 * @param int $post_id
 */
function tk_catat_pulihkan( $post_id ) {
    if ( 'tradisi' === get_post_type( $post_id ) && tk_log_get( $post_id ) ) {
        tk_log_tambah( $post_id, 'pulihkan' );
    }
}

add_action( 'init', 'tk_isi_ulang_penanda_kurator', 20 );

/**
 * Sekali jalan: tandai kurator pada riwayat yang dibuat sebelum fitur
 * "Riwayat Kurasi Saya" ada (riwayat lama belum menyimpan user_id).
 */
function tk_isi_ulang_penanda_kurator() {
    if ( get_option( 'tk_penanda_kurator_v1' ) ) {
        return;
    }

    $kurator = get_users( array( 'capability' => 'tk_kurasi', 'fields' => array( 'ID', 'display_name' ) ) );
    $ids     = get_posts( array(
        'post_type'      => 'tradisi',
        'post_status'    => array( 'publish', 'pending', 'draft', 'trash' ),
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_key'       => '_tk_log',
    ) );

    foreach ( $ids as $id ) {
        foreach ( tk_log_get( $id ) as $baris ) {
            if ( ! in_array( $baris['aksi'], tk_log_aksi_kurator(), true ) ) {
                continue;
            }
            foreach ( $kurator as $k ) {
                if ( $k->display_name === $baris['oleh'] ) {
                    tk_tandai_kurator( $id, $k->ID );
                }
            }
        }
    }

    update_option( 'tk_penanda_kurator_v1', 1 );
}
