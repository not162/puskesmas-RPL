@extends('layouts.public')
@section('title', 'Kebijakan Privasi')
@section('content')
<section class="section container" style="max-width:780px">
  <h1 class="section-title">Kebijakan Privasi</h1>
  <div class="card mt">
    <ol>
      <li>Data (NIK, nama, tanggal lahir, alamat, nomor HP, BPJS, foto KTP) hanya dipakai untuk pelayanan kesehatan di {{ $namaPkm }}.</li>
      <li>Halaman publik dan layar antrean hanya menampilkan nomor antrean dan nama poli. Nama, NIK, rekam medis, serta ID dokter tidak pernah ditampilkan.</li>
      <li>Foto KTP disimpan di penyimpanan privat dan hanya dapat dibuka petugas berwenang.</li>
      <li>Akses data dibatasi per peran (RBAC). Setiap perubahan data tercatat di log audit.</li>
      <li>Tiket antrean ditandatangani HMAC sehingga tidak dapat dipalsukan.</li>
      <li>Anda dapat meminta perbaikan data di loket dengan membawa KTP.</li>
    </ol>
  </div>
</section>
@endsection
