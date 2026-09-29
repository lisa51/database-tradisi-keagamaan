/**
 * WARISI — kontak.js
 * [tk_kontak]: tolak lampiran yang melebihi batas ukuran sebelum diunggah.
 * Batas dibaca dari atribut data-maks (byte). Pengecekan sebenarnya tetap
 * dilakukan di server (includes/kontak/proses.php).
 */
( function () {
  var input = document.getElementById( 'tk_lampiran' );
  if ( ! input ) {
    return;
  }

  input.addEventListener( 'change', function () {
    var maks = parseInt( input.getAttribute( 'data-maks' ), 10 );
    var file = input.files && input.files[0];

    if ( file && maks && file.size > maks ) {
      input.setCustomValidity( 'Lampiran terlalu besar (maks. ' + Math.round( maks / 1048576 ) + ' MB).' );
      input.reportValidity();
    } else {
      input.setCustomValidity( '' );
    }
  } );
} )();
