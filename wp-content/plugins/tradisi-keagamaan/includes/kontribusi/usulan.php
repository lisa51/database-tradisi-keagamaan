<?php
/**
 * Usulan perubahan: kontributor mengubah koleksi miliknya yang SUDAH TERBIT.
 *
 * Versi terbit tidak disentuh sampai kurator menyetujui:
 *   1. Kontributor membuka Ubah pada koleksi terbit → dibuat salinan
 *      ("usulan", status draf, meta _tk_usulan_untuk = ID asli), atau
 *      usulan yang sudah ada dibuka lagi. Lihat tk_form_head().
 *   2. Usulan diisi & dikirim lewat form biasa → masuk antrean kurasi.
 *   3. Kurator: Terbitkan (= "Setujui & Terapkan") → isi usulan disalin ke
 *      versi asli (tk_usulan_terapkan()), lalu usulan dihapus.
 *      Minta Revisi / Tolak berlaku seperti kiriman biasa; versi asli tetap.
 *
 * Usulan tidak pernah terbit sendiri: status "publish" dari wp-admin ditahan
 * (tk_usulan_tahan_terbit()).
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Meta yang TIDAK ikut disalin antara asli dan usulan: data internal alur
 * kurasi, penghitung, identitas tamu, dan catatan sistem WordPress.
 *
 * @param string $key
 * @return bool
 */
function tk_usulan_meta_disalin( $key ) {
    $kecuali = array( TK_VIEW_META, '_thumbnail_id', 'tk_tamu_nama', 'tk_tamu_email', 'tk_tamu_instansi', 'tk_tamu_setuju',
        '_tk_tamu_nama', '_tk_tamu_email', '_tk_tamu_instansi', '_tk_tamu_setuju', 'galeri_unggah', '_galeri_unggah' );
    if ( in_array( $key, $kecuali, true ) ) {
        return false;
    }
    foreach ( array( '_tk_', '_edit_', '_wp_', '_encloseme', '_pingme' ) as $awalan ) {
        if ( 0 === strpos( $key, $awalan ) ) {
            return false;
        }
    }
    return true;
}

/**
 * Taxonomy yang ikut disalin.
 *
 * @return string[]
 */
function tk_usulan_taxonomy() {
    return array( 'jenis', 'agama', 'wilayah', 'kategori-tradisi', 'post_tag' );
}

/**
 * ID koleksi asli dari sebuah usulan, atau 0 bila bukan usulan.
 *
 * @param int $post_id
 * @return int
 */
function tk_usulan_asal( $post_id ) {
    return absint( get_post_meta( $post_id, '_tk_usulan_untuk', true ) );
}

/**
 * Usulan yang masih berjalan (draf/menunggu) milik seorang pengguna untuk koleksi asli.
 *
 * @param int $asal_id
 * @param int $user_id
 * @return int ID usulan, atau 0.
 */
function tk_usulan_cari( $asal_id, $user_id ) {
    $ids = get_posts( array(
        'post_type'      => 'tradisi',
        'post_status'    => array( 'draft', 'pending' ),
        'author'         => $user_id,
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_key'       => '_tk_usulan_untuk', // phpcs:ignore WordPress.DB.SlowDBQuery
        'meta_value'     => $asal_id,           // phpcs:ignore WordPress.DB.SlowDBQuery
    ) );
    return $ids ? (int) $ids[0] : 0;
}

/**
 * Salin isi (judul, artikel, ringkasan, meta, term, foto utama) dari satu
 * koleksi ke koleksi lain. Meta di tujuan yang tidak ada di sumber dihapus.
 *
 * @param int $dari
 * @param int $ke
 */
function tk_usulan_salin_isi( $dari, $ke ) {
    $sumber = get_post( $dari );

    wp_update_post( array(
        'ID'           => $ke,
        'post_title'   => $sumber->post_title,
        'post_content' => $sumber->post_content,
        'post_excerpt' => $sumber->post_excerpt,
    ) );

    $meta_sumber = get_post_meta( $dari );
    foreach ( array_keys( get_post_meta( $ke ) ) as $key ) {
        if ( tk_usulan_meta_disalin( $key ) && ! isset( $meta_sumber[ $key ] ) ) {
            delete_post_meta( $ke, $key );
        }
    }
    foreach ( $meta_sumber as $key => $nilai ) {
        if ( tk_usulan_meta_disalin( $key ) ) {
            // Nilai mentah (serialized) disalin apa adanya, tanpa memicu hook ACF.
            update_post_meta( $ke, $key, maybe_unserialize( $nilai[0] ) );
        }
    }

    foreach ( tk_usulan_taxonomy() as $tax ) {
        $ids = wp_get_object_terms( $dari, $tax, array( 'fields' => 'ids' ) );
        wp_set_object_terms( $ke, is_wp_error( $ids ) ? array() : array_map( 'intval', $ids ), $tax );
    }

    $foto = get_post_thumbnail_id( $dari );
    $foto ? set_post_thumbnail( $ke, $foto ) : delete_post_thumbnail( $ke );
}

