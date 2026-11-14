@extends('layouts.public')
@section('title', 'Kebijakan Privasi & Keamanan Data')
@section('content')
<section class="section container prosa">
  <span class="eyebrow-kiri">Kebijakan Privasi</span>
  <h1>Bagaimana kami menjaga data Anda</h1>
  <p class="lead-kiri">{{ $namaPkm }} mengelola data pasien untuk pelayanan kesehatan. Halaman ini menjelaskan data apa yang kami simpan, untuk apa, siapa yang boleh melihatnya, dan apa hak Anda. Berlaku sejak {{ \Carbon\Carbon::parse('2026-11-15')->locale('id')->translatedFormat('d F Y') }}.</p>

  <div class="ringkas-privasi">
    <div><x-ico n="shield"/><b>Hanya untuk berobat</b><span>Data dipakai untuk pelayanan, tidak dijual dan tidak dipakai untuk iklan.</span></div>
    <div><x-ico n="lock"/><b>Akses terbatas</b><span>Petugas hanya melihat data yang dibutuhkan untuk tugasnya.</span></div>
    <div><x-ico n="eye"/><b>Tidak tampil di publik</b><span>Layar antrean hanya menampilkan nomor, bukan nama atau NIK.</span></div>
    <div><x-ico n="user"/><b>Anda punya hak</b><span>Melihat, mengunduh, memperbaiki, dan menarik persetujuan.</span></div>
  </div>

  <h2>1. Dasar hukum dan acuan</h2>
  <ul>
    <li><b>Undang-Undang No. 27 Tahun 2022 tentang Pelindungan Data Pribadi (UU PDP).</b> Data kesehatan termasuk data pribadi yang bersifat spesifik dan mendapat perlindungan lebih ketat.</li>
    <li><b>Undang-Undang No. 11 Tahun 2008 tentang Informasi dan Transaksi Elektronik</b> beserta perubahannya (UU No. 19 Tahun 2016 dan UU No. 1 Tahun 2024) atau <b>UU ITE</b>, termasuk kewajiban menjaga keamanan sistem elektronik.</li>
    <li><b>Undang-Undang No. 17 Tahun 2023 tentang Kesehatan</b>, termasuk kewajiban menjaga rahasia kesehatan pasien.</li>
    <li><b>Peraturan Menteri Kesehatan No. 24 Tahun 2022 tentang Rekam Medis</b>, termasuk rekam medis elektronik, dan Peraturan Menteri Kesehatan terbaru yang berlaku{{ $pengaturan['permenkes_tambahan'] ? ' ('.$pengaturan['permenkes_tambahan'].')' : '' }}.</li>
    <li><b>Standar ISO/IEC 27001:2022</b> tentang sistem manajemen keamanan informasi, sebagai acuan cara kami mengamankan data (lihat bagian 7). Aplikasi ini <i>mengacu</i> pada standar tersebut; sertifikasi resmi dilakukan terpisah oleh lembaga sertifikasi.</li>
  </ul>

  <h2>2. Data yang kami kumpulkan</h2>
  <div class="table-wrap"><table>
    <tr><th>Jenis data</th><th>Contoh</th><th>Untuk apa</th></tr>
    <tr><td>Identitas</td><td>NIK, Nomor KK, nama, tanggal lahir, jenis kelamin, alamat</td><td>Memastikan Anda pasien yang benar dan mencegah data ganda</td></tr>
    <tr><td>Kontak</td><td>Email, nomor HP/WhatsApp</td><td>Mengirim kode OTP saat Anda lupa kata sandi dan pemberitahuan antrean (WhatsApp hanya jika Anda setuju)</td></tr>
    <tr><td>Kepesertaan</td><td>Nomor BPJS (jika diisi), foto KTP (jika diunggah)</td><td>Administrasi pelayanan</td></tr>
    <tr><td>Kesehatan</td><td>Keluhan, diagnosis, resep, riwayat kunjungan</td><td>Pemeriksaan dan pengobatan oleh dokter dan apoteker</td></tr>
    <tr><td>Catatan keamanan</td><td>Waktu masuk, alamat IP, perangkat tepercaya</td><td>Mencegah penyalahgunaan akun</td></tr>
  </table></div>

  <h2>3. Siapa yang boleh melihat data Anda</h2>
  <ul>
    <li><b>Dokter</b> hanya melihat pasien di poli tempatnya bertugas (Poli Umum atau Poli KIA & Kandungan).</li>
    <li><b>Apoteker</b> melihat resep yang harus disiapkan.</li>
    <li><b>Petugas loket</b> melihat identitas untuk pendaftaran, tetapi tidak melihat diagnosis.</li>
    <li><b>Pengelola Puskesmas</b> mengatur akun dan melihat laporan bulanan berupa angka ringkasan.</li>
    <li>Kami <b>tidak membagikan</b> data kepada pihak lain, kecuali diwajibkan undang-undang (misalnya pelaporan kesehatan ke Dinas Kesehatan) atau atas persetujuan Anda.</li>
  </ul>

  <h2>4. Berapa lama data disimpan</h2>
  <p>Rekam medis disimpan sesuai Permenkes No. 24 Tahun 2022 (paling singkat 25 tahun sejak kunjungan terakhir). Karena itu catatan pemeriksaan tidak dapat dihapus sebelum waktunya. Data akun (email, nomor HP) dan persetujuan WhatsApp dapat diubah atau ditarik kapan saja.</p>

  <h2>5. Hak Anda (UU PDP)</h2>
  <div class="hak-grid">
    <div><b>Mendapat informasi</b><span>Mengetahui data apa yang kami simpan dan untuk apa (halaman ini).</span></div>
    <div><b>Melihat & mengunduh</b><span>Menu <i>Profil</i> → <i>Unduh data saya</i>.</span></div>
    <div><b>Memperbaiki</b><span>Ubah email, HP, alamat di Profil. Data KTP diperbaiki di loket.</span></div>
    <div><b>Menarik persetujuan</b><span>Matikan WhatsApp di Profil kapan saja.</span></div>
    <div><b>Meminta penghapusan</b><span>Kirim lewat menu <i>Pengaduan</i> kategori <i>Data pribadi</i>, kecuali rekam medis yang wajib disimpan.</span></div>
    <div><b>Mengajukan keberatan</b><span>Lewat menu <i>Pengaduan</i> atau datang ke Puskesmas. Kami tanggapi paling lambat 3 x 24 jam hari kerja.</span></div>
  </div>

  <h2>6. Jika terjadi kebocoran data</h2>
  <p>Bila terjadi kegagalan pelindungan data, kami memberi tahu Anda dan lembaga yang berwenang paling lambat <b>3 x 24 jam</b> sesuai UU PDP, beserta data yang terdampak, waktu kejadian, dan langkah penanganannya.</p>

  <h2>7. Cara kami mengamankan data (acuan ISO/IEC 27001:2022)</h2>
  <div class="table-wrap"><table>
    <tr><th>Yang kami lakukan</th><th>Penjelasan sederhana</th><th>Kontrol ISO 27001:2022</th></tr>
    <tr><td>Hak akses per peran</td><td>Setiap petugas hanya membuka menu sesuai tugasnya</td><td class="mono">5.15, 8.2, 8.3</td></tr>
    <tr><td>Masuk yang aman</td><td>Kode OTP ke email untuk mengatur ulang kata sandi, akun dikunci sementara jika salah kata sandi berulang kali, verifikasi anti-robot</td><td class="mono">5.17, 8.5</td></tr>
    <tr><td>Kata sandi tersimpan acak</td><td>Kata sandi disimpan dalam bentuk acak (hash), petugas pun tidak bisa membacanya</td><td class="mono">8.24</td></tr>
    <tr><td>Tiket anti-palsu</td><td>QR tiket diberi tanda tangan digital; tiket yang diubah otomatis ditolak</td><td class="mono">8.24</td></tr>
    <tr><td>Data disamarkan</td><td>Layar umum hanya menampilkan nomor antrean; NIK disamarkan di layar</td><td class="mono">8.11, 8.12</td></tr>
    <tr><td>Semua perubahan tercatat</td><td>Setiap tambah, ubah, hapus data, dan pembukaan foto KTP tercatat dengan waktu dan pelakunya</td><td class="mono">8.15, 8.16</td></tr>
    <tr><td>Data pribadi dilindungi</td><td>Foto KTP disimpan di tempat tertutup, bukan di folder publik</td><td class="mono">5.34</td></tr>
    <tr><td>Cadangan data</td><td>Data dicadangkan secara berkala (dijadwalkan saat aplikasi dipasang di server Puskesmas)</td><td class="mono">8.13</td></tr>
  </table></div>

  <h2>8. Cookie</h2>
  <p>Kami hanya memakai cookie yang wajib agar Anda tetap masuk dan aman (sesi, perlindungan formulir, perangkat tepercaya). Tidak ada cookie iklan atau pelacak pihak ketiga.</p>

  <h2>9. Hubungi kami</h2>
  <p>Petugas pelindungan data {{ $namaPkm }}: {{ $pengaturan['email'] }} · {{ $pengaturan['telepon'] }} · {{ $pengaturan['alamat'] }}.</p>
</section>
@endsection
