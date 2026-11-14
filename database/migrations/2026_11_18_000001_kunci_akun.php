<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akun', function (Blueprint $t) {
            $t->unsignedSmallInteger('gagal_login')->default(0)->after('status');
            $t->dateTime('terkunci_sampai')->nullable()->after('gagal_login');
        });
    }

    public function down(): void
    {
        Schema::table('akun', fn (Blueprint $t) => $t->dropColumn(['gagal_login', 'terkunci_sampai']));
    }
};
