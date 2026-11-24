/* Layar pembuka (sekali per sesi) & payloader saat berpindah link lokal (tanpa slogan). */
(() => {
  const el = document.getElementById('pembuka');
  if (!el) return;
  const root = document.documentElement;
  const tutup = () => {
    el.classList.add('hilang');
    el.classList.remove('muat', 'tampil-slogan');
    setTimeout(() => el.setAttribute('aria-hidden', 'true'), 250);
  };

  // 1) Layar pembuka sesi pertama: tampil sekali saat buka aplikasi (dengan slogan)
  if (!root.classList.contains('tanpa-pembuka')) {
    el.removeAttribute('aria-hidden');
    setTimeout(() => el.classList.add('tampil-slogan'), 200);
    const mulai = performance.now();
    const selesai = () => setTimeout(tutup, Math.max(0, 900 - (performance.now() - mulai)));
    document.readyState === 'complete' ? selesai() : addEventListener('load', selesai, { once: true });
    document.getElementById('pembuka-lewati')?.addEventListener('click', tutup);
    try { sessionStorage.setItem('pembuka', '1'); } catch (e) {}
  }

  // 2) Payloader: HANYA berjalan ketika link lokal di-klik, TANPA memunculkan slogan
  let timer;
  const tampilPayloader = () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
      el.classList.remove('hilang', 'tampil-slogan');
      el.classList.add('muat');
      el.removeAttribute('aria-hidden');
    }, 180);
  };

  document.addEventListener('click', e => {
    const a = e.target.closest('a[href]');
    if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

    const hrefAttr = (a.getAttribute('href') || '').trim();
    if (!hrefAttr || hrefAttr === '#' || hrefAttr.startsWith('#') || hrefAttr.startsWith('javascript:')) return;

    let url;
    try {
      url = new URL(a.href, location.href);
    } catch (_) {
      return;
    }

    // Hanya untuk link lokal (domain yang sama), bukan tab baru atau unduhan
    if (a.target === '_blank' || a.hasAttribute('download') || url.origin !== location.origin) return;

    // Abaikan jika menuju halaman yang sama persis (termasuk hash)
    if (url.pathname === location.pathname && url.search === location.search) return;

    // Abaikan unduhan dokumen / aksi khusus
    if (/\/(tiket|export|xlsx|pdf|unduh-data|notifikasi\/[^/]+\/baca)/.test(url.pathname) && !/\/baca/.test(url.pathname)) return;

    tampilPayloader();
  });

  // Navigasi kembali (bfcache) atau halaman siap: sembunyikan payloader
  addEventListener('pageshow', () => { clearTimeout(timer); tutup(); });
  addEventListener('blur', () => { clearTimeout(timer); });

  // Batas aman payloader otomatis hilang jika halaman tidak kunjung berpindah dalam 8 detik
  new MutationObserver(() => {
    if (el.classList.contains('muat')) setTimeout(tutup, 8000);
  }).observe(el, { attributes: true, attributeFilter: ['class'] });
})();
