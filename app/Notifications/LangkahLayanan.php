<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Notifikasi per langkah layanan (lihat config/alur.php).
 * Disimpan di tabel notifications (channel database) dan dibaca lewat
 * lonceng notifikasi (polling) + toast di UI.
 */
class LangkahLayanan extends Notification
{
    public function __construct(
        public int $langkah,
        public array $param = [],
        public ?string $url = null,
        public string $tipe = 'info', // info / success / warning / danger
        public ?string $pesanKhusus = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $def = config("alur.langkah.{$this->langkah}");
        $tpl = \App\Models\TemplateNotifikasi::find($this->langkah); // template bisa diubah Super Admin
        if ($tpl) { $def['notif'] = $tpl->pesan; $def['judul'] = $tpl->judul; }
        $pesan = $this->pesanKhusus ?? $def['notif'] ?? '';
        foreach ($this->param as $k => $v) {
            $pesan = str_replace(':'.$k, (string) $v, $pesan);
        }

        return [
            'langkah' => $this->langkah,
            'total_langkah' => count(config('alur.langkah')),
            'judul' => "Langkah {$this->langkah}: ".($def['judul'] ?? 'Info'),
            'pesan' => $pesan,
            'tipe' => $this->tipe,
            'url' => $this->url,
        ];
    }
}

