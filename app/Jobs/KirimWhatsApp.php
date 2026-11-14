<?php

namespace App\Jobs;

use App\Models\WaLog;
use App\Support\WhatsApp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class KirimWhatsApp implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [15, 60];

    public function __construct(public int $idLog, public string $pesan) {}

    public function handle(): void
    {
        $log = WaLog::find($this->idLog);
        if (! $log) return;
        $status = WhatsApp::kirimSekarang($log->nomor, $this->pesan, $log->jenis, $log->id_akun, $log->ringkas, $log);
        if ($status === 'gagal' && $this->attempts() < $this->tries) {
            $this->release($this->backoff[$this->attempts() - 1] ?? 60);
        }
    }
}

