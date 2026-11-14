<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Skema mengikuti ERD_Puskesmas_Chen.xlsx (lembar "Pemetaan Tabel").
 * Tambahan di luar ERD ditandai [EXT]: petugas_loket, antrian, pengumuman,
 * kolom `kode` (ULID publik agar ID internal tidak terekspos), notifications.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ===== Autentikasi & RBAC =====
        Schema::create('peran', function (Blueprint $t) {
            $t->id('id_peran');
            $t->string('nama_peran', 30)->unique();   // super_admin, dokter_umum, dokter_kandungan, apoteker, loket, pasien
            $t->string('label', 60)->nullable();      // [EXT] nama tampilan
        });

        Schema::create('izin', function (Blueprint $t) {
            $t->id('id_izin');
            $t->string('kode_izin', 50)->unique();    // contoh: resep.buat
            $t->string('deskripsi', 200)->nullable();
        });

        Schema::create('peran_izin', function (Blueprint $t) {
            $t->foreignId('id_peran')->constrained('peran', 'id_peran')->cascadeOnDelete();
            $t->foreignId('id_izin')->constrained('izin', 'id_izin')->cascadeOnDelete();
            $t->primary(['id_peran', 'id_izin']);
        });

        Schema::create('akun', function (Blueprint $t) {
            $t->id('id_akun');
            $t->foreignId('id_peran')->constrained('peran', 'id_peran');
            $t->string('email', 100)->unique();
            $t->string('password', 255);              // hash
            $t->string('status', 20)->default('aktif'); // aktif / nonaktif
            $t->rememberToken();
            $t->timestamps();
        });

        Schema::create('log_login', function (Blueprint $t) {
            $t->id('id_log');
            $t->foreignId('id_akun')->nullable()->constrained('akun', 'id_akun')->nullOnDelete();
            $t->string('email_dicoba', 100)->nullable(); // [EXT] untuk percobaan gagal dgn email tak dikenal
            $t->dateTime('waktu');
            $t->string('hasil', 20);                  // berhasil / gagal
            $t->string('ip', 45)->nullable();         // [EXT]
        });

        Schema::create('pasien', function (Blueprint $t) {
            $t->id('id_pasien');
            $t->foreignId('id_akun')->nullable()->unique()->constrained('akun', 'id_akun')->nullOnDelete();
            $t->string('no_rm', 20)->unique();        // [EXT] nomor rekam medis
            $t->string('nik', 16)->unique();
            $t->string('nama', 100);
            $t->date('tgl_lahir')->nullable();        // [EXT]
            $t->string('jenis_kelamin', 1)->nullable(); // [EXT] L / P
            $t->text('alamat')->nullable();
            $t->string('no_tlp', 20)->nullable();
            $t->timestamps();
        });

        // ===== Staf =====
        Schema::create('spesialisasi', function (Blueprint $t) {
            $t->id('id_spesialisasi');
            $t->string('nama', 50)->unique();         // Umum, Kandungan
            $t->string('poli', 50)->nullable();       // [EXT] Poli Umum, Poli KIA/Kandungan
        });

        Schema::create('admin', function (Blueprint $t) {
            $t->id('id_admin');
            $t->foreignId('id_akun')->unique()->constrained('akun', 'id_akun')->cascadeOnDelete();
            $t->string('nama', 100);
        });

        Schema::create('dokter', function (Blueprint $t) {
            $t->id('id_dokter');
            $t->foreignId('id_akun')->unique()->constrained('akun', 'id_akun')->cascadeOnDelete();
            $t->foreignId('id_spesialisasi')->constrained('spesialisasi', 'id_spesialisasi');
            $t->foreignId('id_admin')->nullable()->constrained('admin', 'id_admin')->nullOnDelete();
            $t->string('nama', 100);
            $t->string('no_str', 30);
            $t->string('jadwal', 100)->nullable();    // [EXT] contoh: Senin–Jumat 08.00–14.00
        });

        Schema::create('apoteker', function (Blueprint $t) {
            $t->id('id_apoteker');
            $t->foreignId('id_akun')->unique()->constrained('akun', 'id_akun')->cascadeOnDelete();
            $t->string('nama', 100);
            $t->string('no_sipa', 30);
        });

        Schema::create('petugas_loket', function (Blueprint $t) { // [EXT]
            $t->id('id_loket');
            $t->foreignId('id_akun')->unique()->constrained('akun', 'id_akun')->cascadeOnDelete();
            $t->string('nama', 100);
        });

        // ===== Pelayanan =====
        Schema::create('konsultasi', function (Blueprint $t) {
            $t->id('id_konsultasi');
            $t->ulid('kode')->unique();               // [EXT] id publik
            $t->foreignId('id_pasien')->constrained('pasien', 'id_pasien')->cascadeOnDelete();
            $t->foreignId('id_dokter')->nullable()->constrained('dokter', 'id_dokter')->nullOnDelete();
            $t->foreignId('id_spesialisasi')->constrained('spesialisasi', 'id_spesialisasi'); // [EXT]
            $t->dateTime('tanggal');
            $t->text('keluhan');
            $t->text('diagnosis')->nullable();
            $t->text('tanggapan')->nullable();        // [EXT] saran dokter untuk konsultasi online
            $t->string('jenis', 10)->default('online'); // [EXT] online / langsung
            $t->string('status', 20)->default('menunggu'); // [EXT] menunggu / ditanggapi / selesai
            $t->timestamps();
        });

        Schema::create('antrian', function (Blueprint $t) { // [EXT]
            $t->id('id_antrian');
            $t->ulid('kode')->unique();
            $t->foreignId('id_pasien')->constrained('pasien', 'id_pasien')->cascadeOnDelete();
            $t->foreignId('id_spesialisasi')->constrained('spesialisasi', 'id_spesialisasi');
            $t->foreignId('id_dokter')->nullable()->constrained('dokter', 'id_dokter')->nullOnDelete();
            $t->foreignId('id_konsultasi')->nullable()->constrained('konsultasi', 'id_konsultasi')->nullOnDelete();
            $t->date('tanggal');
            $t->string('nomor', 10);                  // U-001 / K-001
            $t->string('status', 20)->default('menunggu'); // menunggu / hadir / dipanggil / dilayani / selesai / batal
            $t->string('sumber', 10)->default('online'); // online / loket
            $t->text('keluhan_awal')->nullable();
            $t->dateTime('waktu_hadir')->nullable();
            $t->dateTime('waktu_panggil')->nullable();
            $t->timestamps();
            $t->unique(['tanggal', 'nomor']);
        });

        // ===== Farmasi =====
        Schema::create('obat', function (Blueprint $t) {
            $t->id('id_obat');
            $t->string('nama_obat', 100);
            $t->string('satuan', 20)->default('tablet'); // [EXT]
            $t->integer('stok')->default(0);
            $t->timestamps();
        });

        Schema::create('resep', function (Blueprint $t) {
            $t->id('id_resep');
            $t->ulid('kode')->unique();               // [EXT]
            $t->foreignId('id_pasien')->constrained('pasien', 'id_pasien')->cascadeOnDelete();
            $t->foreignId('id_dokter')->constrained('dokter', 'id_dokter');
            $t->foreignId('id_apoteker')->nullable()->constrained('apoteker', 'id_apoteker')->nullOnDelete();
            $t->foreignId('id_konsultasi')->nullable()->constrained('konsultasi', 'id_konsultasi')->nullOnDelete(); // [EXT]
            $t->string('status', 20)->default('baru'); // baru / diproses / diserahkan
            $t->dateTime('tgl_resep');
        });

        Schema::create('detail_resep', function (Blueprint $t) {
            $t->foreignId('id_resep')->constrained('resep', 'id_resep')->cascadeOnDelete();
            $t->foreignId('id_obat')->constrained('obat', 'id_obat');
            $t->integer('jumlah');
            $t->string('aturan_pakai', 100);
            $t->primary(['id_resep', 'id_obat']);
        });

        Schema::create('penyerahan_obat', function (Blueprint $t) {
            $t->id('id_penyerahan');
            $t->foreignId('id_resep')->unique()->constrained('resep', 'id_resep')->cascadeOnDelete();
            $t->foreignId('id_apoteker')->constrained('apoteker', 'id_apoteker');
            $t->foreignId('id_pasien')->constrained('pasien', 'id_pasien');
            $t->dateTime('waktu');
        });

        // ===== Informasi publik [EXT] =====
        Schema::create('pengumuman', function (Blueprint $t) {
            $t->id();
            $t->string('judul', 150);
            $t->text('isi');
            $t->string('kategori', 30)->default('pengumuman'); // pengumuman / agenda / edukasi
            $t->date('tanggal');
            $t->boolean('terbit')->default(true);
            $t->timestamps();
        });

        // ===== Bawaan Laravel =====
        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email')->primary();
            $t->string('token');
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index();
        });

        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->morphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $t) {
            $t->id();
            $t->morphs('tokenable');
            $t->text('name');
            $t->string('token', 64)->unique();
            $t->text('abilities')->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->timestamp('expires_at')->nullable()->index();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'personal_access_tokens', 'notifications', 'sessions', 'password_reset_tokens', 'pengumuman',
            'penyerahan_obat', 'detail_resep', 'resep', 'obat', 'antrian', 'konsultasi',
            'petugas_loket', 'apoteker', 'dokter', 'admin', 'spesialisasi', 'pasien',
            'log_login', 'akun', 'peran_izin', 'izin', 'peran',
        ] as $tabel) {
            Schema::dropIfExists($tabel);
        }
    }
};
