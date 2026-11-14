<?php

use App\Models\Peran;
use Illuminate\Support\Facades\Broadcast;

// Kanal publik "publik" tidak perlu otorisasi (hanya sinyal antrean/jadwal/pengumuman).

// Semua petugas (bukan pasien)
Broadcast::channel('staf', fn ($akun) => $akun->aktif() && ! $akun->hasRole(Peran::PASIEN));

// Kanal pribadi tiap akun (notifikasi & data miliknya)
Broadcast::channel('akun.{id}', fn ($akun, $id) => $akun->aktif() && (int) $akun->id_akun === (int) $id);
