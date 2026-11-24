/* Layar pembuka (splash screen awal sesi) tanpa overlay payloader yang mengganggu saat navigasi */
(() => {
  const el = document.getElementById('pembuka');
  if (!el) return;
  const root = document.documentElement;
  const tutup = () => {
    el.classList.add('hilang');
    el.classList.remove('muat', 'tampil-slogan');
    setTimeout(() => {
      el.setAttribute('aria-hidden', 'true');
      el.style.display = 'none';
    }, 250);
  };

  // 1) Layar pembuka: hanya tampil sekali saat kunjungan pertama sesi baru
  if (!root.classList.contains('tanpa-pembuka')) {
    el.removeAttribute('aria-hidden');
    setTimeout(() => el.classList.add('tampil-slogan'), 150);
    const mulai = performance.now();
    const selesai = () => setTimeout(tutup, Math.max(0, 600 - (performance.now() - mulai)));
    document.readyState === 'complete' ? selesai() : addEventListener('load', selesai, { once: true });
    document.getElementById('pembuka-lewati')?.addEventListener('click', tutup);
    try { sessionStorage.setItem('pembuka', '1'); } catch (e) {}
  } else {
    tutup();
  }

  // 2) Top progress bar ramping saat berpindah link lokal (smooth ala browser modern, tanpa layar tertutup)
  let bar = document.getElementById('pwa-progress-bar');
  if (!bar) {
    bar = document.createElement('div');
    bar.id = 'pwa-progress-bar';
    document.body.prepend(bar);
  }

  let progressTimer;
  const startProgress = () => {
    clearTimeout(progressTimer);
    bar.style.transition = 'none';
    bar.style.width = '0%';
    bar.style.opacity = '1';
    requestAnimationFrame(() => {
      bar.style.transition = 'width 0.4s ease';
      bar.style.width = '70%';
    });
  };

  const finishProgress = () => {
    clearTimeout(progressTimer);
    bar.style.transition = 'width 0.2s ease, opacity 0.2s ease';
    bar.style.width = '100%';
    progressTimer = setTimeout(() => {
      bar.style.opacity = '0';
      setTimeout(() => { bar.style.width = '0%'; }, 200);
    }, 200);
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

    if (a.target === '_blank' || a.hasAttribute('download') || url.origin !== location.origin) return;
    if (url.pathname === location.pathname && url.search === location.search) return;
    if (/\/(tiket|export|xlsx|pdf|unduh-data)/.test(url.pathname)) return;

    startProgress();
  });

  addEventListener('pageshow', finishProgress);
  addEventListener('load', finishProgress);
})();
