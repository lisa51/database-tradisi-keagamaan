<?php
/**
 * Fungsi bantu umum yang dipakai banyak modul.
 *
 *   tk_term_names()    Nama term sebuah post sebagai teks ("Bali, Jawa Timur").
 *   tk_icon()          Ikon SVG garis (pin, gedung, buku, user, mata, link).
 *   tk_url_tambah()    URL halaman Tambah Tradisi.
 *   tk_url_kurasi()    URL halaman Dashboard Kurasi.
 *   tk_url_jelajahi()  URL panel pencarian di Beranda (#jelajahi).
 *   tk_url_tinjau()    URL tradisi untuk kurator (publik bila terbit, pratinjau bila belum).
 *   tk_ip_pengunjung() IP pengunjung, juga di belakang proxy Cloudflare.
 *   tk_batas_per_ip()  Batas jumlah kiriman per jam per IP (anti-spam).
 *
 * @package TradisiKeagamaan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ambil nama term (kategori, wilayah, tag) sebuah post sebagai satu string.
 *
 * Contoh:
 *   tk_term_names( 12, 'kategori-tradisi' )     // "Keagamaan, Perayaan Adat"
 *   tk_term_names( 12, 'wilayah', ', ', 1 )     // "Jawa Timur" (hanya term pertama)
 *
 * @param int    $post_id  ID post.
 * @param string $taxonomy Nama taxonomy.
 * @param string $sep      Pemisah antar-nama. Default ", ".
 * @param int    $limit    Maksimal jumlah term. 0 = semua.
 * @return string Nama term, atau string kosong kalau tidak ada.
 */
function tk_term_names( $post_id, $taxonomy, $sep = ', ', $limit = 0 ) {
    $terms = get_the_terms( $post_id, $taxonomy );
    if ( ! $terms || is_wp_error( $terms ) ) {
        return '';
    }
    if ( $limit > 0 ) {
        $terms = array_slice( $terms, 0, $limit );
    }
    return implode( $sep, wp_list_pluck( $terms, 'name' ) );
}

/**
 * Ikon SVG garis yang mengikuti warna teks (currentColor).
 *
 * Contoh:
 *   tk_icon( 'pin' )         // 14px, untuk lokasi di card
 *   tk_icon( 'buku', 22 )    // 22px, untuk kotak stats
 *
 * @param string $name Nama ikon: 'pin', 'gedung', 'buku', 'benda', 'user', 'mata', 'link';
 *                     untuk menu: 'rumah', 'cari', 'peta', 'kurasi', 'info',
 *                     'surat', 'tambah', 'masuk', 'keluar'.
 * @param int    $size Ukuran dalam piksel. Default 14.
 * @return string Markup SVG, atau string kosong kalau nama tidak dikenal.
 */
function tk_icon( $name, $size = 14 ) {
    $paths = array(
        'pin'    => '<path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/>',
        'gedung' => '<path d="M3 21h18M5 21V8l7-4 7 4v13M9 21v-6h6v6"/>',
        'user'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'mata'   => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'link'   => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
        'buku'   => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/>',
        'benda'  => '<path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8M12 13v8"/>', // Kotak: budaya material.

        // Menu (lihat includes/core/menu.php).
        'rumah'  => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/>',
        'cari'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'peta'   => '<path d="M9 4 3 6v14l6-2 6 2 6-2V4l-6 2-6-2z"/><path d="M9 4v14M15 6v14"/>',
        'kurasi' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1"/><path d="m9 13 2 2 4-4"/>',
        'info'   => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'surat'  => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'tambah' => '<path d="M12 5v14M5 12h14"/>',
        'masuk'  => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/>',
        'keluar' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
    );
    if ( ! isset( $paths[ $name ] ) ) {
        return '';
    }
    $size = absint( $size );
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . $paths[ $name ] . '</svg>';
}

/**
 * URL sebuah halaman plugin berdasarkan slug (TK_SLUG_* di config.php).
 * Bila halaman belum dibuat, pakai /<slug>/.
 *
 * @param string $slug
 * @return string
 */
function tk_url_halaman( $slug ) {
    $page = get_page_by_path( $slug );
    return $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
}

/** URL halaman "Tambah Tradisi". */
function tk_url_tambah() {
    return tk_url_halaman( TK_SLUG_TAMBAH );
}

/**
 * URL halaman "Ubah Tradisi", opsional langsung ke satu koleksi.
 *
 * @param int $post_id 0 = halaman Ubah tanpa koleksi terpilih.
 * @return string
 */
