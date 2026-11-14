<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pasien', function (Blueprint $t) {
            $t->boolean('wa_opt_in')->default(false)->after('no_tlp'); // persetujuan menerima WhatsApp
        });
        Schema::table('otp_kode', function (Blueprint $t) {
            $t->string('saluran', 10)->default('email')->after('tujuan'); // email / whatsapp
        });
        Schema::create('wa_log', function (Blueprint $t) {
            $t->id();
            $t->foreignId('id_akun')->nullable()->constrained('akun', 'id_akun')->nullOnDelete();
            $t->string('nomor', 20)->index();          // 62xxxxxxxxxx
            $t->string('jenis', 30);                   // langkah_5 / langkah_7 / otp / uji ...
            $t->string('status', 15);                  // terkirim / gagal / dibatasi / log
            $t->string('ringkas', 160)->nullable();    // potongan pesan (OTP disamarkan)
            $t->string('respons', 255)->nullable();
            $t->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_log');
        Schema::table('otp_kode', fn (Blueprint $t) => $t->dropColumn('saluran'));
        Schema::table('pasien', fn (Blueprint $t) => $t->dropColumn('wa_opt_in'));
    }
};

