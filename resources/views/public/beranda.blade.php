@extends('layouts.public')
@section('title', 'Beranda')
@section('content')
<section class="hero container">
  <div class="hero-icon"><x-ico n="cross"/></div>
  <h1>Layanan Kesehatan Puskesmas</h1>
  <p class="lead">Ambil antrean online, konsultasi dengan dokter sebelum datang, pantau panggilan secara langsung, dan cek resep obat Anda dengan mudah.</p>
  <span class="live"><span class="dot"></span> Data tampil langsung dari database · <span id="live-time">memuat…</span></span>

  <p class="ask">Apa yang ingin Anda lakukan?</p>
  <div class="intents">
    <a class="intent" href="{{ auth()->check() ? route('pasien.antrian.create') : route('login') }}"><span class="ic c-blue"><x-ico n="ticket"/></span> Saya mau ambil antrean online</a>
    <a class="intent" href="{{ auth()->check() ? route('pasien.konsultasi.create') : route('login') }}"><span class="ic c-green"><x-ico n="chat"/></span> Saya mau konsultasi online dulu</a>
    <a class="intent" href="{{ route('layar') }}"><span class="ic c-red"><x-ico n="monitor"/></span> Saya mau lihat antrean sekarang</a>
    <a class="intent" href="{{ route('publik.dokter') }}"><span class="ic c-violet"><x-ico n="steth"/></span> Saya mau lihat jadwal dokter</a>
    <a class="intent" href="{{ auth()->check() ? route('pasien.resep') : route('login') }}"><span class="ic c-teal"><x-ico n="pill"/></span> Saya mau cek resep & obat</a>
    <a class="intent" href="{{ route('register') }}"><span class="ic c-amber"><x-ico n="user"/></span> Saya pasien baru, mau daftar</a>
  </div>
</section>

<section class="section container" id="layanan">
  <span class="eyebrow">Layanan</span>
  <h2 class="section-title">Poli & Antrean Hari Ini</h2>
  <p class="section-sub">Antrean dihitung langsung. Identitas pasien dan data internal dokter tidak pernah ditampilkan di halaman publik.</p>
  <div class="grid g2" id="live-poli">
    @foreach ($spesialisasi as $sp)
      <div class="card"><div class="stat"><span class="ic {{ $loop->first ? 'c-blue' : 'c-violet' }}"><x-ico :n="$loop->first ? 'steth' : 'baby'"/></span><div><b>{{ $sp->poli }}</b><span>{{ $sp->dokter_count }} dokter · memuat antrean…</span></div></div></div>
    @endforeach
  </div>
  <p style="text-align:center;margin-top:18px"><a class="btn" href="{{ route('layar') }}"><x-ico n="monitor"/> Buka layar antrean penuh</a></p>
</section>

<section class="section container">
  <span class="eyebrow">Cara Pakai</span>
  <h2 class="section-title">Empat langkah sebelum datang</h2>
  <p class="section-sub">Alur singkat, Anda selalu tahu langkah berikutnya. <a href="{{ route('alur') }}">Lihat alur lengkap 10 langkah →</a></p>
  <div class="grid g4">
    @foreach ([['01','Daftar akun','Isi NIK, data diri, dan email. Nomor rekam medis langsung dibuat.','user'],['02','Konsultasi online','Ceritakan keluhan, dokter poli menanggapi sebelum Anda datang.','chat'],['03','Ambil antrian','Pilih poli dan tanggal, unduh tiket antrian.','ticket'],['04','Datang & check-in','Tunjukkan tiket di loket, pantau panggilan di layar.','monitor']] as [$n,$j,$d,$i])
      <div class="card"><span class="badge b-info">{{ $n }}</span><h3 class="mt" style="font-size:17px"><x-ico :n="$i"/> {{ $j }}</h3><p class="muted mb0">{{ $d }}</p></div>
    @endforeach
  </div>
</section>

