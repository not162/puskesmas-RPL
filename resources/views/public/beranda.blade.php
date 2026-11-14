@extends('layouts.public')
@section('title', 'Beranda')
@section('content')
@php
  $masuk = fn ($rute) => auth()->check() ? route($rute) : route('login');
  $praktikHariIni = collect($dokter)->whereNotNull('hari_ini')->count();
@endphp

{{-- ============ HERO ============ --}}
<section class="hero2">
  <div class="container hero2-grid">
    <div>
      <span class="chip-hero"><x-ico n="check"/> Teman sehat untuk keluarga Anda</span>
      <h1>Berobat ke Puskesmas jadi lebih mudah</h1>
      <p class="lead-kiri">Ambil nomor antrean dari rumah, ceritakan keluhan ke dokter sebelum datang, lalu pantau kapan nomor Anda dipanggil. Tidak perlu menunggu lama di ruang tunggu.</p>
      <div class="flex hero2-aksi">
        <a href="{{ $masuk('pasien.antrian.create') }}" class="btn btn-primary btn-lg"><x-ico n="ticket"/> Ambil Antrean</a>
        <a href="{{ route('publik.dokter') }}" class="btn btn-lg"><x-ico n="calendar"/> Lihat Jadwal Dokter</a>
      </div>
      <p class="hero2-catatan"><x-ico n="check" class="ico ok"/> Belum punya akun? <a href="{{ route('register') }}">Daftar sekali</a>, langsung bisa dipakai.</p>
    </div>

    <div class="hero2-kartu" aria-label="Antrean hari ini">
      <div class="flex between"><b>Antrean hari ini</b><span class="live"><span class="dot"></span> <span id="live-time">memuat…</span></span></div>
      <div id="live-poli" class="hero2-poli">
        @foreach ($spesialisasi as $sp)
          <div class="poli-baris"><div><b>{{ $sp->poli }}</b><span class="muted">memuat…</span></div><div class="poli-no">—</div></div>
        @endforeach
      </div>
      <div class="hero2-kaki">
        <span><x-ico n="steth"/> {{ $praktikHariIni }} dokter praktik hari ini</span>
        <a href="{{ route('layar') }}">Layar antrean →</a>
      </div>
    </div>
  </div>
</section>

{{-- ============ KEBUTUHAN ============ --}}
<section class="section container">
  <span class="eyebrow-kiri">Mulai dari sini</span>
  <h2 class="judul-kiri">Apa yang Anda butuhkan hari ini?</h2>
  <p class="sub-kiri">Tidak perlu hafal nama menu. Pilih saja yang Anda perlukan.</p>
  <div class="intents">
    <a class="intent" href="{{ $masuk('pasien.antrian.create') }}"><span class="ic c-blue"><x-ico n="ticket"/></span> Saya mau ambil nomor antrean</a>
    <a class="intent" href="{{ $masuk('pasien.konsultasi.create') }}"><span class="ic c-green"><x-ico n="chat"/></span> Saya mau tanya dokter dulu</a>
    <a class="intent" href="{{ route('layar') }}"><span class="ic c-red"><x-ico n="monitor"/></span> Saya mau lihat antrean sekarang</a>
    <a class="intent" href="{{ route('publik.dokter') }}"><span class="ic c-violet"><x-ico n="steth"/></span> Saya mau lihat jadwal dokter</a>
    <a class="intent" href="{{ $masuk('pasien.resep') }}"><span class="ic c-teal"><x-ico n="pill"/></span> Saya mau cek resep & obat</a>
    <a class="intent" href="{{ route('register') }}"><span class="ic c-amber"><x-ico n="user"/></span> Saya pasien baru, mau daftar</a>
  </div>
</section>

{{-- ============ MENGAPA ============ --}}
<section class="section latar-lembut">
  <div class="container">
    <span class="eyebrow-kiri">Mengapa lewat aplikasi</span>
    <h2 class="judul-kiri">Datang tepat waktu, dilayani lebih cepat</h2>
    <div class="grid g4 mt">
      <div class="kartu-fitur"><span class="ic c-blue"><x-ico n="shield"/></span><b>Dokter resmi</b><p>Semua dokter terdaftar dengan Surat Tanda Registrasi yang berlaku.</p></div>
      <div class="kartu-fitur"><span class="ic c-green"><x-ico n="calendar"/></span><b>Jadwal selalu terbaru</b><p>Lihat dokter yang praktik hari ini sebelum berangkat.</p></div>
      <div class="kartu-fitur"><span class="ic c-violet"><x-ico n="chat"/></span><b>Tanya sebelum datang</b><p>Ceritakan keluhan, dokter memberi saran apakah perlu datang.</p></div>
      <div class="kartu-fitur"><span class="ic c-teal"><x-ico n="lock"/></span><b>Data Anda terjaga</b><p>Nama dan NIK tidak pernah tampil di layar umum. <a href="{{ route('privasi') }}">Baca kebijakan</a>.</p></div>
    </div>
    <div class="angka-grid">
      <div><b data-hitung="{{ $angka['pasien'] }}">{{ number_format($angka['pasien'], 0, ',', '.') }}</b><span>Pasien terdaftar</span></div>
      <div><b data-hitung="{{ $angka['dokter'] }}">{{ $angka['dokter'] }}</b><span>Dokter</span></div>
      <div><b data-hitung="{{ $angka['dilayani'] }}">{{ number_format($angka['dilayani'], 0, ',', '.') }}</b><span>Pasien dilayani bulan ini</span></div>
      <div><b data-hitung="{{ $angka['info'] }}">{{ $angka['info'] }}</b><span>Info kesehatan</span></div>
    </div>
  </div>
