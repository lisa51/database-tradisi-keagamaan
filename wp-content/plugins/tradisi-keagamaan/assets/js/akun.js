/**
 * WARISI — akun.js
 * Jendela konfirmasi sebelum keluar (logout).
 *
 * Berlaku untuk semua link logout WordPress (href berisi "action=logout"),
 * misalnya menu "Keluar" (tk-menu-akun) dan "Log Out" di admin bar.
 *
 *   Klik Keluar      buka konfirmasi
 *   Ya, Keluar       lanjut ke link logout
 *   Batal, Esc,      tutup, tetap login
 *   klik latar
 *
 * Tanpa JavaScript, link langsung logout seperti biasa.
 * Browser tanpa <dialog> memakai confirm() bawaan.
 * Gaya: assets/css/dialog.css. Dimuat di halaman depan (hanya saat login)
 * dan di wp-admin, lihat tk_enqueue_akun() di includes/core/assets.php.
 */
( function () {
  var PESAN = 'Anda yakin ingin keluar dari akun WARISI?';
  var dialog = null;
  var tujuan = '';

  function buatDialog() {
    dialog = document.createElement( 'dialog' );
    dialog.className = 'tk-dialog';
    dialog.setAttribute( 'aria-labelledby', 'tk-dialog-judul' );
    // Padding ada di .tk-dialog__isi, supaya klik di luarnya = klik latar.
    dialog.innerHTML =
      '<div class="tk-dialog__isi">' +
        '<h2 id="tk-dialog-judul" class="tk-dialog__judul">Keluar dari akun?</h2>' +
        '<p class="tk-dialog__pesan">' + PESAN + '</p>' +
        '<div class="tk-dialog__tombol">' +
          '<button type="button" class="tk-dialog__btn" data-aksi="batal">Batal</button>' +
          '<button type="button" class="tk-dialog__btn tk-dialog__btn--utama" data-aksi="keluar">Ya, Keluar</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild( dialog );

    dialog.addEventListener( 'click', function ( e ) {
      var aksi = e.target.getAttribute( 'data-aksi' );
      if ( 'keluar' === aksi ) {
        window.location.href = tujuan;
      } else if ( 'batal' === aksi || e.target === dialog ) {
        // e.target === dialog: klik di latar (di luar .tk-dialog__isi).
        dialog.close();
      }
    } );
  }

  document.addEventListener( 'click', function ( e ) {
    var link = e.target.closest && e.target.closest( 'a[href*="action=logout"]' );
    if ( ! link || e.ctrlKey || e.metaKey || e.shiftKey ) {
      return;
    }
    e.preventDefault();
    tujuan = link.href;

    if ( 'function' !== typeof HTMLDialogElement ) {
      if ( window.confirm( PESAN ) ) {
        window.location.href = tujuan;
      }
      return;
    }

    if ( ! dialog ) {
      buatDialog();
    }
    dialog.showModal();
    // Fokus awal di "Batal" supaya Enter tidak langsung logout.
    dialog.querySelector( '[data-aksi="batal"]' ).focus();
  } );
} )();
