{{-- QR dibuat di server (SVG) dari payload bertanda tangan HMAC: PKM1.<kode>.<sig> — tanpa CDN/JS --}}
@php
  $qrSvg = (new \BaconQrCode\Writer(new \BaconQrCode\Renderer\ImageRenderer(
      new \BaconQrCode\Renderer\RendererStyle\RendererStyle(180, 1),
      new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
  )))->writeString($isi);
  $qrSvg = preg_replace('/^<\?xml[^>]*>\s*/', '', $qrSvg);
@endphp
<div class="qr-box" aria-label="QR check-in" data-qr="{{ $isi }}">{!! $qrSvg !!}</div>

