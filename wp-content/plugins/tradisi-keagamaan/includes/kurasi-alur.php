<?php
/**
 * Alur kurasi: data & aturan bersama untuk form kontributor dan Dashboard Kurasi.
 *
 * Isi:
 *   1. Pengirim      tk_nama_pengirim(), tk_email_pengirim(), tk_is_kiriman_tamu()
 *   2. Status        tk_status_kiriman(): label + warna, termasuk "Perlu Revisi"
 *   3. Kelengkapan   tk_kelengkapan(): checklist foto, abstrak, sumber, dst.
 *   4. Riwayat       tk_log_tambah(), tk_log_get(): siapa melakukan apa, kapan
 *   5. Token revisi  tk_token_buat(), tk_token_cocok(): link edit untuk tamu
 *   6. Email         pemberitahuan ke kurator & pengirim
 *   7. wp-admin      kotak "Riwayat Kurasi" di editor tradisi
 *
 * Post meta yang dipakai (awalan _ = tersembunyi dari panel Custom Fields):
 *   tk_tamu_nama, tk_tamu_email, tk_tamu_instansi   Identitas kontributor tamu.
 *   _tk_log            Array riwayat kurasi.
 *   _tk_perlu_revisi   1 = dikembalikan kurator untuk direvisi.
 *   _tk_catatan        Catatan revisi/alasan tolak terakhir dari kurator.
 *   _tk_token          Token rahasia link revisi untuk tamu.
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================================================
 * 1. Pengirim (akun atau tamu)
 * ========================================================================== */

/**
 * Apakah tradisi ini dikirim oleh kontributor tamu (tanpa akun)?
 *
 * @param int $post_id
 * @return bool
 */
function tk_is_kiriman_tamu( $post_id ) {
    return '' !== (string) get_post_meta( $post_id, 'tk_tamu_email', true );
}

/**
 * Nama pengirim: nama yang diisi tamu, atau nama tampilan akun.
 *
 * @param int $post_id
 * @return string
 */
function tk_nama_pengirim( $post_id ) {
    if ( tk_is_kiriman_tamu( $post_id ) ) {
        return (string) get_post_meta( $post_id, 'tk_tamu_nama', true );
    }
    return (string) get_the_author_meta( 'display_name', get_post_field( 'post_author', $post_id ) );
}

/**
 * Email pengirim (untuk pemberitahuan). Tidak pernah ditampilkan ke publik.
 *
 * @param int $post_id
 * @return string
 */
function tk_email_pengirim( $post_id ) {
    if ( tk_is_kiriman_tamu( $post_id ) ) {
        return (string) get_post_meta( $post_id, 'tk_tamu_email', true );
    }
    return (string) get_the_author_meta( 'user_email', get_post_field( 'post_author', $post_id ) );
}

add_filter( 'the_author', 'tk_filter_nama_penulis' );

/**
 * Di halaman depan, tampilkan nama tamu (bukan nama akun sistem
 * "Kontributor Tamu") sebagai penulis tradisi kiriman tamu.
 *
 * @param string $nama
 * @return string
 */
function tk_filter_nama_penulis( $nama ) {
    $id = get_the_ID();
    if ( ! is_admin() && $id && 'tradisi' === get_post_type( $id ) && tk_is_kiriman_tamu( $id ) ) {
        return tk_nama_pengirim( $id );
    }
    return $nama;
}

/* =============================================================================
 * 2. Status kiriman
 * ========================================================================== */

/**
 * Label & kelas warna status sebuah tradisi.
 * Draf yang dikembalikan kurator ditampilkan sebagai "Perlu Revisi".
 *
 * @param WP_Post $post
 * @return string[] array( label, modifier class untuk .tk-status--* )
 */
function tk_status_kiriman( $post ) {
    if ( 'draft' === $post->post_status && get_post_meta( $post->ID, '_tk_perlu_revisi', true ) ) {
        return array( 'Perlu Revisi', 'revisi' );
    }

    $daftar = array(
        'pending' => array( 'Menunggu Kurasi', 'pending' ),
        'publish' => array( 'Terpublikasi', 'publish' ),
        'draft'   => array( 'Draf', 'draft' ),
        'trash'   => array( 'Ditolak', 'trash' ),
    );
    return isset( $daftar[ $post->post_status ] ) ? $daftar[ $post->post_status ] : array( $post->post_status, 'draft' );
}

/* =============================================================================
 * 3. Checklist kelengkapan
 * ========================================================================== */

/** Jumlah kata minimum isi artikel agar dianggap "cukup". */
define( 'TK_MIN_KATA_ISI', 150 );

/**
 * Checklist kelengkapan kiriman, untuk membantu kurator.
 * Ubah daftar di sini untuk menambah/mengurangi kriteria.
 *
 * @param int $post_id
 * @return bool[] Label => terpenuhi?
 */
