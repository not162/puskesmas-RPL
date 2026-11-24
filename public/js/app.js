/* Puskesmas Sehat — interaksi UI: tema, toast, konfirmasi, notifikasi real-time (polling). */
const ICON = {
  sun: '<svg class="ico" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>',
  moon: '<svg class="ico" viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>',
  check: '<svg class="ico" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>',
  alert: '<svg class="ico" viewBox="0 0 24 24"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0zM12 9v4M12 17h.01"/></svg>',
  x: '<svg class="ico" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg>',
  info: '<svg class="ico" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>',
};

(() => {
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];
  const root = document.documentElement;

  /* ---------- Tema gelap / terang ---------- */
  const ikonTema = () => $$('[data-theme-icon]').forEach(el => { el.innerHTML = root.dataset.theme === 'dark' ? ICON.sun : ICON.moon; });
  $$('[data-toggle-theme]').forEach(b => b.addEventListener('click', () => {
    root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
    try { localStorage.setItem('tema', root.dataset.theme); } catch (e) {}
    ikonTema();
  }));

  /* ---------- Toast & Alert Aktivitas ---------- */
  const soundBeep = (freqs = [600], dur = 0.05, vol = 0.08) => {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      freqs.forEach((f, idx) => {
        const o = ctx.createOscillator(), g = ctx.createGain(), t = ctx.currentTime + idx * dur;
        o.frequency.value = f;
        o.connect(g);
        g.connect(ctx.destination);
        g.gain.setValueAtTime(vol, t);
        g.gain.exponentialRampToValueAtTime(0.001, t + dur);
        o.start(t);
        o.stop(t + dur);
      });
    } catch (e) {}
  };

  const TI = { success: ['c-green', 'check'], warning: ['c-amber', 'alert'], danger: ['c-red', 'x'], info: ['c-blue', 'info'] };
  window.toast = (tipe = 'info', judul = '', pesan = '', ms = 5500) => {
    let box = $('.toasts'); if (!box) { box = document.createElement('div'); box.className = 'toasts'; document.body.append(box); }
    const [c, i] = TI[tipe] || TI.info;
    const el = document.createElement('div');
    el.className = `toast ${tipe}`; el.setAttribute('role', 'status');
    el.innerHTML = `<div class="ti ${c}">${ICON[i]}</div><div><b></b><p></p></div><button class="x" aria-label="Tutup">&times;</button>`;
    $('b', el).textContent = judul; $('p', el).textContent = pesan;
    const tutup = () => { el.classList.add('out'); setTimeout(() => el.remove(), 250); };
    $('.x', el).onclick = tutup; box.append(el); setTimeout(tutup, ms);

    // Suara alert feedback untuk setiap notifikasi aksi
    if (tipe === 'success') soundBeep([587, 880], 0.1, 0.08);
    else if (tipe === 'warning' || tipe === 'danger') soundBeep([440, 310], 0.12, 0.1);
    else soundBeep([660], 0.06, 0.05);
  };
  (window.__ALERTS || []).forEach(a => toast(a.tipe, a.judul, a.pesan));

  /* ---------- Alert & Interaksi Tombol (Smooth & Responsif) ---------- */
  document.addEventListener('click', e => {
    const b = e.target.closest('button, .btn, [role="button"], input[type="submit"]');
    if (!b || b.disabled) return;

    // Notifikasi alert khusus jika atribut data-alert-click disetel
    if (b.dataset.alertClick) {
      toast('info', b.dataset.alertJudul || 'Aktivitas', b.dataset.alertClick, 3000);
    }
  });

  /* ---------- Modal konfirmasi untuk aksi penting (hapus, batal, serahkan) ---------- */
  const modal = $('#confirm-modal');
  let pending = null;
  document.addEventListener('submit', e => {
    const f = e.target;
    if (!f.dataset.confirm || f.dataset.confirmed) return;
    e.preventDefault(); pending = f;
    $('#cm-title').textContent = f.dataset.confirmTitle || 'Konfirmasi';
    $('#cm-text').textContent = f.dataset.confirm;
    const danger = f.dataset.confirmTone !== 'ok';
    $('#cm-ok').className = 'btn ' + (danger ? 'btn-bad' : 'btn-primary');
    $('#cm-ok').textContent = f.dataset.confirmOk || 'Ya, lanjutkan';
    $('#cm-icon').className = 'ic ' + (danger ? 'c-red' : 'c-blue');
    modal.classList.add('open');
  });
  if (modal) {
    $('#cm-cancel').onclick = () => { modal.classList.remove('open'); pending = null; };
    $('#cm-ok').onclick = () => { if (pending) { pending.dataset.confirmed = '1'; pending.requestSubmit ? pending.requestSubmit() : pending.submit(); } modal.classList.remove('open'); };
    modal.addEventListener('click', e => { if (e.target === modal) $('#cm-cancel').click(); });
  }
  document.addEventListener('keydown', e => { if (e.key === 'Escape') { modal?.classList.remove('open'); $$('.menu.open').forEach(m => m.classList.remove('open')); } });

  /* ---------- Cegah submit ganda & Indikator aksi ---------- */
  document.addEventListener('submit', e => {
    if (e.defaultPrevented) return;
    const sub = e.target.querySelector('button[type=submit], input[type=submit]');
    if (sub) {
      sub.classList.add('loading');
    }
    $$('button[type=submit]', e.target).forEach(b => { setTimeout(() => { b.disabled = true; }, 0); });
  });

  /* ---------- Navigasi mobile & dropdown ---------- */
  /* Hasil audit menu hamburger: aria-expanded, ikon ✕, tutup saat klik tautan / luar menu / Esc /
     layar melebar, kunci scroll halaman belakang, fokus pindah ke menu. */
  const IKON_MENU = '<svg class="ico" viewBox="0 0 24 24"><path d="M3 6h18M3 12h18M3 18h18"/></svg>';
  const IKON_TUTUP = '<svg class="ico" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>';
  const pills = $('.pills'), burger = $('[data-burger]');
  const setPublik = buka => {
    if (!pills || !burger) return;
    pills.classList.toggle('open', buka);
    burger.setAttribute('aria-expanded', buka);
    burger.setAttribute('aria-label', buka ? 'Tutup menu' : 'Buka menu');
    burger.innerHTML = buka ? IKON_TUTUP : IKON_MENU;
    if (buka) pills.querySelector('a')?.focus();
  };
  if (pills && burger) {
    pills.id ||= 'menu-publik';
    burger.setAttribute('aria-controls', pills.id);
    burger.setAttribute('aria-expanded', 'false');
    burger.addEventListener('click', e => { e.stopPropagation(); setPublik(!pills.classList.contains('open')); });
    pills.addEventListener('click', e => { if (e.target.closest('a')) setPublik(false); });
    document.addEventListener('click', e => { if (pills.classList.contains('open') && !e.target.closest('.pills')) setPublik(false); });
  }
  const side = $('.sidebar'), scrim = $('.scrim'), tombolSide = $$('[data-side]');
  const setSide = buka => {
    if (!side) return;
    side.classList.toggle('open', buka);
    scrim?.classList.toggle('show', buka);
    document.body.classList.toggle('kunci-scroll', buka);
    tombolSide.forEach(b => b.setAttribute('aria-expanded', buka));
    if (buka) side.querySelector('a')?.focus();
  };
  if (side) {
    side.id ||= 'menu-samping';
    tombolSide.forEach(b => { b.setAttribute('aria-controls', side.id); b.setAttribute('aria-expanded', 'false'); b.addEventListener('click', () => setSide(!side.classList.contains('open'))); });
    scrim?.addEventListener('click', () => setSide(false));
    side.addEventListener('click', e => { if (e.target.closest('a') && innerWidth <= 960) setSide(false); });
  }
  document.addEventListener('keydown', e => { if (e.key === 'Escape') { setPublik(false); setSide(false); } });
  matchMedia('(min-width: 961px)').addEventListener('change', m => { if (m.matches) { setPublik(false); setSide(false); } });
  $$('[data-dropdown]').forEach(b => b.addEventListener('click', e => { e.stopPropagation(); const m = b.nextElementSibling; $$('.menu.open').forEach(x => x !== m && x.classList.remove('open')); m.classList.toggle('open'); }));
  document.addEventListener('click', e => { if (!e.target.closest('.menu')) $$('.menu.open').forEach(m => m.classList.remove('open')); });

  /* ---------- Lihat/sembunyikan kata sandi ---------- */
  $$('[data-pw]').forEach(b => b.addEventListener('click', () => { const i = b.previousElementSibling; const show = i.type === 'password'; i.type = show ? 'text' : 'password'; b.setAttribute('aria-pressed', show); b.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password'); b.style.color = show ? 'var(--brand)' : ''; }));

  /* ---------- Baris obat pada form resep ---------- */
  const rx = $('#rx-rows');
  if (rx) {
    const tpl = $('#rx-template').innerHTML; let i = rx.children.length;
    $('#rx-add').onclick = () => { rx.insertAdjacentHTML('beforeend', tpl.replaceAll('__i__', i++)); };
    rx.addEventListener('click', e => { if (e.target.closest('[data-rx-del]')) (e.target.closest('.rx-blok') || e.target.closest('.rx-row')).remove(); });
  }

  /* ---------- Notifikasi real-time (polling 15 dtk) ---------- */
  const feedUrl = window.__NOTIF_FEED;
  if (feedUrl) {
    let sejak = Math.floor(Date.now() / 1000);
    const render = d => {
      const c = $('#bell-count');
      if (c) { c.textContent = d.belum_dibaca > 99 ? '99+' : d.belum_dibaca; c.style.display = d.belum_dibaca ? 'grid' : 'none'; }
      $$('[data-notif-count]').forEach(x => { x.textContent = d.belum_dibaca; x.style.display = d.belum_dibaca ? '' : 'none'; });
      const list = $('#notif-list'); if (!list) return;
      list.innerHTML = d.terbaru.length ? '' : '<div class="empty">Belum ada notifikasi</div>';
      d.terbaru.forEach(n => {
        const a = document.createElement('a');
        a.className = 'notif-item' + (n.dibaca ? '' : ' unread'); a.href = window.__NOTIF_READ.replace('__id__', n.id);
        a.innerHTML = `<span class="notif-dot ${n.tipe || ''}"></span><div><div class="t"></div><div class="m"></div><div class="w"></div></div>`;
        $('.t', a).textContent = n.judul; $('.m', a).textContent = n.pesan; $('.w', a).textContent = n.waktu;
        list.append(a);
      });
    };
    const poll = () => fetch(feedUrl + '?sejak=' + sejak, { headers: { Accept: 'application/json' } })
      .then(r => r.ok ? r.json() : null).then(d => {
        if (!d) return;
        d.baru.forEach(n => { toast(n.tipe || 'info', n.judul, n.pesan, 8000); window.__onNotif && window.__onNotif(n); });
        sejak = d.server_time; render(d);
      }).catch(() => {});
    // Bila WebSocket tersambung, notifikasi didorong instan; polling hanya cadangan tiap 60 dtk
    window.__pollNotif = poll;
    let tik = 0;
    poll(); setInterval(() => { if (!window.__wsTersambung || ++tik % 4 === 0) poll(); }, 15000);
  }

  ikonTema();
})();

