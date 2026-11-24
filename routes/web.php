<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dokter\PraktikController;
use App\Http\Controllers\Dokter\RiwayatController;
use App\Http\Controllers\Farmasi;
use App\Http\Controllers\KtpController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\Loket;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\Pasien;
use App\Http\Controllers\PengaduanController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\RealtimeController;
use App\Models\Peran;
use Illuminate\Support\Facades\Route;

/* ---------- Publik (pola Zavora: tanpa ID internal / data pribadi) ---------- */
Route::controller(PublicController::class)->group(function () {
    Route::get('/', 'beranda')->name('beranda');
    Route::get('/layar-antrean', 'layarAntrian')->name('layar');
    Route::get('/live/antrian', 'liveAntrian')->name('live.antrian');
    Route::get('/live/statistik', 'stats')->name('live.stats');
    Route::get('/jadwal', 'dokter')->name('publik.dokter');
    Route::get('/alur-layanan', 'alur')->name('alur');
    Route::get('/informasi', 'info')->name('info');
});
Route::redirect('/layar-antrian', '/layar-antrean');
Route::redirect('/dokter', '/jadwal');
Route::view('/kebijakan-privasi', 'public.privasi')->name('privasi');
Route::view('/api/docs', 'swagger')->name('swagger');
Route::get('/avatar/{akun}', [ProfilController::class, 'avatar'])->name('avatar');

/* ---------- Gateway login (semua peran) & registrasi (pasien saja) ---------- */
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'formLogin'])->name('login');
    Route::get('/pasien/masuk', [AuthController::class, 'formLoginPasien'])->name('login.pasien');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1,login');
    Route::get('/register', [AuthController::class, 'formRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1,register');
    Route::get('/register/cek', [AuthController::class, 'cekRegister'])->middleware('throttle:40,1,register-cek')->name('register.cek');
    Route::get('/register/verifikasi', [AuthController::class, 'formOtpRegister'])->name('register.otp');
    Route::post('/register/verifikasi', [AuthController::class, 'verifikasiOtpRegister'])->middleware('throttle:10,1,register-verifikasi');
    Route::post('/register/kirim-ulang', [AuthController::class, 'kirimUlangOtpRegister'])->middleware('throttle:5,1,register-kirim-ulang')->name('register.otp.ulang');
    Route::get('/login/verifikasi', [AuthController::class, 'formOtpLogin'])->name('login.otp');
    Route::post('/login/verifikasi', [AuthController::class, 'verifikasiOtpLogin'])->middleware('throttle:10,1,login-verifikasi');
    Route::post('/login/kirim-ulang', [AuthController::class, 'kirimUlangOtpLogin'])->middleware('throttle:5,1,login-kirim-ulang')->name('login.otp.ulang');
    Route::get('/lupa-password/verifikasi', [AuthController::class, 'formOtpReset'])->name('password.otp');
    Route::post('/lupa-password/verifikasi', [AuthController::class, 'simpanResetOtp'])->middleware('throttle:10,1,lupa-password-verifikasi');
    Route::post('/lupa-password/kirim-ulang', [AuthController::class, 'kirimUlangOtpReset'])->middleware('throttle:5,1,lupa-password-kirim-ulang')->name('password.otp.ulang');
    Route::get('/lupa-password', [AuthController::class, 'formLupa'])->name('password.request');
    Route::post('/lupa-password', [AuthController::class, 'kirimLupa'])->middleware('throttle:5,1,lupa-password')->name('password.email');
});

