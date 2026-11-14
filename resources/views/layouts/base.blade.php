<!doctype html>
<html lang="id" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Beranda') — {{ $namaPkm }}</title>
  <script>
    (function () { var t = null; try { t = localStorage.getItem('tema'); } catch (e) {}
      document.documentElement.dataset.theme = t || (matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark'); })();
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=2">
  @stack('head')
</head>
<body class="@yield('body-class')">
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
  <script src="{{ asset('js/app.js') }}?v=2"></script>
  <script src="{{ asset('js/realtime.js') }}?v=2"></script>
  @stack('scripts')
</body>
</html>

