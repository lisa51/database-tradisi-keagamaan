/**
 * WARISI — kabkota.js
 * Saran isian Kabupaten/Kota (field ACF asal_daerah) saat mengetik.
 *
 * Memakai <datalist> bawaan browser: ketik sebagian nama (mis. "toraja"),
 * lalu pilih dari saran. Saran disaring sesuai provinsi yang dipilih:
 *   Form depan     field "Provinsi" (tk_form_wilayah)
 *   wp-admin       kotak Provinsi di editor blok / editor klasik
 * Belum ada provinsi = semua kabupaten/kota ditampilkan.
 *
 * Data (window.tkKabkota) dan validasi di server: includes/core/kabupaten-kota.php.
 */
( function ( $ ) {
  'use strict';

  if ( ! window.acf || ! window.tkKabkota ) {
    return;
  }

  var daftar = window.tkKabkota.daftar;
  var idProvinsi = window.tkKabkota.idProvinsi;
  var semua = [];
  Object.keys( daftar ).forEach( function ( p ) {
    semua = semua.concat( daftar[ p ] );
  } );

  acf.addAction( 'ready_field/name=asal_daerah', function ( field ) {
    var $input = field.$input();
    var list = document.createElement( 'datalist' );
    list.id = 'tk-kabkota-' + field.get( 'key' );
    $input.after( list ).attr( { list: list.id, autocomplete: 'off' } );

    var terakhir = null;

    function isi( provinsi ) {
      if ( provinsi === terakhir ) {
        return;
      }
      terakhir = provinsi;
      var nama = ( provinsi && daftar[ provinsi ] ) ? daftar[ provinsi ] : semua;
      list.innerHTML = '';
      nama.forEach( function ( n ) {
        var opsi = document.createElement( 'option' );
        opsi.value = n;
        list.appendChild( opsi );
      } );
      $input.attr( 'placeholder', provinsi && daftar[ provinsi ]
        ? 'Ketik untuk mencari di ' + provinsi
        : 'Contoh: Kabupaten Tana Toraja' );
    }

    isi( bacaProvinsi() );

    // Form depan & editor klasik: dengarkan perubahan pilihan.
    $( document ).on( 'change', '[data-name="tk_form_wilayah"] select, #wilayahchecklist input', function () {
      isi( bacaProvinsi() );
    } );

    // Editor blok: pantau taxonomy "wilayah" di data editor.
    if ( window.wp && wp.data && wp.data.select( 'core/editor' ) ) {
      wp.data.subscribe( function () {
        isi( bacaProvinsi() );
      } );
    }
  } );

  /**
   * Nama provinsi yang sedang dipilih, atau ''.
   */
  function bacaProvinsi() {
    var $pilih = $( '[data-name="tk_form_wilayah"] select option:selected' );
    if ( $pilih.length && $pilih.val() ) {
      return idProvinsi[ $pilih.val() ] || '';
    }

    var $cek = $( '#wilayahchecklist input:checked' ).first();
    if ( $cek.length ) {
      return idProvinsi[ $cek.val() ] || '';
    }

    if ( window.wp && wp.data && wp.data.select( 'core/editor' ) ) {
      var ids = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'wilayah' ) || [];
      for ( var i = 0; i < ids.length; i++ ) {
        if ( idProvinsi[ ids[ i ] ] ) {
          return idProvinsi[ ids[ i ] ];
        }
      }
    }
    return '';
  }
} )( jQuery );
