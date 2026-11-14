<?php

namespace App\Console\Commands;

use App\Support\Hmac;
use Illuminate\Console\Command;

/** Membuat header bertanda tangan untuk uji API kiosk. */
class ContohHmac extends Command
{
    protected $signature = 'hmac:contoh {method} {path} {body?} {--kiosk=}';
    protected $description = 'Cetak header X-Kiosk-Id / X-Timestamp / X-Signature untuk API kiosk';

    public function handle(): int
    {
        $id = $this->option('kiosk') ?: array_key_first(config('hmac.kiosk'));
        $secret = config("hmac.kiosk.$id");
        if (! $secret) {
            $this->error('KIOSK_HMAC_SECRET belum diatur di .env');
            return self::FAILURE;
        }
        $ts = (string) time();
        $sig = Hmac::tanda(Hmac::kanonik($this->argument('method'), $this->argument('path'), $ts, (string) $this->argument('body')), $secret);
        $this->line("X-Kiosk-Id: $id");
        $this->line("X-Timestamp: $ts");
        $this->line("X-Signature: $sig");
        return self::SUCCESS;
    }
}

