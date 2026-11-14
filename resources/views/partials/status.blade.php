@php
  $map = ['menunggu' => 'b-warn', 'hadir' => 'b-info', 'dipanggil' => 'b-info', 'dilayani' => 'b-info', 'selesai' => 'b-ok', 'batal' => 'b-bad',
          'baru' => 'b-warn', 'diproses' => 'b-info', 'diserahkan' => 'b-ok', 'ditanggapi' => 'b-ok', 'aktif' => 'b-ok', 'nonaktif' => 'b-bad',
          'berhasil' => 'b-ok', 'gagal' => 'b-bad'];
@endphp
<span class="badge {{ $map[$s] ?? '' }}">{{ ucfirst($s) }}</span>

