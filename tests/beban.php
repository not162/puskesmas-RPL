<?php
/**
 * Uji beban ringan: N pengguna serentak membuka halaman publik.
 * Pemakaian: php tests/beban.php [base_url] [pengguna] [ulang]
 * Contoh:   php tests/beban.php http://localhost:8000 50 3
 */
[$_, $base, $n, $ulang] = $argv + [1 => 'http://localhost:8000', 2 => 50, 3 => 3];
$jalur = ['/live/antrian', '/', '/layar-antrean', '/jadwal'];

$hasil = [];
$mulaiTotal = microtime(true);
for ($u = 0; $u < (int) $ulang; $u++) {
    foreach ($jalur as $j) {
        $mh = curl_multi_init();
        $hs = [];
        for ($i = 0; $i < (int) $n; $i++) {
            $h = curl_init($base.$j);
            curl_setopt_array($h, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_HTTPHEADER => ['Accept: text/html,application/json']]);
            curl_multi_add_handle($mh, $h);
            $hs[] = $h;
        }
        do { $s = curl_multi_exec($mh, $aktif); curl_multi_select($mh, 0.5); } while ($aktif && $s === CURLM_OK);
        foreach ($hs as $h) {
            $hasil[$j][] = ['kode' => curl_getinfo($h, CURLINFO_HTTP_CODE), 'ms' => curl_getinfo($h, CURLINFO_TOTAL_TIME) * 1000];
            curl_multi_remove_handle($mh, $h);
        }
        curl_multi_close($mh);
    }
}
$total = microtime(true) - $mulaiTotal;

$pct = function (array $v, float $p) { sort($v); return $v[(int) max(0, ceil($p / 100 * count($v)) - 1)]; };
printf("%-16s %6s %6s %8s %8s %8s\n", 'Halaman', 'Req', 'Gagal', 'p50 ms', 'p95 ms', 'maks ms');
$semua = 0; $gagal = 0;
foreach ($hasil as $j => $r) {
    $ms = array_column($r, 'ms');
    $g = count(array_filter($r, fn ($x) => $x['kode'] !== 200));
    $semua += count($r); $gagal += $g;
    printf("%-16s %6d %6d %8.0f %8.0f %8.0f\n", $j, count($r), $g, $pct($ms, 50), $pct($ms, 95), max($ms));
}
printf("\nTotal %d permintaan, %d gagal, %.1f detik, %.1f permintaan/detik (%d pengguna serentak)\n", $semua, $gagal, $total, $semua / $total, $n);