function tk_url_ubah( $post_id = 0 ) {
    $url = tk_url_halaman( TK_SLUG_UBAH );
    return $post_id ? add_query_arg( 'edit', absint( $post_id ), $url ) : $url;
}

/** URL halaman "Dashboard Kurasi". */
function tk_url_kurasi() {
    return tk_url_halaman( TK_SLUG_KURASI );
}

/**
 * URL halaman "Riwayat Suntingan" untuk satu koleksi.
 *
 * @param int $post_id
 * @param int $revisi_id Opsional: langsung ke revisi ini.
 * @return string
 */
function tk_url_riwayat_suntingan( $post_id, $revisi_id = 0 ) {
    $url = add_query_arg( 'id', absint( $post_id ), tk_url_halaman( TK_SLUG_RIWAYAT ) );
    return $revisi_id ? $url . '#rev-' . absint( $revisi_id ) : $url;
}

/**
 * URL panel pencarian di Beranda (anchor #jelajahi dari [tk_koleksi]).
 *
 * @return string
 */
function tk_url_jelajahi() {
    return home_url( '/#jelajahi' );
}

/**
 * URL halaman tradisi untuk kurator: halaman publik bila terbit,
 * pratinjau bila belum.
 *
 * @param int $post_id
 * @return string
 */
function tk_url_tinjau( $post_id ) {
    return 'publish' === get_post_status( $post_id )
        ? get_permalink( $post_id )
        : get_preview_post_link( $post_id );
}

/**
 * Alamat IP pengunjung.
 *
 * Di belakang proxy Cloudflare, REMOTE_ADDR berisi IP Cloudflare (sama untuk
 * banyak pengunjung), sehingga IP asli diambil dari header CF-Connecting-IP.
 * Header itu hanya dipercaya bila permintaan memang datang dari IP Cloudflare
 * (TK_CLOUDFLARE_IP), karena siapa pun bisa mengirim header palsu.
 *
 * @return string IP, atau '' bila tidak diketahui.
 */
function tk_ip_pengunjung() {
    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

    if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) && tk_ip_cloudflare( $ip ) ) {
        $asli = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
        if ( filter_var( $asli, FILTER_VALIDATE_IP ) ) {
            return $asli;
        }
    }
    return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
}

/**
 * Apakah IP ini milik proxy Cloudflare?
 *
 * @param string $ip
 * @return bool
 */
function tk_ip_cloudflare( $ip ) {
    foreach ( TK_CLOUDFLARE_IP as $rentang ) {
        if ( tk_ip_dalam_rentang( $ip, $rentang ) ) {
            return true;
        }
    }
    return false;
}

/**
 * Apakah IP berada dalam rentang CIDR (IPv4 atau IPv6), mis. "104.16.0.0/13"?
 *
 * @param string $ip
 * @param string $cidr
 * @return bool
 */
function tk_ip_dalam_rentang( $ip, $cidr ) {
    list( $subnet, $bit ) = explode( '/', $cidr );
    if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
        return false;
    }
    $ip_bin     = inet_pton( $ip );
    $subnet_bin = inet_pton( $subnet );
    if ( strlen( $ip_bin ) !== strlen( $subnet_bin ) ) {
        return false; // IPv4 vs IPv6.
    }

    $bit   = (int) $bit;
    $penuh = intdiv( $bit, 8 ); // Byte yang harus sama persis.
    $sisa  = $bit % 8;          // Bit di byte berikutnya.
    if ( substr( $ip_bin, 0, $penuh ) !== substr( $subnet_bin, 0, $penuh ) ) {
        return false;
    }
    if ( ! $sisa ) {
        return true;
    }
    $mask = chr( ( 0xff << ( 8 - $sisa ) ) & 0xff );
    return ( $ip_bin[ $penuh ] & $mask ) === ( $subnet_bin[ $penuh ] & $mask );
}

/**
 * Hitung satu kiriman dari IP pengunjung dan periksa batas per jam.
 *
 * @param string $awalan Nama penghitung, mis. 'tk_tamu' atau 'tk_kontak'.
 * @param int    $batas  Jumlah maksimum per jam.
 * @return bool false bila batas sudah terlampaui (kiriman tidak dihitung).
 */
function tk_batas_per_ip( $awalan, $batas ) {
    $kunci  = $awalan . '_' . md5( tk_ip_pengunjung() );
    $hitung = (int) get_transient( $kunci );

    if ( $hitung >= $batas ) {
        return false;
    }
    set_transient( $kunci, $hitung + 1, HOUR_IN_SECONDS );
    return true;
}
