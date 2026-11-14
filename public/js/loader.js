/* Layar pembuka (sekali per sesi) & loader logo + slogan saat berpindah halaman. */
(() => {
  const el = document.getElementById('pembuka');
  if (!el) return;
  const root = document.documentElement;
  const tutup = () => { el.classList.add('hilang'); el.classList.remove('muat'); setTimeout(() => el.setAttribute('aria-hidden', 'true'), 300); };

  // 1) Layar pembuka: tampil saat kunjungan pertama dalam sesi, minimal ~1,2 detik
  if (!root.classList.contains('tanpa-pembuka')) {
    el.removeAttribute('aria-hidden');
    setTimeout(() => el.classList.add('tampil-slogan'), 250);
    const mulai = performance.now();
    const selesai = () => setTimeout(tutup, Math.max(0, 1200 - (performance.now() - mulai)));
    document.readyState === 'complete' ? selesai() : addEventListener('load', selesai, { once: true });
    document.getElementById('pembuka-lewati')?.addEventListener('click', tutup);
    try { sessionStorage.setItem('pembuka', '1'); } catch (e) {}
  }

  // 2) Loader saat klik tautan/kirim formulir ke halaman lain (muncul bila lebih dari 150 ms)
  let timer;
  const tampilMuat = () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
      el.classList.remove('hilang');
      el.classList.add('muat', 'tampil-slogan');
      el.removeAttribute('aria-hidden');
    }, 150);
  };
  document.addEventListener('click', e => {
    const a = e.target.closest('a[href]');
    if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    const url = new URL(a.href, location.href);
    if (a.target === '_blank' || a.hasAttribute('download') || url.origin !== location.origin) return;
    if (url.pathname === location.pathname && url.search === location.search && url.hash) return; // lompat ke bagian halaman
    if (/\/(tiket|export|xlsx|pdf|notifikasi\/[^/]+\/baca)/.test(url.pathname) && !/\/baca/.test(url.pathname)) return; // unduhan
    tampilMuat();
  });
  document.addEventListener('submit', e => {
    setTimeout(() => { if (!e.defaultPrevented && !e.target.target) tampilMuat(); }, 0);
  });
  // Kembali dengan tombol Back (bfcache) atau unduhan selesai: sembunyikan loader
  addEventListener('pageshow', () => { clearTimeout(timer); tutup(); });
  addEventListener('blur', () => { clearTimeout(timer); });
  // Pengaman: loader tidak pernah tertahan lebih dari 10 detik (mis. unduhan file)
  new MutationObserver(() => { if (el.classList.contains('muat')) setTimeout(tutup, 10000); }).observe(el, { attributes: true, attributeFilter: ['class'] });
})();
