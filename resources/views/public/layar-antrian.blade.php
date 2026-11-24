@extends('layouts.public')
@section('title', 'Layar Antrian')
@section('content')
<section class="container" style="padding:36px 16px">
  <div class="page-h">
    <div><h1 style="font-size:30px;font-weight:800">Layar Antrean Langsung</h1>
      <p class="muted">
        <span class="live" id="live-indicator">
          <span class="dot" id="live-dot" style="background:var(--ok)"></span>
          <span id="upd-text">Diperbarui otomatis · <span id="upd">{{ $dataAwal['diperbarui'] ?? now()->format('H:i:s') }}</span></span>
        </span>
      </p>
    </div>
    <button class="btn" id="fs"><x-ico n="monitor"/> Layar penuh</button>
  </div>

  <div class="board" id="board">
    @forelse ($dataAwal['poli'] as $p)
      <div class="board-card" data-poli="{{ $p['poli'] }}">
        <h2>{{ $p['poli'] }}</h2>
        <div class="muted">Nomor dipanggil</div>
        <div class="big-no">{{ $p['sedang_dipanggil'] ?: '—' }}</div>
        <div class="muted">Berikutnya</div>
        <div class="next-list">
          @forelse ($p['berikutnya'] as $n)
            <span>{{ $n }}</span>
          @empty
            <span>—</span>
          @endforelse
        </div>
        <p class="muted mt mb0 st">{{ $p['menunggu'] }} menunggu · {{ $p['selesai'] }} selesai</p>
      </div>
    @empty
      <div class="board-card empty">Belum ada poli aktif.</div>
    @endforelse
  </div>

  <p class="privacy-note"><x-ico n="shield"/> Halaman publik hanya menampilkan nomor antrean dan nama poli. Nama, NIK, rekam medis pasien, serta ID dokter tidak ditampilkan.</p>
</section>
@endsection
@push('scripts')
<script>
(function () {
  const last = {};
  const endpoint = @json(route('live.antrian', [], false));
  const updEl = document.getElementById('upd');
  const updText = document.getElementById('upd-text');
  const liveDot = document.getElementById('live-dot');
  const board = document.getElementById('board');

  const say = t => {
    try {
      const u = new SpeechSynthesisUtterance(t);
      u.lang = 'id-ID';
      speechSynthesis.speak(u);
    } catch (e) {}
  };

  const load = () => {
    fetch(endpoint, { headers: { Accept: 'application/json' } })
      .then(r => {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      })
      .then(d => {
        if (liveDot) liveDot.style.background = 'var(--ok)';
        if (updText) updText.innerHTML = 'Diperbarui otomatis · <span id="upd">' + d.diperbarui + '</span>';

        board.innerHTML = '';
        d.poli.forEach(p => {
          const c = document.createElement('div');
          c.className = 'board-card';
          c.innerHTML = '<h2></h2><div class="muted">Nomor dipanggil</div><div class="big-no"></div><div class="muted">Berikutnya</div><div class="next-list"></div><p class="muted mt mb0 st"></p>';
          c.querySelector('h2').textContent = p.poli;

          const no = c.querySelector('.big-no');
          no.textContent = p.sedang_dipanggil || '—';

          if (p.sedang_dipanggil && last[p.poli] !== undefined && last[p.poli] !== p.sedang_dipanggil) {
            no.classList.add('flash');
            say(`Nomor antrean ${p.sedang_dipanggil.split('').join(' ')}, silakan menuju ${p.poli}`);
          }
          last[p.poli] = p.sedang_dipanggil;

          const nl = c.querySelector('.next-list');
          if (!p.berikutnya.length) {
            nl.innerHTML = '<span>—</span>';
          } else {
            p.berikutnya.forEach(n => {
              const s = document.createElement('span');
              s.textContent = n;
              nl.append(s);
            });
          }

          c.querySelector('.st').textContent = `${p.menunggu} menunggu · ${p.selesai} selesai`;
          board.append(c);
        });
      })
      .catch(() => {
        // Tampilkan indikator status menyambungkan kembali tanpa pop-up toast merah terus-menerus
        if (liveDot) liveDot.style.background = 'var(--warn)';
        if (updText) updText.textContent = 'Menyambungkan kembali…';
      });
  };

  // Mulai polling berkala
  load();
  setInterval(() => {
    if (!window.__wsTersambung) load();
  }, 5000);

  // Respons instan jika ada event perubahan antrean
  document.addEventListener('pkm:berubah', e => {
    if (e.detail.includes('antrian') || e.detail.includes('*')) load();
  });

  document.getElementById('fs').onclick = () => {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen?.().catch(() => {});
    } else {
      document.exitFullscreen?.().catch(() => {});
    }
  };
})();
</script>
@endpush

