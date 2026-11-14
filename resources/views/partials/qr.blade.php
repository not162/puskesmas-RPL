{{-- QR berisi payload bertanda tangan HMAC: PKM1.<kode>.<sig> --}}
<div class="qr-box" data-qr="{{ $isi }}" aria-label="QR check-in"></div>
@once
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('[data-qr]').forEach(el => {
  try { const q = qrcode(0, 'M'); q.addData(el.dataset.qr); q.make(); el.innerHTML = q.createSvgTag({ cellSize: 4, margin: 2, scalable: true }); }
  catch (e) { el.textContent = el.dataset.qr; }
}));
</script>
@endonce

