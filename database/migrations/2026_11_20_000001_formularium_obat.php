<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Formularium: kode OBT-xxx, kategori, zat aktif, indikasi (kata kunci), poli, keamanan kehamilan, dosis bawaan. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obat', function (Blueprint $t) {
            $t->string('kode', 10)->nullable()->unique()->after('id_obat');
            $t->string('kategori', 40)->nullable()->after('nama_obat');
            $t->string('bentuk', 30)->nullable()->after('kategori');      // Tablet, Kapsul, Sachet, Tablet Kunyah
            $t->string('zat_aktif', 80)->nullable()->after('bentuk');
            $t->string('golongan', 20)->nullable()->after('zat_aktif');   // antibiotik / oains / suplemen / null
            $t->string('poli', 10)->default('umum')->after('golongan');   // umum / kandungan / semua
            $t->string('keamanan_hamil', 12)->default('konsultasi')->after('poli'); // aman / hati_hati / hindari / konsultasi
            $t->text('indikasi')->nullable()->after('keamanan_hamil');    // kata kunci dipisah koma: demam, nyeri, ...
            $t->string('kegunaan', 160)->nullable()->after('indikasi');   // keterangan singkat untuk dokter/pasien
            $t->unsignedSmallInteger('jumlah_bawaan')->default(10)->after('kegunaan');
            $t->string('aturan_bawaan', 100)->nullable()->after('jumlah_bawaan');
            $t->string('catatan', 255)->nullable()->after('aturan_bawaan'); // cara minum / peringatan untuk pasien
            $t->unsignedSmallInteger('stok_minimum')->default(20)->after('stok');
            $t->boolean('aktif')->default(true)->after('stok_minimum');
        });
    }

    public function down(): void
    {
        Schema::table('obat', fn (Blueprint $t) => $t->dropColumn(['kode', 'kategori', 'bentuk', 'zat_aktif', 'golongan', 'poli', 'keamanan_hamil', 'indikasi', 'kegunaan', 'jumlah_bawaan', 'aturan_bawaan', 'catatan', 'stok_minimum', 'aktif']));
    }
};

