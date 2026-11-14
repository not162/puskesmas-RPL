<!doctype html>
<html lang="id" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Beranda') · {{ $namaPkm }}</title>
  <meta name="description" content="{{ $namaPkm }}: ambil antrean dari rumah, konsultasi dengan dokter, dan pantau panggilan antrean.">
  <meta name="theme-color" content="#2563eb">
  <link rel="icon" href="{{ asset('images/logo-puskesmas.svg') }}" type="image/svg+xml">
  <script>
    (function () { var t = null; try { t = localStorage.getItem('tema'); } catch (e) {}
      document.documentElement.dataset.theme = t || (matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
      var s = null; try { s = sessionStorage.getItem('pembuka'); } catch (e) {}
      if (s) document.documentElement.classList.add('tanpa-pembuka'); })();
  </script>
  <link rel="preload" href="{{ asset('vendor/fonts/plus-jakarta-sans-latin-600.woff2') }}" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=5">
  <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v=4">
  @stack('head')
</head>
<body class="@yield('body-class')">
  <div id="pembuka" role="status" aria-live="polite" aria-label="Memuat halaman">
    <div class="pembuka-isi">
      <img src="{{ asset('images/logo-puskesmas.svg') }}" alt="" width="88" height="88">
      <b>{{ $namaPkm }}</b>
      <span class="pembuka-sub">Pusat Kesehatan Masyarakat · {{ $pengaturan['kota'] }}</span>
      <p class="pembuka-slogan">{{ $pengaturan['slogan'] }}</p>
      <span class="pembuka-titik" aria-hidden="true"><i></i><i></i><i></i></span>
    </div>
    <button type="button" class="pembuka-lewati" id="pembuka-lewati">Lewati</button>
  </div>
  @yield('body')

  <div class="toasts" aria-live="polite"></div>

  <div class="modal" id="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="cm-title">
    <div class="modal-box">
      <div class="ic c-red" id="cm-icon"><x-ico n="help"/></div>
      <h3 id="cm-title">Konfirmasi</h3>
      <p class="muted" id="cm-text"></p>
      <div class="modal-actions">
        <button type="button" class="btn" id="cm-cancel">Batal</button>
        <button type="button" class="btn btn-bad" id="cm-ok">Ya, lanjutkan</button>
      </div>
    </div>
  </div>

  @php
    $alerts = [];
    if (session('alert')) $alerts[] = session('alert');
    if ($errors->any()) $alerts[] = ['tipe' => 'danger', 'judul' => 'Periksa kembali isian', 'pesan' => $errors->first()];
  @endphp
  <script>
    window.__ALERTS = @json($alerts);
    @auth
    window.__NOTIF_FEED = @json(route('notifikasi.feed'));
    window.__NOTIF_READ = @json(route('notifikasi.baca', '__id__'));
    @endauth
  </script>
  @php
    $ws = config('broadcasting.default') === 'reverb' && config('broadcasting.connections.reverb.key') ? [
      'key' => config('broadcasting.connections.reverb.key'),
      'host' => config('broadcasting.connections.reverb.options.host') === 'localhost' ? request()->getHost() : config('broadcasting.connections.reverb.options.host'),
      'port' => (int) config('broadcasting.connections.reverb.options.port'),
      'tls' => config('broadcasting.connections.reverb.options.scheme') === 'https',
      'akun' => auth()->id(),
      'staf' => auth()->check() && ! auth()->user()->hasRole('pasien'),
    ] : null;
  @endphp
  <script>window.__WS = @json($ws);</script>
  <script src="{{ asset('js/loader.js') }}?v=1"></script>
  <script src="{{ asset('js/app.js') }}?v=5"></script>
  @if ($ws)
  <script src="{{ asset('vendor/pusher.min.js') }}"></script>
  <script src="{{ asset('vendor/echo.iife.js') }}"></script>
  <script src="{{ asset('js/websocket.js') }}?v=2"></script>
  @endif
  <script src="{{ asset('js/realtime.js') }}?v=3"></script>
  @stack('scripts')
</body>
</html>

