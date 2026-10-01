/**
 * WARISI — bagikan.js
 * Tombol "Salin tautan" & "Instagram" di bilah bagikan (includes/core/bagikan.php).
 *
 *   salin      salin URL ke clipboard; ikon berubah sebentar menjadi centang + pesan.
 *   instagram  Instagram tidak punya tautan berbagi untuk web. Bila browser punya
 *              menu bagikan bawaan (navigator.share, umumnya di ponsel), buka menu
 *              itu (Instagram ada di dalamnya). Bila tidak, salin tautan + petunjuk.
 *   lainnya    menu bagikan bawaan; tombolnya hanya ditampilkan bila didukung.
 */
( function () {
  'use strict';

  var bilah = document.querySelector( '[data-tk-bagikan]' );
  if ( ! bilah ) {
    return;
  }

  var url    = bilah.getAttribute( 'data-url' );
  var judul  = bilah.getAttribute( 'data-judul' );
  var pesan  = bilah.querySelector( '[data-tk-bagikan-pesan]' );
  var jeda;

  // Tombol "Lainnya" hanya berguna bila browser punya menu bagikan bawaan.
  if ( navigator.share ) {
    var lainnya = bilah.querySelector( '[data-tk-bagikan-aksi="lainnya"]' );
    if ( lainnya ) {
      lainnya.hidden = false;
    }
  }

  function tampilkan( teks ) {
    pesan.textContent = teks;
    clearTimeout( jeda );
    jeda = setTimeout( function () { pesan.textContent = ''; }, 5000 );
  }

  // Salin ke clipboard; cadangan untuk browser lama / halaman non-HTTPS.
  function salin() {
    if ( navigator.clipboard && window.isSecureContext ) {
      return navigator.clipboard.writeText( url );
    }
    return new Promise( function ( selesai, gagal ) {
      var area = document.createElement( 'textarea' );
      area.value = url;
      area.setAttribute( 'readonly', '' );
      area.style.position = 'fixed';
      area.style.opacity = '0';
      document.body.appendChild( area );
      area.select();
      var ok = document.execCommand( 'copy' );
      document.body.removeChild( area );
      ( ok ? selesai : gagal )();
    } );
  }

  bilah.addEventListener( 'click', function ( e ) {
    var tombol = e.target.closest( '[data-tk-bagikan-aksi]' );
    if ( ! tombol ) {
      return;
    }
    var aksi = tombol.getAttribute( 'data-tk-bagikan-aksi' );

    if ( ( 'instagram' === aksi || 'lainnya' === aksi ) && navigator.share ) {
      navigator.share( { title: judul, url: url } ).catch( function () {} ); // Dibatalkan pengguna: abaikan.
      return;
    }

    salin().then( function () {
      if ( 'instagram' === aksi ) {
        tampilkan( 'Tautan disalin. Buka Instagram, lalu tempelkan tautan di Story (stiker Tautan), bio, atau pesan.' );
        return;
      }
      tombol.classList.add( 'is-aktif' ); // Ikon rantai → centang (single.css).
      tampilkan( 'Tautan disalin.' );
      setTimeout( function () { tombol.classList.remove( 'is-aktif' ); }, 2500 );
    } ).catch( function () {
      tampilkan( 'Tautan tidak dapat disalin otomatis. Salin dari bilah alamat browser: ' + url );
    } );
  } );
}() );
