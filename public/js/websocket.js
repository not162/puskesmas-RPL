/* Puskesmas — real-time WebSocket (Laravel Reverb via Laravel Echo).
 * Server hanya mengirim NAMA kanal yang berubah (tanpa data pribadi); halaman lalu memuat ulang
 * bagiannya sendiri. Bila sambungan putus, realtime.js/app.js otomatis kembali ke polling. */
(() => {
  const cfg = window.__WS;
  if (!cfg || !window.Echo || !window.Pusher) return;
  window.__wsTersambung = false;
  const chip = () => document.querySelectorAll('.live-chip').forEach(el => {
    el.title = window.__wsTersambung ? 'Pembaruan langsung aktif' : 'Pembaruan berkala setiap beberapa detik';
    el.dataset.mode = window.__wsTersambung ? 'ws' : 'poll';
  });

  // Baru saja gagal tersambung (di halaman lain)? Jangan coba lagi selama 1 menit: pakai polling saja.
  try { if (Date.now() < +(sessionStorage.getItem('ws-mati') || 0)) { chip(); return; } } catch (e) {}

  let echo;
  try {
    echo = new window.Echo({
      broadcaster: 'reverb',
      key: cfg.key,
      wsHost: cfg.host,
      wsPort: cfg.port,
      wssPort: cfg.port,
      forceTLS: cfg.tls,
      enabledTransports: cfg.tls ? ['wss'] : ['ws'], // satu protokol saja agar tidak ada percobaan ganda
      authEndpoint: '/broadcasting/auth',
      auth: { headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' } },
    });
  } catch (e) { return; }

  const conn = echo.connector.pusher.connection;
  conn.bind('state_change', s => {
    // Server WebSocket tidak tersedia: hentikan percobaan (hindari error berulang), pakai polling
    if (s.current === 'unavailable' || s.current === 'failed') {
      try { sessionStorage.setItem('ws-mati', String(Date.now() + 60000)); } catch (e) {}
      try { echo.disconnect(); } catch (e) {}
      window.__wsTersambung = false; chip(); return;
    }
    const sebelum = window.__wsTersambung;
    window.__wsTersambung = s.current === 'connected';
    chip();
    // Tersambung kembali setelah putus: segarkan semua agar tidak ada perubahan yang terlewat
    if (window.__wsTersambung && sebelum === false && s.previous !== 'initialized') terima(['*']);
  });
  chip();

  function terima(kanal) {
    if (kanal.includes('notifikasi') || kanal.includes('*')) window.__pollNotif?.();
    const data = kanal.filter(k => k !== 'notifikasi');
    if (!data.length) return;
    const semua = data.includes('*') ? ['antrian', 'konsultasi', 'resep', 'obat', 'pasien', 'pengaduan', 'jadwal', 'pengumuman', 'akun'] : data;
    window.__segarkanKanal?.(semua);
    document.dispatchEvent(new CustomEvent('pkm:berubah', { detail: semua }));
  }
  const dengar = ch => ch.listen('.berubah', e => terima(e.kanal || []));

  dengar(echo.channel('publik'));
  if (cfg.staf) dengar(echo.private('staf'));
  if (cfg.akun) dengar(echo.private('akun.' + cfg.akun));
  window.__echo = echo;
})();
