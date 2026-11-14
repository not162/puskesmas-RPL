<div class="grid g4">
  @foreach ($lap['ringkasan'] as $k => $v)
    <div class="card stat"><div><b>{{ $v }}</b><span>{{ ucwords(str_replace('_', ' ', $k)) }}</span></div></div>
  @endforeach
</div>
<div class="card mt"><h3>Kunjungan harian</h3>
  @php $max = max(1, max($lap['harian'])); @endphp
  <div class="bars">@foreach ($lap['harian'] as $tgl => $n)<div class="bar" title="{{ $tgl }}: {{ $n }}"><i style="height:{{ max(2, $n / $max * 100) }}%"></i>{{ $tgl }}</div>@endforeach</div></div>
<div class="grid g2 mt">
  <div class="card"><h3>Kunjungan per poli</h3><div class="table-wrap"><table><tr><th>Poli</th><th>Total</th><th>Online</th><th>Loket</th></tr>
    @forelse ($lap['per_poli'] as $r)<tr><td>{{ $r['poli'] }}</td><td>{{ $r['total'] }}</td><td>{{ $r['online'] }}</td><td>{{ $r['loket'] }}</td></tr>@empty<tr><td colspan="4" class="empty">Tidak ada data</td></tr>@endforelse</table></div></div>
  <div class="card"><h3>Layanan per dokter</h3><div class="table-wrap"><table><tr><th>Dokter</th><th>Spesialisasi</th><th>Periksa</th><th>Online</th></tr>
    @forelse ($lap['per_dokter'] as $r)<tr><td>{{ $r['dokter'] }}</td><td>{{ $r['spesialisasi'] }}</td><td>{{ $r['pemeriksaan'] }}</td><td>{{ $r['online'] }}</td></tr>@empty<tr><td colspan="4" class="empty">Tidak ada data</td></tr>@endforelse</table></div></div>
</div>
<div class="card mt"><h3>Obat keluar</h3><div class="table-wrap"><table><tr><th>Obat</th><th>Satuan</th><th>Jumlah keluar</th><th>Sisa stok</th></tr>
  @forelse ($lap['obat_keluar'] as $r)<tr><td>{{ $r['obat'] }}</td><td>{{ $r['satuan'] }}</td><td>{{ $r['jumlah'] }}</td><td>{{ $r['sisa_stok'] }}</td></tr>@empty<tr><td colspan="4" class="empty">Tidak ada data</td></tr>@endforelse</table></div></div>

