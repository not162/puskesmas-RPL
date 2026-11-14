/* Puskesmas — real-time lintas peran + tombol alert notifikasi. */
(() => {
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];

  /* ---- Tombol alert: pop-up perangkat (Notification API) + bunyi ---- */
  const btn = document.querySelector('[data-alert-toggle]');
  let alertOn = false;
  try { alertOn = localStorage.getItem('alert') === '1' && 'Notification' in window && Notification.permission === 'granted'; } catch (e) {}
  const ikon = () => {
    if (!btn) return;
    btn.setAttribute('aria-pressed', alertOn);
    btn.title = alertOn ? 'Alert aktif — klik untuk mematikan' : 'Aktifkan alert suara & pop-up perangkat';
    btn.querySelector('[data-alert-icon]').innerHTML = alertOn
      ? '<svg class="ico" viewBox="0 0 24 24"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10.3 21a1.9 1.9 0 0 0 3.4 0M4 2C2.8 3.7 2 5.7 2 8M22 8c0-2.3-.8-4.3-2-6"/></svg>'
      : '<svg class="ico" viewBox="0 0 24 24"><path d="M8.7 3A6 6 0 0 1 18 8a21 21 0 0 0 .6 5M17 17H3s3-2 3-9a4.7 4.7 0 0 1 .3-1.7M10.3 21a1.9 1.9 0 0 0 3.4 0M2 2l20 20"/></svg>';
  };
  const beep = () => {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      [880, 1175].forEach((f, i) => {
        const o = ctx.createOscillator(), g = ctx.createGain(), t = ctx.currentTime + i * .18;
        o.frequency.value = f; o.connect(g); g.connect(ctx.destination);
        g.gain.setValueAtTime(.18, t); g.gain.exponentialRampToValueAtTime(.001, t + .16); o.start(t); o.stop(t + .17);
      });
    } catch (e) {}
  };
  btn?.addEventListener('click', async () => {
    if (alertOn) {
      alertOn = false; try { localStorage.setItem('alert', '0'); } catch (e) {}
      ikon(); toast('info', 'Alert dimatikan', 'Notifikasi tetap muncul di lonceng.'); return;
    }
    if (!('Notification' in window)) { toast('warning', 'Tidak didukung', 'Browser ini tidak mendukung notifikasi perangkat.'); return; }
    const izin = await Notification.requestPermission();
    alertOn = izin === 'granted';
    try { localStorage.setItem('alert', alertOn ? '1' : '0'); } catch (e) {}
    ikon();
    if (alertOn) { beep(); toast('success', 'Alert aktif', 'Bunyi & pop-up muncul saat ada notifikasi baru.'); }
    else toast('danger', 'Izin ditolak', 'Aktifkan izin notifikasi untuk situs ini di pengaturan browser.');
  });
  window.__onNotif = n => {
    if (!alertOn) return;
    beep();
    try { const x = new Notification(n.judul, { body: n.pesan, tag: n.id }); x.onclick = () => { window.focus(); if (n.url) location.href = n.url; }; } catch (e) {}
  };
  ikon();

  /* ---- Real-time: muat ulang bagian [data-live="kanal,..."] saat peran lain mengubah data ---- */
  const url = window.__REALTIME;
  const zona = $$('[data-live][id]');
  if (!url || !zona.length) return;
  let versi = null, sibuk = false;
  const segarkan = async berubah => {
    const target = zona
      .filter(el => el.dataset.live.split(',').some(k => berubah.includes(k.trim())))
      .filter(el => !el.contains(document.activeElement));
    if (!target.length) return;
    const html = await fetch(location.href, { headers: { 'X-Requested-With': 'realtime' } }).then(r => (r.ok ? r.text() : null));
    if (!html) return;
    const doc = new DOMParser().parseFromString(html, 'text/html');
    target.forEach(el => {
      const baru = doc.getElementById(el.id);
      if (baru && baru.innerHTML !== el.innerHTML) {
        el.innerHTML = baru.innerHTML;
        el.classList.remove('live-flash'); void el.offsetWidth; el.classList.add('live-flash');
      }
    });
  };
  const cek = async () => {
    if (sibuk || document.hidden) return;
    sibuk = true;
    try {
      const d = await fetch(url, { headers: { Accept: 'application/json' } }).then(r => (r.ok ? r.json() : null));
      if (d) {
        if (versi) {
          const berubah = Object.keys(d.v).filter(k => d.v[k] !== versi[k]);
          if (berubah.length) await segarkan(berubah);
        }
        versi = d.v;
      }
    } catch (e) {} finally { sibuk = false; }
  };
  cek(); setInterval(cek, 4000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) cek(); });
})();
