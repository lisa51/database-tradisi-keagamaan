<?php
/**
 * Data & aturan kurasi: pengirim, status, kelengkapan, dan aksi yang diizinkan.
 *
 *   Pengirim     tk_is_kiriman_tamu(), tk_nama_pengirim(), tk_email_pengirim()
 *                + filter 'the_author' agar nama tamu tampil sebagai penulis.
 *   Status       tk_status_kiriman(): label & warna, termasuk "Perlu Revisi".
 *   Kelengkapan  tk_kelengkapan(): checklist untuk kurator.
 *   Aksi         tk_aksi_diizinkan(), tk_aksi_wajib_catatan()
 *
 * Post meta terkait (awalan _ = tersembunyi dari panel Custom Fields):
 *   tk_tamu_nama, tk_tamu_email, tk_tamu_instansi   identitas kontributor tamu
 *   _tk_perlu_revisi   1 = dikembalikan kurator untuk direvisi
 *   _tk_catatan        catatan revisi / alasan tolak terakhir
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =============================================================================
 * Pengirim (akun atau tamu)
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
 * Status
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
 * Kelengkapan
 * ========================================================================== */

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
        'Kabupaten/kota'                      => '' !== trim( (string) get_post_meta( $post_id, 'asal_daerah', true ) ),
        'Provinsi'                            => '' !== tk_term_names( $post_id, 'wilayah' ),
        'Kategori'                            => '' !== tk_term_names( $post_id, 'kategori-tradisi' ),
        'Koordinat'                           => function_exists( 'tk_peta_get_koordinat' ) && null !== tk_peta_get_koordinat( $post_id ),
    );
}

/* =============================================================================
 * Aksi kurasi
 * ========================================================================== */

/**
 * Aksi kurasi yang boleh dilakukan dari setiap status.
 *
 *   terbitkan  Terbitkan                    (dari: menunggu, draf/perlu revisi)
 *   revisi     Minta revisi ke pengirim     (dari: menunggu, terbit)
 *   antrean    Kembalikan ke antrean kurasi (dari: terbit, draf/perlu revisi)
 *   tolak      Tolak & pindah ke Trash      (dari: menunggu, terbit, draf)
 *
 * @param string $status Status post saat ini.
 * @return string[]
 */
function tk_aksi_diizinkan( $status ) {
    $peta = array(
        'pending' => array( 'terbitkan', 'revisi', 'tolak' ),
        'publish' => array( 'antrean', 'revisi', 'tolak' ),
        'draft'   => array( 'terbitkan', 'antrean', 'tolak' ),
    );
    return isset( $peta[ $status ] ) ? $peta[ $status ] : array();
}

/**
 * Aksi yang wajib disertai catatan.
 *
 * @return string[]
 */
function tk_aksi_wajib_catatan() {
    return array( 'revisi', 'tolak', 'antrean' );
}
