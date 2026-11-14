@extends('layouts.public')
@section('title', 'Layar Antrian')
@section('content')
<section class="container" style="padding:36px 16px">
  <div class="page-h">
    <div><h1 style="font-size:30px;font-weight:800">Layar Antrean Langsung</h1>
      <p class="muted"><span class="live"><span class="dot"></span> Diperbarui otomatis setiap 5 detik · <span id="upd">memuat…</span></span></p></div>
    <button class="btn" id="fs"><x-ico n="monitor"/> Layar penuh</button>
  </div>
  <div class="board" id="board"><div class="board-card empty">Memuat data antrean…</div></div>
  <p class="privacy-note"><x-ico n="shield"/> Halaman publik hanya menampilkan nomor antrean dan nama poli. Nama, NIK, rekam medis pasien, serta ID dokter tidak ditampilkan.</p>
</section>
@endsection
@push('scripts')
<script>
(function () {
  const last = {};
  const say = t => { try { const u = new SpeechSynthesisUtterance(t); u.lang = 'id-ID'; speechSynthesis.speak(u); } catch (e) {} };
  const load = () => fetch(@json(route('live.antrian')), {headers: {Accept: 'application/json'}}).then(r => r.json()).then(d => {
    document.getElementById('upd').textContent = d.diperbarui;
    const b = document.getElementById('board'); b.innerHTML = '';
    d.poli.forEach(p => {
      const c = document.createElement('div'); c.className = 'board-card';
      c.innerHTML = '<h2></h2><div class="muted">Nomor dipanggil</div><div class="big-no"></div><div class="muted">Berikutnya</div><div class="next-list"></div><p class="muted mt mb0 st"></p>';
      c.querySelector('h2').textContent = p.poli;
      const no = c.querySelector('.big-no'); no.textContent = p.sedang_dipanggil || '—';
      if (p.sedang_dipanggil && last[p.poli] !== undefined && last[p.poli] !== p.sedang_dipanggil) {
        no.classList.add('flash'); say(`Nomor antrean ${p.sedang_dipanggil.split('').join(' ')}, silakan menuju ${p.poli}`);
      }
      last[p.poli] = p.sedang_dipanggil;
      const nl = c.querySelector('.next-list');
      if (!p.berikutnya.length) nl.innerHTML = '<span>—</span>';
      p.berikutnya.forEach(n => { const s = document.createElement('span'); s.textContent = n; nl.append(s); });
      c.querySelector('.st').textContent = `${p.menunggu} menunggu · ${p.selesai} selesai`;
      b.append(c);
    });
  }).catch(() => toast('danger', 'Koneksi terputus', 'Mencoba lagi…', 3000));
  load(); setInterval(load, 5000);
  document.getElementById('fs').onclick = () => document.documentElement.requestFullscreen?.();
})();
</script>
@endpush

