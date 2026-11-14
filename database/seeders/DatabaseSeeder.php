<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Akun;
use App\Models\Apoteker;
use App\Models\Dokter;
use App\Models\Izin;
use App\Models\JadwalPraktik;
use App\Models\Obat;
use App\Models\Pasien;
use App\Models\Pengaturan;
use App\Models\Pengumuman;
use App\Models\Peran;
use App\Models\PetugasLoket;
use App\Models\Spesialisasi;
use App\Models\TemplateNotifikasi;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Kata sandi awal tiap peran BERBEDA dan dibaca dari .env (SEED_PASSWORD_<PERAN>),
     * tidak pernah ditulis di kode maupun ditampilkan di halaman login.
     */
    private function sandi(string $peran): string
    {
        $key = 'SEED_PASSWORD_'.strtoupper($peran);
        $v = env($key);
        if (! $v || strlen($v) < 8) {
            throw new RuntimeException("Atur $key (min. 8 karakter) di file .env sebelum menjalankan seeder.");
        }
        return $v;
    }

    public function run(): void
    {
        // ---- Izin (RBAC) ----
        $izin = [
            'akun.kelola' => 'Kelola akun staf & pasien',
            'peran.kelola' => 'Atur izin tiap peran',
            'audit.lihat' => 'Lihat audit & log login',
            'pengaturan.kelola' => 'Ubah pengaturan Puskesmas & poli',
            'template.kelola' => 'Ubah template notifikasi',
            'notifikasi.broadcast' => 'Kirim alert/broadcast ke peran',
            'jadwal.kelola' => 'Kelola jadwal praktik dokter',
            'laporan.lihat' => 'Lihat & ekspor laporan bulanan',
            'pengumuman.kelola' => 'Kelola pengumuman',
            'pengaduan.kelola' => 'Tanggapi pengaduan',
            'pengaduan.buat' => 'Kirim pengaduan',
            'pasien.lihat' => 'Lihat data pasien',
            'pasien.daftar' => 'Daftarkan pasien di loket',
            'antrian.kelola' => 'Check-in (QR), panggil, batalkan antrean',
            'antrian.ambil' => 'Ambil antrean online',
            'konsultasi.ajukan' => 'Ajukan konsultasi online',
            'konsultasi.tanggapi' => 'Tanggapi konsultasi & isi diagnosis',
            'resep.buat' => 'Terbitkan resep',
            'resep.proses' => 'Proses & serahkan resep',
            'obat.kelola' => 'Kelola data & stok obat',
            'obat.lihat' => 'Lihat daftar obat',
        ];
        foreach ($izin as $kode => $desk) {
            Izin::updateOrCreate(['kode_izin' => $kode], ['deskripsi' => $desk]);
        }

        // ---- 7 peran ----
        $dokterIzin = ['pasien.lihat', 'konsultasi.tanggapi', 'resep.buat', 'obat.lihat'];
        $peran = [
            Peran::SUPER_ADMIN => ['Super Admin (Puskesmas)', array_keys($izin)],
            Peran::ADMIN => ['Admin Puskesmas', ['laporan.lihat', 'pengumuman.kelola', 'jadwal.kelola', 'pengaduan.kelola', 'pasien.lihat', 'notifikasi.broadcast']],
            Peran::DOKTER_UMUM => ['Dokter Umum', $dokterIzin],
            Peran::DOKTER_KANDUNGAN => ['Dokter Kandungan', $dokterIzin],
            Peran::APOTEKER => ['Apoteker / Farmasi', ['resep.proses', 'obat.kelola', 'obat.lihat']],
            Peran::LOKET => ['Petugas Loket', ['pasien.lihat', 'pasien.daftar', 'antrian.kelola', 'pengaduan.kelola']],
            Peran::PASIEN => ['Pasien', ['antrian.ambil', 'konsultasi.ajukan', 'pengaduan.buat']],
        ];
        foreach ($peran as $nama => [$label, $kode]) {
            $p = Peran::updateOrCreate(['nama_peran' => $nama], ['label' => $label]);
            $p->izin()->sync(Izin::whereIn('kode_izin', $kode)->pluck('id_izin'));
        }
        $idPeran = fn ($n) => Peran::where('nama_peran', $n)->value('id_peran');

        $umum = Spesialisasi::updateOrCreate(['nama' => 'Umum'], ['poli' => 'Poli Umum']);
        $kand = Spesialisasi::updateOrCreate(['nama' => 'Kandungan'], ['poli' => 'Poli KIA & Kandungan']);

        $akun = fn (string $username, string $email, string $peran) => Akun::updateOrCreate(
            ['username' => $username],
            ['email' => $email, 'id_peran' => $idPeran($peran), 'password' => $this->sandi($peran), 'status' => 'aktif'],
        );

        $super = Admin::updateOrCreate(['id_akun' => $akun('superadmin', 'superadmin@puskesmas.test', Peran::SUPER_ADMIN)->id_akun], ['nama' => 'Kepala Puskesmas']);
        Admin::updateOrCreate(['id_akun' => $akun('admin', 'admin@puskesmas.test', Peran::ADMIN)->id_akun], ['nama' => 'Admin Tata Usaha']);

        $dr = [
            ['dr.umum', 'dokter.umum@puskesmas.test', Peran::DOKTER_UMUM, $umum, 'dr. Rina Kartika', 'STR-3171-0001', [[1, '08:00', '14:00'], [2, '08:00', '14:00'], [3, '08:00', '14:00'], [4, '08:00', '14:00'], [5, '08:00', '11:00'], [6, '08:00', '12:00']]],
            ['dr.umum2', 'dokter.umum2@puskesmas.test', Peran::DOKTER_UMUM, $umum, 'dr. Bayu Pratama', 'STR-3171-0002', [[1, '13:00', '17:00'], [3, '13:00', '17:00'], [5, '13:00', '17:00']]],
            ['dr.kandungan', 'dokter.kandungan@puskesmas.test', Peran::DOKTER_KANDUNGAN, $kand, 'dr. Sari Wulandari, Sp.OG', 'STR-3171-0003', [[1, '09:00', '13:00'], [3, '09:00', '13:00'], [5, '09:00', '11:00']]],
        ];
        foreach ($dr as [$u, $e, $p, $sp, $nama, $str, $jadwal]) {
            $d = Dokter::updateOrCreate(['id_akun' => $akun($u, $e, $p)->id_akun], [
                'id_spesialisasi' => $sp->id_spesialisasi, 'id_admin' => $super->id_admin, 'nama' => $nama, 'no_str' => $str,
            ]);
            foreach ($jadwal as [$h, $m, $s]) {
                JadwalPraktik::updateOrCreate(['id_dokter' => $d->id_dokter, 'hari' => $h, 'jam_mulai' => $m], ['jam_selesai' => $s, 'kuota' => 30, 'aktif' => true]);
            }
        }

        Apoteker::updateOrCreate(['id_akun' => $akun('apoteker', 'apoteker@puskesmas.test', Peran::APOTEKER)->id_akun], ['nama' => 'apt. Dewi Lestari, S.Farm', 'no_sipa' => 'SIPA-0001']);
        PetugasLoket::updateOrCreate(['id_akun' => $akun('loket', 'loket@puskesmas.test', Peran::LOKET)->id_akun], ['nama' => 'Andi Loket']);

        Pasien::updateOrCreate(['nik' => '3171010101900001'], [
            'id_akun' => $akun('pasien', 'pasien@puskesmas.test', Peran::PASIEN)->id_akun,
            'no_rm' => 'RM-000001', 'no_bpjs' => '0001234567890', 'nama' => 'Budi Santoso', 'tgl_lahir' => '1990-01-01', 'jenis_kelamin' => 'L',
            'alamat' => 'Jl. Melati No. 5', 'no_tlp' => '081234567890',
        ]);

        foreach ([['Paracetamol 500 mg', 'tablet', 500], ['Amoxicillin 500 mg', 'kapsul', 300], ['Asam Folat 400 mcg', 'tablet', 400],
                  ['Tablet Tambah Darah (Fe)', 'tablet', 600], ['Antasida DOEN', 'tablet', 250], ['CTM 4 mg', 'tablet', 200],
                  ['Vitamin B6', 'tablet', 300], ['Oralit', 'sachet', 150], ['Kalsium Laktat', 'tablet', 350], ['Ibuprofen 400 mg', 'tablet', 8]] as [$n, $s, $st]) {
            Obat::updateOrCreate(['nama_obat' => $n], ['satuan' => $s, 'stok' => $st]);
        }

        foreach (config('alur.langkah') as $no => $l) {
            TemplateNotifikasi::firstOrCreate(['langkah' => $no], ['judul' => $l['judul'], 'pesan' => $l['notif'], 'aktif' => true]);
        }
        foreach (Pengaturan::BAWAAN as $k => $v) {
            Pengaturan::firstOrCreate(['kunci' => $k], ['nilai' => $v]);
        }

        Pengumuman::updateOrCreate(['judul' => 'Antrean online kini tersedia'], ['isi' => 'Ambil nomor antrean dari rumah, tunjukkan QR tiket di loket, dan pantau layar antrean secara langsung.', 'kategori' => 'pengumuman', 'tanggal' => now()->toDateString()]);
        Pengumuman::updateOrCreate(['judul' => 'Kelas ibu hamil'], ['isi' => 'Setiap Sabtu pukul 09.00 di aula Puskesmas bersama bidan dan dokter kandungan.', 'kategori' => 'agenda', 'tanggal' => now()->addDays(3)->toDateString()]);
        Pengumuman::updateOrCreate(['judul' => 'Imunisasi dasar lengkap'], ['isi' => 'Bawa buku KIA saat jadwal imunisasi anak di Poli KIA.', 'kategori' => 'edukasi', 'tanggal' => now()->toDateString()]);
    }
}
