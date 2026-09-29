/**
 * WARISI — galeri.js
 * Lightbox untuk Galeri Foto di halaman detail tradisi (.tk-galeri[data-tk-galeri]).
 *
 *   Klik foto        buka foto besar
 *   ← / →, tombol    foto sebelumnya / berikutnya
 *   Esc, klik latar  tutup
 *   Geser (HP)       sebelumnya / berikutnya
 *
 * Tanpa JavaScript, link foto tetap membuka gambar besar biasa.
 * Gaya: .tk-lightbox di assets/css/single.css.
 */
( function () {
  var galeri = document.querySelector( '[data-tk-galeri]' );
  if ( ! galeri ) {
    return;
  }

  var links = Array.prototype.slice.call( galeri.querySelectorAll( 'a' ) );
  var indeks = 0;
  var pemicu = null;
  var sentuhX = null;

  // Kerangka lightbox, dibuat sekali.
  var kotak = document.createElement( 'div' );
  kotak.className = 'tk-lightbox';
  kotak.hidden = true;
  kotak.setAttribute( 'role', 'dialog' );
  kotak.setAttribute( 'aria-modal', 'true' );
  kotak.setAttribute( 'aria-label', 'Galeri foto' );
  kotak.innerHTML =
    '<button type="button" class="tk-lightbox__tutup" aria-label="Tutup">&times;</button>' +
    '<button type="button" class="tk-lightbox__nav tk-lightbox__nav--kiri" aria-label="Foto sebelumnya">&#8249;</button>' +
    '<figure class="tk-lightbox__isi"><img alt=""><figcaption></figcaption></figure>' +
    '<button type="button" class="tk-lightbox__nav tk-lightbox__nav--kanan" aria-label="Foto berikutnya">&#8250;</button>' +
    '<div class="tk-lightbox__hitung" aria-live="polite"></div>';
  document.body.appendChild( kotak );

  var img = kotak.querySelector( 'img' );
  var ket = kotak.querySelector( 'figcaption' );
  var hitung = kotak.querySelector( '.tk-lightbox__hitung' );
  var tutupBtn = kotak.querySelector( '.tk-lightbox__tutup' );
  var banyak = links.length > 1;

  kotak.querySelector( '.tk-lightbox__nav--kiri' ).hidden = ! banyak;
  kotak.querySelector( '.tk-lightbox__nav--kanan' ).hidden = ! banyak;

  function tampil( i ) {
    indeks = ( i + links.length ) % links.length;
    var a = links[ indeks ];
    var thumb = a.querySelector( 'img' );
    img.src = a.href;
    img.alt = thumb ? thumb.alt : '';
    ket.textContent = a.getAttribute( 'data-keterangan' ) || '';
    ket.hidden = ! ket.textContent;
    hitung.textContent = ( indeks + 1 ) + ' / ' + links.length;
  }

  function buka( i ) {
    pemicu = document.activeElement;
    tampil( i );
    kotak.hidden = false;
    document.documentElement.classList.add( 'tk-lightbox-terbuka' );
    tutupBtn.focus();
  }

  function tutup() {
    kotak.hidden = true;
    img.removeAttribute( 'src' );
    document.documentElement.classList.remove( 'tk-lightbox-terbuka' );
    if ( pemicu ) {
      pemicu.focus();
    }
  }

  links.forEach( function ( a, i ) {
    a.addEventListener( 'click', function ( e ) {
      e.preventDefault();
      buka( i );
    } );
  } );

  kotak.addEventListener( 'click', function ( e ) {
    var t = e.target;
    if ( t === kotak || t === tutupBtn ) {
      tutup();
    } else if ( t.classList.contains( 'tk-lightbox__nav--kiri' ) ) {
      tampil( indeks - 1 );
    } else if ( t.classList.contains( 'tk-lightbox__nav--kanan' ) ) {
      tampil( indeks + 1 );
    }
  } );

  document.addEventListener( 'keydown', function ( e ) {
    if ( kotak.hidden ) {
      return;
    }
    if ( 'Escape' === e.key ) {
      tutup();
    } else if ( 'ArrowLeft' === e.key && banyak ) {
      tampil( indeks - 1 );
    } else if ( 'ArrowRight' === e.key && banyak ) {
      tampil( indeks + 1 );
    } else if ( 'Tab' === e.key ) {
      // Jaga fokus tetap di dalam lightbox.
      var tombol = Array.prototype.filter.call( kotak.querySelectorAll( 'button' ), function ( b ) { return ! b.hidden; } );
      var awal = tombol[0];
      var akhir = tombol[ tombol.length - 1 ];
      if ( e.shiftKey && document.activeElement === awal ) {
        e.preventDefault();
        akhir.focus();
      } else if ( ! e.shiftKey && document.activeElement === akhir ) {
        e.preventDefault();
        awal.focus();
      }
    }
  } );

  // Geser di layar sentuh.
  kotak.addEventListener( 'touchstart', function ( e ) {
    sentuhX = e.touches[0].clientX;
  }, { passive: true } );
  kotak.addEventListener( 'touchend', function ( e ) {
    if ( null === sentuhX || ! banyak ) {
      return;
    }
    var jarak = e.changedTouches[0].clientX - sentuhX;
    if ( Math.abs( jarak ) > 50 ) {
      tampil( indeks + ( jarak < 0 ? 1 : -1 ) );
    }
    sentuhX = null;
  } );
} )();
