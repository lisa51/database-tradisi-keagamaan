/**
 * WARISI — hero.js
 * Slideshow koleksi terbaru di [tk_hero] (includes/shortcodes/hero.php).
 *
 *   jalur   scroll-snap horizontal; tanpa JS tetap bisa digeser.
 *   ‹ ›     slide sebelumnya / berikutnya (berputar di ujung).
 *   titik   lompat ke slide; titik aktif diberi aria-current.
 *   putar   otomatis tiap JEDA ms; berhenti saat kursor/fokus di slider,
 *           saat tab tidak terlihat, dan bila pengguna memilih kurangi animasi.
 *           Setelah digeser jari, putar otomatis berhenti sampai tombol ditekan.
 */
( function () {
  'use strict';

  var JEDA = 6000;

  var slider = document.querySelector( '[data-tk-slider]' );
  if ( ! slider ) {
    return;
  }

  var jalur  = slider.querySelector( '[data-tk-slider-jalur]' );
  var nav    = slider.querySelector( '[data-tk-slider-nav]' );
  var titik  = slider.querySelectorAll( '[data-tk-slider-titik]' );
  var total  = jalur.children.length;
  var diam   = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
  var aktif  = 0;
  var timer  = null;
  var tahan  = false;

  if ( ! nav ) { // hero.php hanya membuat navigasi bila slide > 1.
    return;
  }
  nav.hidden = false;

  function ke( i ) {
    aktif = ( i + total ) % total;
    jalur.scrollTo( { left: aktif * jalur.clientWidth } );
  }

  function tandai() {
    for ( var i = 0; i < titik.length; i++ ) {
      titik[ i ].setAttribute( 'aria-current', i === aktif ? 'true' : 'false' );
    }
  }

  function mulai() {
    if ( diam || tahan || document.hidden ) {
      return;
    }
    berhenti();
    timer = setInterval( function () { ke( aktif + 1 ); }, JEDA );
  }

  function berhenti() {
    clearInterval( timer );
    timer = null;
  }

  // Slide aktif mengikuti posisi geser (juga saat digeser jari).
  var tunda;
  jalur.addEventListener( 'scroll', function () {
    clearTimeout( tunda );
    tunda = setTimeout( function () {
      aktif = Math.round( jalur.scrollLeft / jalur.clientWidth );
      tandai();
    }, 60 );
  } );

  nav.addEventListener( 'click', function ( e ) {
    var tombol = e.target.closest( 'button' );
    if ( ! tombol ) {
      return;
    }
    if ( tombol.hasAttribute( 'data-tk-slider-arah' ) ) {
      ke( aktif + Number.parseInt( tombol.getAttribute( 'data-tk-slider-arah' ), 10 ) );
    } else {
      ke( Number.parseInt( tombol.getAttribute( 'data-tk-slider-titik' ), 10 ) );
    }
    mulai();
  } );

  function tahanPutar( nilai ) {
    tahan = nilai;
    if ( nilai ) {
      berhenti();
    } else {
      mulai();
    }
  }
  slider.addEventListener( 'mouseenter', function () { tahanPutar( true ); } );
  slider.addEventListener( 'mouseleave', function () { tahanPutar( false ); } );
  slider.addEventListener( 'focusin', function () { tahanPutar( true ); } );
  slider.addEventListener( 'focusout', function ( e ) {
    if ( ! slider.contains( e.relatedTarget ) ) {
      tahanPutar( false );
    }
  } );
  jalur.addEventListener( 'touchstart', berhenti, { passive: true } );

  document.addEventListener( 'visibilitychange', function () {
    if ( document.hidden ) {
      berhenti();
    } else {
      mulai();
    }
  } );

  // Lebar berubah (rotasi ponsel): tetap di slide yang sama.
  window.addEventListener( 'resize', function () {
    jalur.scrollTo( { left: aktif * jalur.clientWidth, behavior: 'instant' } );
  } );

  tandai();
  mulai();
} )();
