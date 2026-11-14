<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Sinyal ringan "data kanal X berubah". Payload hanya nama kanal — TANPA data pribadi.
 * Klien lalu memuat ulang bagian halamannya sendiri (yang tetap dicek hak aksesnya oleh server).
 */
class DataBerubah implements ShouldBroadcastNow
{
    /** @param string $saluran 'publik' | 'staf' | 'akun.{id}' */
    public function __construct(public string $saluran, public array $kanal) {}

    public function broadcastOn(): array
    {
        return [$this->saluran === 'publik' ? new Channel('publik') : new PrivateChannel($this->saluran)];
    }

    public function broadcastAs(): string
    {
        return 'berubah';
    }

    public function broadcastWith(): array
    {
        return ['kanal' => array_values($this->kanal)];
    }
}

