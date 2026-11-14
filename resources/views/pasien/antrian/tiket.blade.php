<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tiket Antrean {{ $a->nomor }}</title>
<style>
  body{font-family:system-ui,Segoe UI,sans-serif;background:#f4f7fc;color:#0e1a33;margin:0;padding:24px}
  .t{max-width:360px;margin:0 auto;background:#fff;border:2px dashed #b9c6dd;border-radius:18px;padding:24px;text-align:center}
  h1{font-size:16px;margin:0 0 4px}.no{font-size:80px;font-weight:800;color:#2563eb;line-height:1;margin:14px 0}
  .m{color:#5b6b88;font-size:13px}.qr-box{display:grid;place-items:center;margin:10px auto}.qr-box svg{width:170px;height:170px}.k{font-family:Consolas,monospace;font-size:11px;word-break:break-all;color:#5b6b88}
  table{width:100%;font-size:13px;text-align:left;margin-top:14px;border-collapse:collapse}td{padding:4px 0}td:first-child{color:#5b6b88}
  @media print{body{background:#fff}.np{display:none}}
</style></head>
<body>
<div class="t">
  <h1>{{ $namaPkm }}</h1><div class="m">Tiket Antrean {{ ucfirst($a->sumber) }}</div>
  <div class="no">{{ $a->nomor }}</div>
  <b>{{ $a->spesialisasi->poli }}</b>
  <table>
    <tr><td>Tanggal</td><td>{{ $a->tanggal->locale('id')->translatedFormat('l, d F Y') }}</td></tr>
    <tr><td>No. RM</td><td>{{ $a->pasien->no_rm }}</td></tr>
    <tr><td>Nama</td><td>{{ $a->pasien->namaSamaran() }}</td></tr>
    <tr><td>Dibuat</td><td>{{ $a->created_at->format('d/m/Y H:i') }}</td></tr>
  </table>
  @include('partials.qr', ['isi' => \App\Support\Hmac::qrTiket($a)])
  <p class="m">Pindai QR ini di loket / kiosk untuk check-in. Bawa KTP.</p>
  <div class="k">Kode verifikasi: {{ $a->kode }}</div>
  <p class="m" style="font-size:11px;margin-top:12px">Dibuat di Indonesia · © 2026 Seluruh hak cipta dilindungi · Grup NussCorp Technologies Indonesia</p>
  @if (!empty($cetak))<p class="np"><button onclick="print()">Cetak / Simpan PDF</button></p><script>setTimeout(()=>print(),400)</script>@endif
</div>
</body></html>