<section class="section container">
  <span class="eyebrow">Dokter</span>
  <h2 class="section-title">Dokter Umum & Dokter Kandungan</h2>
  <p class="section-sub">Kenali dokter dan jadwal praktik sebelum berkunjung.</p>
  <div class="grid g3">
    @foreach ($dokter as $d)
      <div class="card"><div class="stat"><span class="ic {{ $d['spesialisasi']==='Kandungan' ? 'c-violet' : 'c-blue' }}"><x-ico n="steth"/></span><div><b style="font-size:16px">{{ $d['nama'] }}</b><span>{{ $d['poli'] }}</span></div></div>
        <p class="muted mt mb0"><x-ico n="clock"/> {{ $d['jadwal'] ?? 'Jadwal menyusul' }}</p></div>
    @endforeach
  </div>
</section>

<section class="section container">
  <span class="eyebrow">Pengumuman & Agenda</span>
  <h2 class="section-title">Kabar Terbaru Puskesmas</h2>
  <p class="section-sub">Informasi terkini, jadwal kegiatan, dan edukasi kesehatan.</p>
  <div class="grid g3">
    @forelse ($pengumuman as $p)
      <article class="card"><span class="badge {{ ['agenda'=>'b-warn','edukasi'=>'b-ok'][$p->kategori] ?? 'b-info' }}">{{ ucfirst($p->kategori) }}</span>
        <h3 class="mt" style="font-size:17px">{{ $p->judul }}</h3><p class="muted">{{ \Illuminate\Support\Str::limit($p->isi, 140) }}</p>
        <small><x-ico n="calendar"/> {{ $p->tanggal->locale('id')->translatedFormat('d F Y') }}</small></article>
    @empty
      <div class="card empty">Belum ada pengumuman.</div>
    @endforelse
  </div>
</section>

<section class="section container" id="bantuan">
  <span class="eyebrow">Bantuan</span>
  <h2 class="section-title">Hubungi Kami & Lokasi</h2>
  <p class="section-sub">{{ $pengaturan['jam_layanan'] }} · <a href="https://wa.me/{{ $pengaturan['whatsapp'] }}" target="_blank" rel="noopener">Chat WhatsApp</a></p>
  <div class="grid g2">
    <div class="card"><h3>Saluran bantuan</h3>
      <ul class="list-plain"><li><span><x-ico n="phone"/> Telepon / WhatsApp</span><b>{{ $pengaturan['telepon'] }}</b></li><li><span><x-ico n="mail"/> Email</span><b>{{ $pengaturan['email'] }}</b></li><li><span><x-ico n="alert"/> Gawat darurat</span><b>119</b></li></ul></div>
    <div class="card"><h3>Alamat</h3><p class="muted"><x-ico n="map"/> {{ $pengaturan['alamat'] }}</p>
      <a class="btn" target="_blank" rel="noopener" href="{{ $pengaturan['maps_url'] }}"><x-ico n="map"/> Buka di Google Maps</a></div>
  </div>
</section>
@endsection
@push('scripts')
<script>
(function () {
  const load = () => fetch(@json(route('live.antrian')), {headers: {Accept: 'application/json'}}).then(r => r.json()).then(d => {
    document.getElementById('live-time').textContent = 'diperbarui ' + d.diperbarui;
    const box = document.getElementById('live-poli'); box.innerHTML = '';
    d.poli.forEach((p, i) => {
      const el = document.createElement('div'); el.className = 'card';
      el.innerHTML = `<div class="flex between"><div><b style="font-size:17px"></b><div class="muted s"></div></div><div style="text-align:right"><div class="muted" style="font-size:12px">Sedang dipanggil</div><div class="n" style="font-size:30px;font-weight:800;color:var(--brand)"></div></div></div>`;
      el.querySelector('b').textContent = p.poli;
      el.querySelector('.s').textContent = `${p.menunggu} menunggu · ${p.selesai} selesai · ${p.total} total hari ini`;
      el.querySelector('.n').textContent = p.sedang_dipanggil || '—';
      box.append(el);
    });
  }).catch(() => {});
  load(); setInterval(() => { if (!window.__wsTersambung) load(); }, 10000);
  document.addEventListener('pkm:berubah', e => { if (e.detail.includes('antrian')) load(); });
})();
</script>
@endpush

