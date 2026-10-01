/**
 * WARISI — Peta interaktif (Leaflet + OpenStreetMap)
 * -----------------------------------------------------------------------------
 * Dimuat otomatis oleh includes/shortcodes/peta.php, hanya di halaman yang
 * memakai peta.
 *
 * Alur:
 *   1. Cari semua elemen .tk-peta, baca data titik dari atribut data-titik
 *      (JSON dari PHP).
 *   2. Kelompokkan titik yang koordinatnya sama (lihat PRESISI_GRUP).
 *   3. Gambar satu pin per kelompok:
 *        - 1 tradisi  → pin bulat oranye.
 *        - >1 tradisi → pin lebih besar berisi angka jumlah tradisi.
 *   4. Saat pin diklik:
 *        - Ada panel (.tk-peta-panel): panel menampilkan detail (1 tradisi)
 *          atau daftar tradisi (>1). Klik nama di daftar → detail, dengan
 *          tombol "← Kembali ke daftar".
 *        - Tanpa panel (peta kecil di halaman single): popup kecil.
 *   5. Zoom disesuaikan agar semua pin terlihat.
 *
 * Keamanan: semua teks dimasukkan dengan textContent (bukan innerHTML).
 */
(function () {
  'use strict';

  /* ---------------------------------------------------------------------------
   * Pengaturan
   * ------------------------------------------------------------------------- */

  /** Pusat & zoom awal: seluruh Indonesia. */
  var PUSAT_INDONESIA = [-2.5, 118];
  var ZOOM_AWAL = 5;
  var ZOOM_SATU_TITIK = 10;

  /**
   * Jumlah angka di belakang koma untuk menganggap dua koordinat "sama".
   * 4 angka ≈ 11 meter. Naikkan ke 3 (≈ 110 m) kalau ingin pin yang sangat
   * berdekatan juga digabung.
   */
  var PRESISI_GRUP = 4;

  /* ---------------------------------------------------------------------------
   * Fungsi bantu
   * ------------------------------------------------------------------------- */

  /** Buat elemen HTML dengan class dan teks (aman). */
  function el(tag, className, text) {
    var node = document.createElement(tag);
    if (className) node.className = className;
    if (text) node.textContent = text;
    return node;
  }

  /** Kelompokkan titik berdasarkan koordinat yang dibulatkan. */
  function kelompokkan(titik) {
    var peta = {};
    var urutan = [];

    titik.forEach(function (t) {
      var kunci = t.lat.toFixed(PRESISI_GRUP) + ',' + t.lng.toFixed(PRESISI_GRUP);
      if (!peta[kunci]) {
        peta[kunci] = [];
        urutan.push(kunci);
      }
      peta[kunci].push(t);
    });

    return urutan.map(function (kunci) { return peta[kunci]; });
  }

  /** Ikon pin. Grup (>1 tradisi) menampilkan angka jumlahnya. */
  function ikonPin(jumlah) {
    var grup = jumlah > 1;
    var ukuran = grup ? 32 : 22;

    return L.divIcon({
      className: grup ? 'tk-pin tk-pin--grup' : 'tk-pin',
      html: '<span>' + (grup ? jumlah : '') + '</span>',
      iconSize: [ukuran, ukuran],
      iconAnchor: [ukuran / 2, ukuran / 2],
      popupAnchor: [0, -(ukuran / 2 + 2)]
    });
  }

  /* ---------------------------------------------------------------------------
   * Isi panel / popup
   * ------------------------------------------------------------------------- */

  /** Detail satu tradisi. */
  function isiDetail(t, denganGambar) {
    var frag = document.createDocumentFragment();

    if (denganGambar && t.gambar) {
      var img = el('img', 'tk-peta-panel-img');
      img.src = t.gambar;
      img.alt = t.judul;
      img.loading = 'lazy';
      frag.appendChild(img);
    }
    if (t.kategori) frag.appendChild(el('span', 'tk-pill tk-pill--kat', t.kategori));
    frag.appendChild(el('h3', 'tk-peta-panel-judul', t.judul));
    if (t.lokasi) frag.appendChild(el('p', 'tk-peta-panel-lokasi', t.lokasi));
    if (t.desk) frag.appendChild(el('p', 'tk-peta-panel-desk', t.desk));

    var link = el('a', 'tk-detail', 'Lihat Detail →');
    link.href = t.url;
    frag.appendChild(link);

    return frag;
  }

  /**
   * Daftar beberapa tradisi di satu titik.
   *
   * @param {Array}    grup     Tradisi dalam satu titik.
   * @param {Function} onPilih  Dipanggil saat satu tradisi dipilih. Kalau null,
   *                            setiap nama langsung menjadi link ke halaman detail.
   */
  function isiDaftar(grup, onPilih) {
    var frag = document.createDocumentFragment();

    frag.appendChild(el('span', 'tk-label', grup.length + ' koleksi di lokasi ini'));
    if (grup[0].lokasi) frag.appendChild(el('p', 'tk-peta-panel-lokasi', grup[0].lokasi));

    var ul = el('ul', 'tk-peta-daftar');
    grup.forEach(function (t) {
      var li = el('li');
      var item;

      if (onPilih) {
        item = el('button', 'tk-peta-daftar-item');
        item.type = 'button';
        item.addEventListener('click', function () { onPilih(t); });
      } else {
        item = el('a', 'tk-peta-daftar-item');
        item.href = t.url;
      }

      item.appendChild(el('strong', null, t.judul));
      if (t.kategori) item.appendChild(el('span', null, t.kategori));

      li.appendChild(item);
      ul.appendChild(li);
    });
    frag.appendChild(ul);

    return frag;
  }

  /** Tampilkan satu kelompok di panel samping. */
  function tampilkanDiPanel(panel, grup) {
    panel.classList.add('is-aktif');

    if (grup.length === 1) {
      panel.replaceChildren(isiDetail(grup[0], true));
      return;
    }

    var tampilkanDaftar = function () {
      panel.replaceChildren(isiDaftar(grup, function (t) {
        var kembali = el('button', 'tk-peta-kembali', '← Kembali ke daftar');
        kembali.type = 'button';
        kembali.addEventListener('click', tampilkanDaftar);

        panel.replaceChildren(kembali, isiDetail(t, true));
      }));
    };

    tampilkanDaftar();
  }

  /** Isi popup kecil (peta tanpa panel). */
  function isiPopup(grup) {
    var wadah = el('div', 'tk-peta-popup');
    wadah.appendChild(grup.length === 1 ? isiDetail(grup[0], false) : isiDaftar(grup, null));
    return wadah;
  }

  /* ---------------------------------------------------------------------------
   * Inisialisasi
   * ------------------------------------------------------------------------- */

  function initPeta(wadah) {
    var titik;
    try {
      titik = JSON.parse(wadah.getAttribute('data-titik') || '[]');
    } catch (e) {
      return;
    }
    if (!titik.length) return;

    var peta = L.map(wadah, { scrollWheelZoom: false }).setView(PUSAT_INDONESIA, ZOOM_AWAL);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 18,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(peta);

    // Zoom dengan scroll hanya setelah peta diklik (agar halaman tetap mudah digulir).
    peta.once('focus', function () { peta.scrollWheelZoom.enable(); });

    var panel = wadah.parentNode.querySelector('.tk-peta-panel');
    var batas = [];

    kelompokkan(titik).forEach(function (grup) {
      var posisi = [grup[0].lat, grup[0].lng];
      var judul = grup.length === 1 ? grup[0].judul : grup.length + ' koleksi';
      var marker = L.marker(posisi, { icon: ikonPin(grup.length), title: judul }).addTo(peta);

      batas.push(posisi);

      if (panel) {
        marker.on('click', function () { tampilkanDiPanel(panel, grup); });
      } else {
        marker.bindPopup(isiPopup(grup));
      }
    });

    if (batas.length === 1) {
      peta.setView(batas[0], ZOOM_SATU_TITIK);
    } else {
      peta.fitBounds(batas, { padding: [40, 40], maxZoom: ZOOM_SATU_TITIK });
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (typeof L === 'undefined') return; // Leaflet gagal dimuat.
    document.querySelectorAll('.tk-peta').forEach(initPeta);
  });
})();
