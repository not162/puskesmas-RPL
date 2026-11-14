@extends('layouts.app')
@section('title', 'Check-in QR')
@section('content')
<div class="grid g2">
  <form class="card" method="POST" action="{{ route('loket.qr') }}" id="qr-form">@csrf
    <h3>Pindai QR tiket</h3>
    <p class="muted">Arahkan pemindai ke QR pada tiket pasien (atau ketik kode). Keaslian tiket diperiksa dengan tanda tangan digital; tiket palsu atau yang diubah otomatis ditolak.</p>
    <div class="field"><label for="qr">Isi QR</label><input id="qr" name="qr" class="input mono" autofocus autocomplete="off" placeholder="PKM1.xxxxxxxx.xxxxxxxx" required></div>
    <div class="flex"><button class="btn btn-primary"><x-ico n="check"/> Verifikasi & check-in</button>
      <button type="button" class="btn" id="cam-btn"><x-ico n="qr"/> Pakai kamera</button></div>
    <video class="scan mt" id="cam" playsinline muted hidden></video>
  </form>
  <div class="card"><h3>Check-in terakhir</h3>
    <div id="live-checkin" data-live="antrian"><ul class="list-plain">
      @forelse ($terakhir as $a)<li><span><b>{{ $a->nomor }}</b> {{ $a->pasien->nama }}<br><small>{{ $a->spesialisasi->poli }}</small></span><small>{{ $a->waktu_hadir->format('H:i') }}</small></li>
      @empty <li class="muted">Belum ada.</li>@endforelse</ul></div>
  </div>
</div>
@endsection
@push('scripts')
<script>
document.getElementById('cam-btn').onclick = async () => {
  if (!('BarcodeDetector' in window)) { toast('warning', 'Kamera tidak didukung', 'Gunakan pemindai USB atau ketik kode. (BarcodeDetector tersedia di Chrome/Edge.)'); return; }
  const v = document.getElementById('cam');
  try {
    const s = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
    v.srcObject = s; v.hidden = false; await v.play();
    const det = new BarcodeDetector({ formats: ['qr_code'] });
    const loop = async () => { const r = await det.detect(v).catch(() => []);
      if (r.length) { s.getTracks().forEach(t => t.stop()); document.getElementById('qr').value = r[0].rawValue; document.getElementById('qr-form').submit(); }
      else requestAnimationFrame(loop); };
    loop();
  } catch (e) { toast('danger', 'Kamera ditolak', 'Izinkan akses kamera untuk memindai QR.'); }
};
</script>
@endpush