</section>

{{-- ============ LAYANAN ============ --}}
<section class="section container" id="layanan">
  <span class="eyebrow-kiri">Layanan</span>
  <h2 class="judul-kiri">Mulai dari yang Anda rasakan</h2>
  <div class="grid g4 mt">
    @foreach ($spesialisasi as $sp)
      <div class="kartu-layanan">
        <span class="ic {{ $sp->nama === 'Kandungan' ? 'c-violet' : 'c-blue' }}"><x-ico :n="$sp->nama === 'Kandungan' ? 'baby' : 'steth'"/></span>
        <b>{{ $sp->poli }}</b>
        <p>{{ $sp->nama === 'Kandungan' ? 'Pemeriksaan kehamilan, kesehatan ibu dan anak, serta KB.' : 'Keluhan sehari-hari seperti demam, batuk, pusing, dan pemeriksaan umum.' }}</p>
        <a href="{{ $masuk('pasien.antrian.create') }}">Ambil antrean →</a>
      </div>
    @endforeach
    <div class="kartu-layanan"><span class="ic c-green"><x-ico n="chat"/></span><b>Konsultasi online</b><p>Tanyakan keluhan ke dokter dari rumah sebelum memutuskan datang.</p><a href="{{ $masuk('pasien.konsultasi.create') }}">Mulai konsultasi →</a></div>
    <div class="kartu-layanan"><span class="ic c-teal"><x-ico n="pill"/></span><b>Resep & obat</b><p>Pantau resep sampai obat siap diambil di bagian farmasi.</p><a href="{{ $masuk('pasien.resep') }}">Cek resep →</a></div>
  </div>
</section>

{{-- ============ DOKTER ============ --}}
<section class="section latar-lembut">
  <div class="container">
    <div class="flex between">
      <div><span class="eyebrow-kiri">Dokter kami</span><h2 class="judul-kiri mb0">Kenali dokter sebelum bertemu</h2></div>
      <a href="{{ route('publik.dokter') }}" class="btn">Semua jadwal →</a>
    </div>
    <div class="grid g3 mt">
      @foreach ($dokter as $d)
        <div class="kartu-dokter">
          <div class="flex"><span class="avatar">{{ mb_substr(preg_replace('/^dr\.?\s*/i', '', $d['nama']), 0, 1) }}</span>
            <div><b>{{ $d['nama'] }}</b><span class="muted">{{ $d['spesialisasi'] === 'Kandungan' ? 'Dokter Kandungan' : 'Dokter Umum' }} · {{ $d['poli'] }}</span></div></div>
          @if ($d['hari_ini'])<span class="badge b-ok mt">Praktik hari ini {{ $d['hari_ini'] }}</span>@else<span class="badge mt">Tidak praktik hari ini</span>@endif
          <p class="muted mt mb0" style="font-size:13px">{{ $d['jadwal'] }}</p>
          <a href="{{ $masuk('pasien.antrian.create') }}" class="btn btn-primary btn-sm mt">Ambil antrean</a>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ============ CARA PAKAI ============ --}}
<section class="section container">
  <span class="eyebrow-kiri">Cara pakai</span>
  <h2 class="judul-kiri">Empat langkah sebelum datang</h2>
  <p class="sub-kiri">Langkahnya singkat, dan Anda selalu tahu apa yang harus dilakukan berikutnya. <a href="{{ route('alur') }}">Lihat alur lengkap →</a></p>
  <ol class="langkah4">
    <li><span>01</span><b>Daftar akun</b><p>Isi data sesuai KTP. Nomor rekam medis langsung dibuat.</p></li>
    <li><span>02</span><b>Tanya dokter (bila perlu)</b><p>Ceritakan keluhan, dokter memberi saran awal.</p></li>
    <li><span>03</span><b>Ambil antrean</b><p>Pilih poli dan tanggal, simpan tiket QR di HP.</p></li>
    <li><span>04</span><b>Datang & tunjukkan tiket</b><p>Pindai QR di loket, lalu tunggu panggilan.</p></li>
  </ol>
</section>

