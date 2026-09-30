/**
 * WARISI — kurasi.js
 * Aksi massal di Dashboard Kurasi (form#tk-massal).
 *
 *   "Pilih semua"   centang/lepas semua koleksi di halaman ini
 *   Hitungan        "N dipilih"
 *   Terapkan        cek ada pilihan & aksi, catatan wajib untuk
 *                   Kembalikan ke Antrean / Minta Revisi / Tolak, lalu konfirmasi
 *
 * Validasi yang sama dijalankan lagi di server (tk_kurasi_massal_handle()).
 */
( function () {
  'use strict';

  var form = document.getElementById( 'tk-massal' );
  if ( ! form ) {
    return;
  }

  var semua = form.querySelector( '[data-tk-pilih-semua]' );
  var hitung = form.querySelector( '[data-tk-hitung]' );
  var WAJIB_CATATAN = [ 'antrean', 'revisi', 'tolak' ];

  function kotak() {
    return Array.prototype.slice.call( document.querySelectorAll( 'input[form="tk-massal"][name="post_ids[]"]' ) );
  }

  function perbarui() {
    var semuaKotak = kotak();
    var dipilih = semuaKotak.filter( function ( k ) { return k.checked; } ).length;
    hitung.textContent = dipilih + ' dipilih';
    semua.checked = dipilih > 0 && dipilih === semuaKotak.length;
    semua.indeterminate = dipilih > 0 && dipilih < semuaKotak.length;
  }

  semua.addEventListener( 'change', function () {
    kotak().forEach( function ( k ) { k.checked = semua.checked; } );
    perbarui();
  } );

  document.addEventListener( 'change', function ( e ) {
    if ( e.target.matches && e.target.matches( 'input[form="tk-massal"]' ) ) {
      perbarui();
    }
  } );

  form.addEventListener( 'submit', function ( e ) {
    var dipilih = kotak().filter( function ( k ) { return k.checked; } ).length;
    var aksi = form.elements.aksi;
    var catatan = form.elements.catatan;

    if ( ! dipilih ) {
      e.preventDefault();
      window.alert( 'Pilih minimal satu koleksi.' );
      return;
    }
    if ( ! aksi.value ) {
      e.preventDefault();
      aksi.focus();
      return;
    }
    if ( WAJIB_CATATAN.indexOf( aksi.value ) !== -1 && ! catatan.value.trim() ) {
      e.preventDefault();
      catatan.focus();
      window.alert( 'Catatan wajib diisi untuk aksi ini. Catatan dikirim ke pengirim.' );
      return;
    }
    var label = aksi.options[ aksi.selectedIndex ].text;
    if ( ! window.confirm( label + ' untuk ' + dipilih + ' koleksi?' ) ) {
      e.preventDefault();
    }
  } );

  perbarui();
} )();