function tk_kelengkapan( $post_id ) {
    $isi = wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) );

    return array(
        'Foto utama'                          => has_post_thumbnail( $post_id ),
        'Abstrak'                             => '' !== trim( (string) get_post_meta( $post_id, 'deskripsi_singkat', true ) ),
        'Isi ≥' . TK_MIN_KATA_ISI . ' kata'   => count( preg_split( '/\s+/u', trim( $isi ), -1, PREG_SPLIT_NO_EMPTY ) ) >= TK_MIN_KATA_ISI,
        'Sumber'                              => '' !== trim( (string) get_post_meta( $post_id, 'sumber_referensi', true ) ),
        'Asal daerah'                         => '' !== trim( (string) get_post_meta( $post_id, 'asal_daerah', true ) ),
        'Wilayah'                             => '' !== tk_term_names( $post_id, 'wilayah' ),
        'Kategori'                            => '' !== tk_term_names( $post_id, 'kategori-tradisi' ),
        'Koordinat'                           => function_exists( 'tk_peta_get_koordinat' ) && null !== tk_peta_get_koordinat( $post_id ),
    );
}

/* =============================================================================
 * 4. Riwayat kurasi
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
    );
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

    $log   = tk_log_get( $post_id );
    $log[] = array(
        'waktu'   => current_time( 'mysql' ),
        'aksi'    => $aksi,
        'oleh'    => $oleh,
        'catatan' => $catatan,
    );
    update_post_meta( $post_id, '_tk_log', $log );
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
 * HTML daftar riwayat (dipakai dashboard & wp-admin).
 *
 * @param int $post_id
 * @return string
 */
function tk_log_render( $post_id ) {
    $log = tk_log_get( $post_id );
    if ( ! $log ) {
        return '<p class="tk-log-kosong">Belum ada riwayat.</p>';
    }

    $label = tk_log_label();
    $html  = '<ol class="tk-log">';
    foreach ( array_reverse( $log ) as $baris ) {
        $html .= sprintf(
            '<li class="tk-log--%1$s"><strong>%2$s</strong> oleh %3$s <span class="tk-log-waktu">%4$s</span>%5$s</li>',
            esc_attr( $baris['aksi'] ),
            esc_html( isset( $label[ $baris['aksi'] ] ) ? $label[ $baris['aksi'] ] : $baris['aksi'] ),
            esc_html( $baris['oleh'] ),
            esc_html( mysql2date( 'j M Y, H:i', $baris['waktu'] ) ),
            $baris['catatan'] ? '<blockquote>' . esc_html( $baris['catatan'] ) . '</blockquote>' : ''
        );
    }
    return $html . '</ol>';
}

/* =============================================================================
 * 5. Token revisi untuk kontributor tamu
 * ========================================================================== */

/**
 * Buat token baru (menggantikan yang lama) dan kembalikan nilainya.
 *
 * @param int $post_id
 * @return string
 */
function tk_token_buat( $post_id ) {
    $token = wp_generate_password( 32, false );
    update_post_meta( $post_id, '_tk_token', $token );
    return $token;
}

/**
 * Apakah token cocok dan tradisi masih menunggu revisi (status draf)?
 *
 * @param int    $post_id
 * @param string $token
 * @return bool
 */
function tk_token_cocok( $post_id, $token ) {
    $simpan = (string) get_post_meta( $post_id, '_tk_token', true );

    return '' !== $simpan
        && '' !== $token
        && hash_equals( $simpan, $token )
        && 'draft' === get_post_status( $post_id );
}

/**
 * URL untuk melanjutkan/merevisi kiriman.
 * Akun → form?edit=ID. Tamu → form?edit=ID&token=... (token baru dibuat).
 *
 * @param int $post_id
 * @return string
 */
function tk_url_revisi( $post_id ) {
    $args = array( 'edit' => $post_id );
    if ( tk_is_kiriman_tamu( $post_id ) ) {
        $args['token'] = tk_token_buat( $post_id );
    }
    return add_query_arg( $args, tk_url_tambah() );
}

/* =============================================================================
 * 6. Email pemberitahuan
 *    Di LocalWP, email tidak terkirim sungguhan; lihat tab "Mailpit".
 * ========================================================================== */

/**
 * Kirim email sederhana berawalan nama situs.
 *
 * @param string|string[] $ke
 * @param string          $judul
 * @param string          $isi
 */
function tk_kirim_email( $ke, $judul, $isi ) {
    if ( ! $ke ) {
        return;
    }
    wp_mail( $ke, sprintf( '[%s] %s', get_bloginfo( 'name' ), $judul ), $isi );
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

/* =============================================================================
 * 7. wp-admin: kotak "Riwayat Kurasi" & pencatatan terbit dari editor
 * ========================================================================== */

add_action( 'add_meta_boxes_tradisi', 'tk_meta_box_riwayat' );

/** Daftarkan kotak Riwayat Kurasi di sidebar editor tradisi. */
function tk_meta_box_riwayat() {
    add_meta_box( 'tk-riwayat', 'Pengirim & Riwayat Kurasi', 'tk_meta_box_riwayat_isi', 'tradisi', 'side' );
}

/** @param WP_Post $post */
function tk_meta_box_riwayat_isi( $post ) {
    printf(
        '<p><strong>%s</strong>%s<br><a href="mailto:%3$s">%3$s</a></p>',
        esc_html( tk_nama_pengirim( $post->ID ) ),
        tk_is_kiriman_tamu( $post->ID ) ? ' (tamu)' : '',
        esc_attr( tk_email_pengirim( $post->ID ) )
    );
    $instansi = get_post_meta( $post->ID, 'tk_tamu_instansi', true );
    if ( $instansi ) {
        echo '<p>' . esc_html( $instansi ) . '</p>';
    }
    echo tk_log_render( $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput -- sudah di-escape di fungsi.
}

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