{{-- ============ KABAR ============ --}}
<section class="section latar-lembut">
  <div class="container">
    <div class="flex between"><div><span class="eyebrow-kiri">Kabar & info kesehatan</span><h2 class="judul-kiri mb0">Kabar terbaru dari Puskesmas</h2></div><a href="{{ route('info') }}" class="btn">Semua info →</a></div>
    <div class="grid g3 mt">
      @forelse ($pengumuman as $p)
        <article class="card"><span class="badge {{ ['agenda' => 'b-warn', 'edukasi' => 'b-ok'][$p->kategori] ?? 'b-info' }}">{{ ['agenda' => 'Agenda', 'edukasi' => 'Info sehat'][$p->kategori] ?? 'Pengumuman' }}</span>
          <h3 class="mt" style="font-size:17px">{{ $p->judul }}</h3><p class="muted">{{ \Illuminate\Support\Str::limit($p->isi, 130) }}</p>
          <small><x-ico n="calendar"/> {{ $p->tanggal->locale('id')->translatedFormat('d F Y') }}</small></article>
      @empty
        <div class="card empty">Belum ada kabar terbaru.</div>
      @endforelse
    </div>
  </div>
</section>

{{-- ============ TANYA JAWAB ============ --}}
<section class="section container" id="tanya">
  <span class="eyebrow-kiri">Tanya jawab</span>
  <h2 class="judul-kiri">Pertanyaan yang sering ditanyakan</h2>
  <div class="faq mt">
    <details><summary>Apakah aplikasi ini menggantikan pemeriksaan dokter?</summary><p>Tidak. Konsultasi online hanya saran awal. Diagnosis dan pengobatan tetap dilakukan saat Anda diperiksa langsung.</p></details>
    <details><summary>Apakah perlu akun untuk melihat antrean dan jadwal?</summary><p>Tidak perlu. Layar antrean dan jadwal dokter bisa dilihat siapa saja. Akun diperlukan untuk mengambil antrean, konsultasi, dan melihat resep.</p></details>
    <details><summary>Bagaimana jika saya tidak punya email atau HP?</summary><p>Datang langsung ke loket. Petugas akan mendaftarkan Anda dan memberi nomor antrean di tempat.</p></details>
    <details><summary>Apakah nama saya akan muncul di layar antrean?</summary><p>Tidak. Layar umum hanya menampilkan nomor antrean dan nama poli.</p></details>
    <details><summary>Saya lupa kata sandi, bagaimana?</summary><p>Pilih <a href="{{ route('password.request') }}">Lupa password</a>. Kode OTP dikirim ke email Anda untuk membuat kata sandi baru.</p></details>
    <details><summary>Apa yang harus dilakukan dalam keadaan darurat?</summary><p>Jangan menunggu antrean. Segera ke IGD terdekat atau hubungi <b>119</b>.</p></details>
  </div>
</section>

{{-- ============ BANTUAN ============ --}}
<section class="section container" id="bantuan">
  <div class="cta-band">
    <div><h2>Butuh bantuan?</h2><p>{{ $pengaturan['jam_layanan'] }}</p>
      <p class="mb0"><x-ico n="map"/> {{ $pengaturan['alamat'] }}</p></div>
    <div class="flex">
      <a class="btn btn-lg btn-putih" target="_blank" rel="noopener" href="https://wa.me/{{ $pengaturan['whatsapp'] }}"><x-ico n="chat"/> Chat WhatsApp</a>
      <a class="btn btn-lg btn-garis" href="tel:{{ preg_replace('/[^0-9+]/', '', $pengaturan['telepon']) }}"><x-ico n="phone"/> {{ $pengaturan['telepon'] }}</a>
      <a class="btn btn-lg btn-garis" target="_blank" rel="noopener" href="{{ $pengaturan['maps_url'] }}"><x-ico n="map"/> Petunjuk arah</a>
    </div>
  </div>
</section>
@endsection
@push('scripts')
<script>
(function () {
  const load = () => fetch(@json(route('live.antrian')), {headers: {Accept: 'application/json'}}).then(r => r.json()).then(d => {
    document.getElementById('live-time').textContent = 'pukul ' + d.diperbarui.slice(0, 5);
    const box = document.getElementById('live-poli'); box.innerHTML = '';
    d.poli.forEach(p => {
      const el = document.createElement('div'); el.className = 'poli-baris';
      el.innerHTML = '<div><b></b><span class="muted"></span></div><div class="poli-no"></div>';
      el.querySelector('b').textContent = p.poli;
      el.querySelector('span').textContent = `${p.menunggu} menunggu · ${p.selesai} selesai`;
      el.querySelector('.poli-no').textContent = p.sedang_dipanggil || '—';
      el.querySelector('.poli-no').title = 'Nomor yang sedang dipanggil';
      box.append(el);
    });
  }).catch(() => {});
  load(); setInterval(() => { if (!window.__wsTersambung) load(); }, 10000);
  document.addEventListener('pkm:berubah', e => { if (e.detail.includes('antrian')) load(); });
})();
</script>
@endpush