Route::middleware(['auth', 'aktif'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/realtime/versi', RealtimeController::class)->name('realtime');

    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi');
    Route::get('/notifikasi/feed', [NotifikasiController::class, 'feed'])->name('notifikasi.feed');
    Route::get('/notifikasi/{id}/baca', [NotifikasiController::class, 'baca'])->name('notifikasi.baca');
    Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.bacaSemua');

    Route::get('/profil', [ProfilController::class, 'edit'])->name('profil');
    Route::put('/profil', [ProfilController::class, 'update']);
    Route::get('/profil/unduh-data', [ProfilController::class, 'unduhData'])->middleware('throttle:5,1,unduh-data')->name('profil.unduh');

    /* ----- Pasien ----- */
    Route::middleware('peran:'.Peran::PASIEN)->prefix('pasien')->name('pasien.')->group(function () {
        Route::middleware('izin:antrian.ambil')->group(function () {
            Route::get('antrean', [Pasien\AntrianController::class, 'index'])->name('antrian.index');
            Route::get('daftar-berobat', [Pasien\AntrianController::class, 'create'])->name('antrian.create');
            Route::post('antrean', [Pasien\AntrianController::class, 'store'])->name('antrian.store');
            Route::get('antrean/{antrian}', [Pasien\AntrianController::class, 'show'])->name('antrian.show');
            Route::get('antrean/{antrian}/status', [Pasien\AntrianController::class, 'status'])->name('antrian.status');
            Route::get('antrean/{antrian}/tiket', [Pasien\AntrianController::class, 'tiket'])->name('antrian.tiket');
            Route::get('antrean/{antrian}/cetak', [Pasien\AntrianController::class, 'cetak'])->name('antrian.cetak');
            Route::post('antrean/{antrian}/batal', [Pasien\AntrianController::class, 'batal'])->name('antrian.batal');
        });
        Route::middleware('izin:konsultasi.ajukan')->group(function () {
            Route::get('riwayat', [Pasien\KonsultasiController::class, 'index'])->name('konsultasi.index');
            Route::get('konsultasi/baru', [Pasien\KonsultasiController::class, 'create'])->name('konsultasi.create');
            Route::post('konsultasi', [Pasien\KonsultasiController::class, 'store'])->name('konsultasi.store');
            Route::get('konsultasi/{konsultasi}', [Pasien\KonsultasiController::class, 'show'])->name('konsultasi.show');
        });
        Route::get('resep', [Pasien\ResepController::class, 'index'])->name('resep');
        Route::middleware('izin:pengaduan.buat')->group(function () {
            Route::get('pengaduan', [Pasien\PengaduanController::class, 'index'])->name('pengaduan');
            Route::post('pengaduan', [Pasien\PengaduanController::class, 'store'])->name('pengaduan.store');
        });
    });

    /* ----- Loket ----- */
    Route::prefix('loket')->name('loket.')->group(function () {
        Route::middleware('izin:antrian.kelola')->group(function () {
            Route::get('antrean', [Loket\AntrianController::class, 'index'])->name('antrian');
            Route::post('antrean/{antrian}/checkin', [Loket\AntrianController::class, 'checkin'])->name('antrian.checkin');
            Route::post('antrean/{antrian}/panggil', [Loket\AntrianController::class, 'panggil'])->name('antrian.panggil');
            Route::post('antrean/{antrian}/batal', [Loket\AntrianController::class, 'batal'])->name('antrian.batal');
            Route::get('checkin-qr', [Loket\CheckinQrController::class, 'form'])->name('qr');
            Route::post('checkin-qr', [Loket\CheckinQrController::class, 'proses'])->middleware('throttle:60,1,checkin-qr');
        });
        Route::middleware('izin:pasien.daftar')->group(function () {
            Route::get('pendaftaran', [Loket\CheckinQrController::class, 'pendaftaran'])->name('pendaftaran');
            Route::resource('pasien', Loket\PasienController::class)->except('show', 'destroy');
            Route::post('pasien/{pasien}/antrean', [Loket\PasienController::class, 'antrian'])->name('pasien.antrian');
        });
    });
    Route::get('pasien-ktp/{pasien}', KtpController::class)->middleware('izin:pasien.daftar')->name('ktp');

    /* ----- Pengaduan (loket, admin, super admin) ----- */
    Route::resource('pengaduan', PengaduanController::class)->only('index', 'show', 'update', 'destroy')->middleware('izin:pengaduan.kelola')->parameters(['pengaduan' => 'pengaduan']);

    /* ----- Dokter (umum & kandungan) ----- */
    Route::middleware(['peran:'.Peran::DOKTER_UMUM.','.Peran::DOKTER_KANDUNGAN, 'izin:konsultasi.tanggapi'])->prefix('praktik')->name('dokter.')->group(function () {
        Route::controller(PraktikController::class)->group(function () {
            Route::get('antrean', 'antrian')->name('antrian');
            Route::post('antrean/{antrian}/panggil', 'panggil')->name('panggil');
            Route::get('antrean/{antrian}/periksa', 'periksa')->name('periksa');
            Route::post('antrean/{antrian}/periksa', 'simpanPeriksa')->middleware('izin:resep.buat')->name('periksa.simpan');
            Route::get('konsultasi', 'konsultasi')->name('konsultasi');
            Route::get('konsultasi/{konsultasi}', 'showKonsultasi')->name('konsultasi.show');
            Route::post('konsultasi/{konsultasi}/tanggapi', 'tanggapi')->name('konsultasi.tanggapi');
            Route::get('resep', 'riwayatResep')->name('resep');
            Route::get('rekomendasi-obat', 'rekomendasiObat')->middleware(['izin:resep.buat', 'throttle:120,1,rekomendasi'])->name('rekomendasi');
        });
        Route::controller(RiwayatController::class)->group(function () {
            Route::get('pemeriksaan', 'pemeriksaan')->name('pemeriksaan');
            Route::get('riwayat-pasien', 'index')->name('riwayat');
            Route::get('riwayat-pasien/{pasien}', 'show')->name('riwayat.show');
            Route::get('jadwal-saya', 'jadwal')->name('jadwal');
        });
    });

    /* ----- Farmasi ----- */
    Route::middleware('izin:resep.proses')->prefix('farmasi')->name('farmasi.')->group(function () {
        Route::get('resep', [Farmasi\ResepController::class, 'index'])->name('resep.index');
        Route::get('resep/{resep}', [Farmasi\ResepController::class, 'show'])->name('resep.show');
        Route::post('resep/{resep}/proses', [Farmasi\ResepController::class, 'proses'])->name('resep.proses');
        Route::post('resep/{resep}/serahkan', [Farmasi\ResepController::class, 'serahkan'])->name('resep.serahkan');
        Route::get('riwayat-penyerahan', Farmasi\PenyerahanController::class)->name('penyerahan');
    });
    Route::resource('obat', Farmasi\ObatController::class)->except('show')->middleware('izin:obat.kelola');
    Route::get('formularium', [Farmasi\ObatController::class, 'formularium'])->middleware('izin:obat.lihat')->name('formularium');

    /* ----- Super Admin & Admin ----- */
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::middleware('izin:akun.kelola')->group(function () {
            Route::resource('akun', Admin\AkunController::class)->except('show');
            Route::post('akun/{akun}/status', [Admin\AkunController::class, 'status'])->name('akun.status');
            Route::post('akun/{akun}/buka-kunci', [Admin\AkunController::class, 'bukaKunci'])->name('akun.bukaKunci');
            Route::get('pasien', [Admin\PasienController::class, 'index'])->name('pasien.index');
            Route::delete('pasien/{pasien}', [Admin\PasienController::class, 'destroy'])->name('pasien.destroy');
        });
        Route::get('peran', [Admin\PeranController::class, 'index'])->middleware('izin:peran.kelola')->name('peran');
        Route::put('peran', [Admin\PeranController::class, 'update'])->middleware('izin:peran.kelola');
        Route::middleware('izin:jadwal.kelola')->group(function () {
            Route::get('jadwal', [Admin\JadwalController::class, 'index'])->name('jadwal');
            Route::post('jadwal', [Admin\JadwalController::class, 'store'])->name('jadwal.store');
            Route::put('jadwal/{jadwal}', [Admin\JadwalController::class, 'update'])->name('jadwal.update');
            Route::delete('jadwal/{jadwal}', [Admin\JadwalController::class, 'destroy'])->name('jadwal.destroy');
        });
        Route::get('template-notifikasi', [Admin\TemplateController::class, 'index'])->middleware('izin:notifikasi.broadcast')->name('template');
        Route::put('template-notifikasi/{template}', [Admin\TemplateController::class, 'update'])->middleware('izin:template.kelola')->name('template.update');
        Route::post('broadcast', [Admin\TemplateController::class, 'broadcast'])->middleware('izin:notifikasi.broadcast')->name('broadcast');
        Route::get('audit', Admin\AuditController::class)->middleware('izin:audit.lihat')->name('audit');
        Route::get('kesehatan', Admin\KesehatanController::class)->middleware('izin:audit.lihat')->name('kesehatan');
        Route::get('whatsapp', [Admin\WaLogController::class, 'index'])->middleware('izin:audit.lihat')->name('wa');
        Route::post('whatsapp/uji', [Admin\WaLogController::class, 'uji'])->middleware(['izin:pengaturan.kelola', 'throttle:10,1,wa-uji'])->name('wa.uji');
        Route::get('log-login', Admin\LogLoginController::class)->middleware('izin:audit.lihat')->name('log');
        Route::get('pengaturan', [Admin\PengaturanController::class, 'index'])->middleware('izin:pengaturan.kelola')->name('pengaturan');
        Route::put('pengaturan', [Admin\PengaturanController::class, 'update'])->middleware('izin:pengaturan.kelola');
        Route::put('spesialisasi/{spesialisasi}', [Admin\SpesialisasiController::class, 'update'])->middleware('izin:pengaturan.kelola')->name('spesialisasi.update');
        Route::resource('pengumuman', Admin\PengumumanController::class)->except('show')->middleware('izin:pengumuman.kelola')->parameters(['pengumuman' => 'pengumuman']);
    });

    Route::middleware('izin:laporan.lihat')->group(function () {
        Route::get('laporan', [LaporanController::class, 'index'])->name('laporan');
        Route::get('laporan/cetak', [LaporanController::class, 'cetak'])->name('laporan.cetak');
        Route::get('laporan/export', [LaporanController::class, 'export'])->name('laporan.export');
        Route::get('laporan/xlsx', [LaporanController::class, 'xlsx'])->name('laporan.xlsx');
        Route::get('laporan/pdf', [LaporanController::class, 'pdf'])->name('laporan.pdf');
    });
});