/**
 * Buat usulan (salinan draf) dari koleksi terbit, atas nama pengguna yang login.
 *
 * @param int $asal_id
 * @return int ID usulan, atau 0 bila gagal.
 */
function tk_usulan_buat( $asal_id ) {
    $asal = get_post( $asal_id );
    $id   = wp_insert_post( array(
        'post_type'   => 'tradisi',
        'post_status' => 'draft',
        'post_author' => get_current_user_id(),
        'post_title'  => $asal->post_title,
    ), true );
    if ( is_wp_error( $id ) ) {
        return 0;
    }

    tk_usulan_salin_isi( $asal_id, $id );
    update_post_meta( $id, '_tk_usulan_untuk', $asal_id );
    return $id;
}

/**
 * Terapkan usulan ke versi asli, lalu hapus usulan.
 *
 * @param int $usulan_id
 * @return int ID koleksi asli, atau 0 bila bukan usulan.
 */
function tk_usulan_terapkan( $usulan_id ) {
    $asal_id = tk_usulan_asal( $usulan_id );
    if ( ! $asal_id || ! get_post( $asal_id ) ) {
        return 0;
    }

    $terkait_lama = (array) get_post_meta( $asal_id, 'terkait', true );
    tk_usulan_salin_isi( $usulan_id, $asal_id );

    // Tautan dua arah: jalankan lewat ACF agar sisi lawan ikut diperbarui.
    if ( function_exists( 'update_field' ) ) {
        $baru = (array) get_post_meta( $asal_id, 'terkait', true );
        update_post_meta( $asal_id, 'terkait', $terkait_lama ); // Nilai lama sebagai pembanding ACF.
        update_field( 'terkait', array_filter( $baru ), $asal_id );
    }

    // Foto yang diunggah ke usulan dipindah ke koleksi asli.
    foreach ( get_children( array( 'post_parent' => $usulan_id, 'post_type' => 'attachment', 'fields' => 'ids' ) ) as $foto ) {
        wp_update_post( array( 'ID' => $foto, 'post_parent' => $asal_id ) );
    }

    tk_log_tambah( $asal_id, 'ubah_diterapkan', 'Usulan dari ' . tk_nama_pengirim( $usulan_id ) . '.' );

    $GLOBALS['tk_usulan_diterapkan'] = true;
    wp_delete_post( $usulan_id, true );
    return $asal_id;
}

add_action( 'before_delete_post', 'tk_usulan_bersihkan_tautan' );
add_action( 'wp_trash_post', 'tk_usulan_bersihkan_tautan' );

/**
 * Hapus ID usulan dari field "terkait" koleksi lain. Tautan dua arah ACF
 * menambahkannya saat usulan disimpan; setelah usulan diterapkan/ditolak
 * ID itu tidak berlaku lagi.
 *
 * @param int $post_id
 */
