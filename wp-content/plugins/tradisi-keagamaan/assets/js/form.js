/**
 * WARISI — Form Tambah Tradisi
 * -----------------------------------------------------------------------------
 * Dimuat oleh includes/shortcodes/form-tradisi.php. Gaya tombol ada di assets/css/kontribusi.css.
 *
 * Tombol "Simpan Draf" (name="tk_status" value="draft") tidak perlu
 * semua field wajib terisi. Script ini mematikan validasi ACF di browser
 * sesaat sebelum form terkirim lewat tombol draf, dan menyalakannya lagi
 * untuk tombol "Kirim untuk Dikurasi". Validasi di server diatur oleh
 * tk_form_validasi_draf() di PHP.
 */
(function () {
  'use strict';

  document.addEventListener('click', function (e) {
    var tombol = e.target.closest('button[name="tk_status"]');
    if (!tombol || !window.acf || !acf.validation) return;

    var form = tombol.form;

    if (tombol.value === 'draft') {
      acf.validation.disable();
      if (form) form.setAttribute('novalidate', 'novalidate'); // abaikan "required" bawaan browser
    } else {
      acf.validation.enable();
    }
  }, true);
})();
