<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** v2: username, BPJS, foto KTP, jadwal praktik, pengaduan, template notifikasi, audit, pengaturan. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akun', function (Blueprint $t) {
            $t->string('username', 30)->nullable()->unique()->after('id_peran');
            $t->string('email', 100)->nullable()->change(); // email pasien opsional
        });

        Schema::table('pasien', function (Blueprint $t) {
            $t->string('no_bpjs', 13)->nullable()->unique()->after('nik');
            $t->string('foto_ktp')->nullable()->after('no_tlp'); // disimpan privat (storage/app/private)
        });

        Schema::create('jadwal_praktik', function (Blueprint $t) {
            $t->id('id_jadwal');
            $t->foreignId('id_dokter')->constrained('dokter', 'id_dokter')->cascadeOnDelete();
            $t->unsignedTinyInteger('hari'); // 1 = Senin ... 7 = Minggu
            $t->time('jam_mulai');
            $t->time('jam_selesai');
            $t->unsignedSmallInteger('kuota')->default(30);
            $t->boolean('aktif')->default(true);
        });

        Schema::create('pengaduan', function (Blueprint $t) {
            $t->id('id_pengaduan');
            $t->ulid('kode')->unique();
            $t->foreignId('id_akun')->nullable()->constrained('akun', 'id_akun')->nullOnDelete(); // pelapor
            $t->string('kategori', 30); // layanan / antrean / obat / fasilitas / lainnya
            $t->string('judul', 150);
            $t->text('isi');
            $t->string('status', 20)->default('baru'); // baru / diproses / selesai
            $t->text('tanggapan')->nullable();
            $t->foreignId('id_penanggap')->nullable()->constrained('akun', 'id_akun')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('template_notifikasi', function (Blueprint $t) {
            $t->unsignedTinyInteger('langkah')->primary();
            $t->string('judul', 100);
            $t->string('pesan', 300);
            $t->boolean('aktif')->default(true);
        });

        Schema::create('audit_log', function (Blueprint $t) {
            $t->id('id_audit');
            $t->foreignId('id_akun')->nullable()->constrained('akun', 'id_akun')->nullOnDelete();
            $t->string('aksi', 20);   // buat / ubah / hapus / login / checkin / ...
            $t->string('objek', 60);  // nama tabel
            $t->string('objek_id', 40)->nullable();
            $t->text('perubahan')->nullable(); // JSON kolom yang berubah (tanpa sandi)
            $t->string('ip', 45)->nullable();
            $t->dateTime('waktu')->index();
        });

        Schema::create('pengaturan', function (Blueprint $t) {
            $t->string('kunci', 50)->primary();
            $t->text('nilai')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan');
        Schema::dropIfExists('audit_log');
        Schema::dropIfExists('template_notifikasi');
        Schema::dropIfExists('pengaduan');
        Schema::dropIfExists('jadwal_praktik');
        Schema::table('pasien', fn (Blueprint $t) => $t->dropColumn(['no_bpjs', 'foto_ktp']));
        Schema::table('akun', fn (Blueprint $t) => $t->dropColumn('username'));
    }
};

