@extends('layouts.app')
@section('title', 'Detail Antrian')
@section('content')
<div class="grid g2">
  <div class="ticket">
    <span class="badge b-info">{{ $a->spesialisasi->poli }}</span>
    <div class="big-no" id="no">{{ $a->nomor }}</div>
    <div id="st">@include('partials.status', ['s' => $a->status])</div>
    <p class="muted mt" id="pos">{{ in_array($a->status, ['menunggu','hadir']) ? $a->posisi().' antrean di depan Anda' : $a->labelStatus() }}</p>
    <p class="mb0">{{ $a->tanggal->locale('id')->translatedFormat('l, d F Y') }}</p>
    @if (in_array($a->status, ['menunggu']))@include('partials.qr', ['isi' => \App\Support\Hmac::qrTiket($a)])@endif
    <p class="mono muted">Kode: {{ $a->kode }}</p>
    <div class="flex" style="justify-content:center">
      <a class="btn btn-primary" href="{{ route('pasien.antrian.tiket', $a) }}"><x-ico n="download"/> Unduh tiket</a>
      <a class="btn" target="_blank" href="{{ route('pasien.antrian.cetak', $a) }}"><x-ico n="printer"/> Cetak / PDF</a>
    </div>
    @if ($a->status === 'menunggu')
      <form method="POST" action="{{ route('pasien.antrian.batal', $a) }}" class="mt" data-confirm="Nomor {{ $a->nomor }} akan dibatalkan dan tidak bisa dipakai lagi." data-confirm-title="Batalkan antrean?">@csrf
        <button class="btn btn-bad btn-sm">Batalkan antrean</button></form>
    @endif
  </div>
  <div class="card"><h3>Info kunjungan</h3>
    <dl class="kv"><dt>Nama</dt><dd>{{ $a->pasien->nama }}</dd><dt>No. RM</dt><dd>{{ $a->pasien->no_rm }}</dd><dt>Sumber</dt><dd>{{ ucfirst($a->sumber) }}</dd><dt>Keluhan</dt><dd>{{ $a->keluhan_awal ?: '-' }}</dd></dl>
    <div class="alert alert-info mt"><x-ico n="info"/><div>Status di halaman ini diperbarui otomatis. Bawa KTP dan tunjukkan tiket di loket untuk check-in.</div></div>
    <a href="{{ route('layar') }}" target="_blank" class="btn"><x-ico n="monitor"/> Lihat layar antrean</a>
  </div>
</div>
@endsection
@push('scripts')
<script>
(function () {
  let last = @json($a->status);
  const lbl = {menunggu:'b-warn',hadir:'b-info',dipanggil:'b-info',dilayani:'b-info',selesai:'b-ok',batal:'b-bad'};
  setInterval(() => fetch(@json(route('pasien.antrian.status', $a)), {headers:{Accept:'application/json'}}).then(r => r.json()).then(d => {
    document.getElementById('st').innerHTML = `<span class="badge ${lbl[d.status]||''}">${d.status[0].toUpperCase()+d.status.slice(1)}</span>`;
    document.getElementById('pos').textContent = ['menunggu','hadir'].includes(d.status) ? `${d.di_depan} antrean di depan Anda` : d.label;
    if (d.status !== last && d.status === 'dipanggil') { document.getElementById('no').classList.add('flash'); toast('warning', 'Nomor Anda dipanggil!', 'Silakan menuju ruang periksa.', 12000); }
    last = d.status;
  }).catch(() => {}), 7000);
})();
</script>
@endpush