function tk_usulan_bersihkan_tautan( $post_id ) {
    if ( ! tk_usulan_asal( $post_id ) ) {
        return;
    }
    $lain = get_posts( array(
        'post_type'      => 'tradisi',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => array( array( 'key' => 'terkait', 'value' => '"' . $post_id . '"', 'compare' => 'LIKE' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
    ) );
    foreach ( $lain as $id ) {
        $ids = array_values( array_diff( (array) get_post_meta( $id, 'terkait', true ), array( (string) $post_id, $post_id ) ) );
        update_post_meta( $id, 'terkait', $ids );
    }
}

/**
 * Daftar bagian yang berbeda antara usulan dan versi asli, untuk kurator.
 *
 * @param int $usulan_id
 * @return string[] Label bagian yang berubah.
 */
function tk_usulan_perubahan( $usulan_id ) {
    $asal_id = tk_usulan_asal( $usulan_id );
    if ( ! $asal_id ) {
        return array();
    }
    $a = get_post( $asal_id );
    $u = get_post( $usulan_id );

    $ubah = array();
    if ( $a->post_title !== $u->post_title ) {
        $ubah[] = 'Judul';
    }
    if ( trim( $a->post_content ) !== trim( $u->post_content ) ) {
        $ubah[] = 'Isi artikel';
    }
    if ( get_post_thumbnail_id( $asal_id ) !== get_post_thumbnail_id( $usulan_id ) ) {
        $ubah[] = 'Foto utama';
    }

    // Meta salinan field yang sebenarnya disimpan di taxonomy/featured image
    // dibandingkan lewat term & foto utama, bukan lewat meta-nya.
    $lewati = array( 'foto_utama', 'kata_kunci', 'jenis_warisan', 'tk_form_agama', 'tk_form_wilayah', 'tk_form_kategori', 'kategori_tradisi', 'kategori_material' );

    $ma = get_post_meta( $asal_id );
    $mu = get_post_meta( $usulan_id );
    foreach ( array_unique( array_merge( array_keys( $ma ), array_keys( $mu ) ) ) as $key ) {
        if ( 0 === strpos( $key, '_' ) || ! tk_usulan_meta_disalin( $key ) || in_array( $key, $lewati, true ) ) {
            continue;
        }
        $va = isset( $ma[ $key ] ) ? (string) $ma[ $key ][0] : '';
        $vu = isset( $mu[ $key ] ) ? (string) $mu[ $key ][0] : '';
        if ( $va !== $vu ) {
            $field  = function_exists( 'acf_get_field' ) ? acf_get_field( $key ) : null;
            $ubah[] = $field ? $field['label'] : $key;
        }
    }

    $label_tax = array( 'jenis' => 'Jenis', 'agama' => 'Agama', 'wilayah' => 'Provinsi', 'kategori-tradisi' => 'Kategori', 'post_tag' => 'Kata kunci' );
    foreach ( tk_usulan_taxonomy() as $tax ) {
        $ta = wp_get_object_terms( $asal_id, $tax, array( 'fields' => 'ids' ) );
        $tu = wp_get_object_terms( $usulan_id, $tax, array( 'fields' => 'ids' ) );
        sort( $ta );
        sort( $tu );
        if ( $ta != $tu ) { // phpcs:ignore Universal.Operators.StrictComparisons -- bandingkan isi array.
            $ubah[] = $label_tax[ $tax ];
        }
    }

    return array_values( array_unique( $ubah ) );
}

add_filter( 'wp_insert_post_data', 'tk_usulan_tahan_terbit', 10, 2 );

/**
 * Usulan tidak boleh terbit sebagai halaman sendiri (mis. tombol Publish di
 * wp-admin); statusnya ditahan di "Menunggu Kurasi". Terapkan lewat tombol
 * Terbitkan di Dashboard Kurasi / Panel Kurator.
 *
 * @param array $data
 * @param array $postarr
 * @return array
 */
function tk_usulan_tahan_terbit( $data, $postarr ) {
    if ( 'tradisi' === $data['post_type'] && in_array( $data['post_status'], array( 'publish', 'future' ), true )
        && ! empty( $postarr['ID'] ) && tk_usulan_asal( $postarr['ID'] ) ) {
        $data['post_status'] = 'pending';
    }
    return $data;
}

add_action( 'admin_notices', 'tk_usulan_pemberitahuan_admin' );

/**
 * Keterangan di editor wp-admin saat membuka sebuah usulan.
 */
function tk_usulan_pemberitahuan_admin() {
    $layar = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    $id    = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
    if ( ! $layar || 'tradisi' !== $layar->post_type || ! $id || ! tk_usulan_asal( $id ) ) {
        return;
    }
    printf(
        '<div class="notice notice-warning"><p>Ini <strong>usulan perubahan</strong> untuk <a href="%s">%s</a>. Usulan tidak bisa diterbitkan sendiri; setujui lewat tombol Terbitkan di <a href="%s">Dashboard Kurasi</a> atau Panel Kurator agar isinya diterapkan ke versi terbit.</p></div>',
        esc_url( get_permalink( tk_usulan_asal( $id ) ) ),
        esc_html( get_the_title( tk_usulan_asal( $id ) ) ),
        esc_url( tk_url_kurasi() )
    );
}
