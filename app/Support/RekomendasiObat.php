<?php

namespace App\Support;

use App\Models\Obat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Rekomendasi obat hibrida untuk dokter saat menulis resep.
 *
 *  1. Graf "sering diresepkan bersama" (graph-based, seperti frequently bought together):
 *     simpul = obat, bobot sisi = prior formularium + jumlah resep yang memuat kedua obat.
 *     Skor kandidat = jumlah bobot sisi ke obat yang SUDAH dipilih (keranjang = resep yang sedang ditulis).
 *  2. Berbasis konten: kecocokan kata kunci indikasi obat dengan keluhan + diagnosis, kesesuaian poli.
 *  3. Kolaboratif: (a) obat yang diresepkan pada kasus lain dengan diagnosis mirip (item-based dari riwayat),
 *     (b) kebiasaan peresepan dokter ini.
 *
 * Skor akhir = 0,45·graf + 0,35·konten + 0,20·kolaboratif (masing-masing dinormalisasi 0–1).
 * Penyaring keamanan: stok habis/nonaktif disingkirkan; obat "hindari" pada Poli Kandungan diturunkan & diberi peringatan;
 * obat sejenis (kategori + zat aktif sama) dengan yang sudah dipilih tidak disarankan ganda.
 * Hasil hanya SARAN; keputusan tetap pada dokter.
 */
class RekomendasiObat
{
    public const BOBOT = ['graf' => 0.45, 'konten' => 0.35, 'kolaboratif' => 0.20];

    /** Daftar sisi graf: [idA][idB] = bobot (simetris). Di-cache 10 menit, dibuang saat resep baru dibuat. */
    public static function graf(): array
    {
        return Cache::remember('rekomendasi:graf', 600, function () {
            $kode = Obat::pluck('id_obat', 'kode');
            $g = [];
            $tambah = function ($a, $b, $w) use (&$g) {
                if (! $a || ! $b || $a === $b) return;
                $g[$a][$b] = ($g[$a][$b] ?? 0) + $w;
                $g[$b][$a] = ($g[$b][$a] ?? 0) + $w;
            };
            foreach (config('formularium.pasangan', []) as [$x, $y, $w]) {
                $tambah($kode[$x] ?? null, $kode[$y] ?? null, $w);
            }
            // Dipelajari dari riwayat resep: pasangan obat dalam resep yang sama
            $baris = DB::table('detail_resep as a')->join('detail_resep as b', fn ($j) => $j->on('a.id_resep', '=', 'b.id_resep')->on('a.id_obat', '<', 'b.id_obat'))
                ->selectRaw('a.id_obat x, b.id_obat y, count(*) n')->groupBy('a.id_obat', 'b.id_obat')->get();
            foreach ($baris as $r) $tambah((int) $r->x, (int) $r->y, (int) $r->n);
            return $g;
        });
    }

    public static function lupakanGraf(): void
    {
        Cache::forget('rekomendasi:graf');
    }

    /** Pecah teks bebas menjadi kata & frasa dua kata (huruf kecil). */
    public static function token(string $teks): array
    {
        $kata = preg_split('/[^a-z0-9]+/', mb_strtolower($teks), -1, PREG_SPLIT_NO_EMPTY);
        $kata = array_values(array_filter($kata, fn ($k) => mb_strlen($k) > 2));
        $frasa = [];
        for ($i = 0; $i < count($kata) - 1; $i++) $frasa[] = $kata[$i].' '.$kata[$i + 1];
        return array_unique(array_merge($kata, $frasa));
    }

