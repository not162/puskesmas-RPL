@extends('layouts.app')
@section('title', 'Hak Akses Peran')
@section('content')
<div class="page-h"><div><h2>Matriks izin (RBAC)</h2><p>Tabel peran ↔ izin (peran_izin). Super Admin selalu memiliki semua izin.</p></div></div>
<form method="POST" action="{{ route('admin.peran') }}">@csrf @method('PUT')
<div class="table-wrap"><table>
  <tr><th>Izin</th>@foreach ($peran as $p)<th style="text-align:center">{{ $p->label }}<br><small>{{ $p->akun_count }} akun</small></th>@endforeach</tr>
  @foreach ($izin as $i)
    <tr><td><b class="mono">{{ $i->kode_izin }}</b><br><small>{{ $i->deskripsi }}</small></td>
      @foreach ($peran as $p)
        <td style="text-align:center"><input type="checkbox" name="izin[{{ $p->id_peran }}][]" value="{{ $i->id_izin }}" style="width:18px;height:18px;accent-color:var(--brand)"
          @checked($p->nama_peran === 'super_admin' || $p->izin->contains('id_izin', $i->id_izin)) @disabled($p->nama_peran === 'super_admin')></td>
      @endforeach</tr>
  @endforeach
</table></div>
<button class="btn btn-primary mt" >Simpan hak akses</button>
</form>
@endsection

