<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pasien', function (Blueprint $t) {
            $t->string('no_kk', 16)->nullable()->index()->after('nik'); // Nomor Kartu Keluarga
        });

        Schema::create('otp_kode', function (Blueprint $t) {
            $t->id();
            $t->ulid('ref')->unique();              // id publik (API)
            $t->string('email', 100)->index();
            $t->string('tujuan', 20);               // registrasi / reset / login
            $t->foreignId('id_akun')->nullable()->constrained('akun', 'id_akun')->cascadeOnDelete();
            $t->string('kode_hash', 64);            // HMAC-SHA256, kode asli tidak disimpan
            $t->text('payload')->nullable();        // data registrasi (terenkripsi)
            $t->unsignedTinyInteger('percobaan')->default(0);
            $t->dateTime('kedaluwarsa');
            $t->dateTime('dipakai_at')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_kode');
        Schema::table('pasien', fn (Blueprint $t) => $t->dropColumn('no_kk'));
    }
};