    /**
     * @param  int[]  $dipilih  id obat yang sudah ada di resep
     * @param  string $teks     keluhan + diagnosis
     * @param  string $poli     nama spesialisasi: Umum / Kandungan
     */
    public static function untuk(array $dipilih, string $teks, string $poli, ?int $idDokter = null, int $batas = 6): array
    {
        $dipilih = array_values(array_unique(array_map('intval', array_filter($dipilih))));
        $kandungan = str_contains(mb_strtolower($poli), 'kandungan');
        $semua = Obat::where('aktif', true)->get()->keyBy('id_obat');
        $terpilih = $semua->only($dipilih);
        $token = self::token($teks);
        $teksKecil = ' '.mb_strtolower($teks).' ';

        // ---- 1. Graf ----
        $graf = self::graf();
        $skorGraf = [];
        $alasanGraf = [];
        foreach ($dipilih as $s) {
            foreach ($graf[$s] ?? [] as $c => $w) {
                $skorGraf[$c] = ($skorGraf[$c] ?? 0) + $w;
                if (! isset($alasanGraf[$c]) || $w > $alasanGraf[$c][1]) $alasanGraf[$c] = [$semua[$s]->nama_obat ?? '', $w];
            }
        }

        // ---- 2. Konten ----
        $skorKonten = [];
        $alasanKonten = [];
        foreach ($semua as $id => $o) {
            $cocok = array_values(array_filter($o->kataKunci(), fn ($k) => str_contains($teksKecil, ' '.$k.' ') || str_contains($teksKecil, ' '.$k) || in_array($k, $token, true)));
            $s = count($cocok) ? min(1, 0.45 + 0.2 * count($cocok)) : 0;
            if (in_array($o->poli, [$kandungan ? 'kandungan' : 'umum', 'semua'], true)) $s += $cocok ? 0.15 : 0.05;
            if ($o->golongan === 'oains') $s *= 0.85; // OAINS lini kedua untuk demam/nyeri ringan (Parasetamol lebih dulu)
            if ($kandungan && $o->poli === 'kandungan' && ! $dipilih) $s += 0.25; // kunjungan ANC tanpa teks pun relevan
            if ($s > 0) { $skorKonten[$id] = $s; $alasanKonten[$id] = $cocok; }
        }

        // ---- 3. Kolaboratif ----
        $skorKolab = [];
        $alasanKolab = [];
        $kataDiag = array_filter($token, fn ($k) => ! str_contains($k, ' ') && mb_strlen($k) > 3);
        if ($kataDiag) {
            // (a) kasus lain dengan diagnosis/keluhan mirip -> obat yang diresepkan
            $kasus = DB::table('konsultasi')->join('resep', 'resep.id_konsultasi', '=', 'konsultasi.id_konsultasi')
                ->join('detail_resep', 'detail_resep.id_resep', '=', 'resep.id_resep')
                ->where(function ($q) use ($kataDiag) {
                    foreach (array_slice($kataDiag, 0, 8) as $k) $q->orWhere('konsultasi.diagnosis', 'like', "%$k%")->orWhere('konsultasi.keluhan', 'like', "%$k%");
                })
                ->selectRaw('detail_resep.id_obat id, count(distinct resep.id_resep) n')->groupBy('detail_resep.id_obat')->pluck('n', 'id');
            foreach ($kasus as $id => $n) { $skorKolab[$id] = ($skorKolab[$id] ?? 0) + $n; $alasanKolab[$id]['kasus'] = $n; }
        }
        if ($idDokter) {
            // (b) kebiasaan dokter ini (bobot setengah)
            $kebiasaan = DB::table('resep')->join('detail_resep', 'detail_resep.id_resep', '=', 'resep.id_resep')
                ->where('resep.id_dokter', $idDokter)->selectRaw('detail_resep.id_obat id, count(*) n')->groupBy('detail_resep.id_obat')->pluck('n', 'id');
            foreach ($kebiasaan as $id => $n) { $skorKolab[$id] = ($skorKolab[$id] ?? 0) + $n / 2; $alasanKolab[$id]['dokter'] = $n; }
        }

        $norm = function (array $s) { $m = max($s ?: [0]); return $m > 0 ? array_map(fn ($v) => $v / $m, $s) : $s; };
        $g = $norm($skorGraf);
        $k = $norm($skorKonten);
        $c = $norm($skorKolab);

        // Obat sejenis dengan yang sudah dipilih (hindari duplikasi terapi)
        $sejenis = fn (Obat $o) => $terpilih->contains(fn ($t) => $t->kategori === $o->kategori && ($t->zat_aktif === $o->zat_aktif || in_array($o->kategori, ['Antibiotik'], true)
            || ($t->kategori === 'Batuk, Sesak & Alergi' && str_contains((string) $t->kegunaan, 'Antihistamin') && str_contains((string) $o->kegunaan, 'Antihistamin'))));

        $hasil = [];
        foreach ($semua as $id => $o) {
            if (in_array($id, $dipilih, true) || $o->stok <= 0) continue;
            $skor = self::BOBOT['graf'] * ($g[$id] ?? 0) + self::BOBOT['konten'] * ($k[$id] ?? 0) + self::BOBOT['kolaboratif'] * ($c[$id] ?? 0);
            if ($skor <= 0.05) continue;

            $peringatan = [];
            if ($kandungan && $o->keamanan_hamil === 'hindari') { $skor *= 0.3; $peringatan[] = 'Hindari pada kehamilan (terutama trimester akhir) kecuali indikasi khusus'; }
            if ($o->golongan === 'antibiotik') $peringatan[] = 'Antibiotik: hanya bila ada tanda infeksi bakteri; pasien wajib menghabiskan';
            if ($sejenis($o)) { $skor *= 0.2; $peringatan[] = 'Sejenis dengan obat yang sudah dipilih'; }
            if ($o->stokMenipis()) $peringatan[] = "Stok menipis ({$o->stok} {$o->satuan})";

            $alasan = [];
            if (isset($alasanGraf[$id])) $alasan[] = 'Sering diresepkan bersama '.$alasanGraf[$id][0];
            if (! empty($alasanKonten[$id])) $alasan[] = 'Cocok dengan keluhan: '.implode(', ', array_slice($alasanKonten[$id], 0, 3));
            elseif (isset($k[$id]) && $kandungan && $o->poli === 'kandungan') $alasan[] = 'Suplemen rutin kunjungan ANC';
            if (! empty($alasanKolab[$id]['kasus'])) $alasan[] = 'Dipakai pada '.$alasanKolab[$id]['kasus'].' kasus serupa';
            if (! empty($alasanKolab[$id]['dokter'])) $alasan[] = 'Sering Anda resepkan';

            $hasil[] = [
                'id_obat' => $id, 'kode' => $o->kode, 'nama' => $o->nama_obat, 'bentuk' => $o->bentuk, 'satuan' => $o->satuan,
                'kategori' => $o->kategori, 'kegunaan' => $o->kegunaan, 'stok' => $o->stok,
                'jumlah' => $o->jumlah_bawaan, 'aturan' => $o->aturan_bawaan,
                'skor' => round($skor, 3),
                'rincian' => ['graf' => round($g[$id] ?? 0, 2), 'konten' => round($k[$id] ?? 0, 2), 'kolaboratif' => round($c[$id] ?? 0, 2)],
                'alasan' => $alasan ?: ['Relevan dengan poli ini'], 'peringatan' => $peringatan,
            ];
        }
        usort($hasil, fn ($a, $b) => $b['skor'] <=> $a['skor']);
        return array_slice($hasil, 0, $batas);
    }
}
