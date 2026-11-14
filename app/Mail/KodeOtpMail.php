<?php

namespace App\Mail;

use App\Models\Pengaturan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class KodeOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public const JUDUL = [
        'registrasi' => 'Verifikasi pendaftaran akun pasien',
        'reset' => 'Atur ulang password',
        'login' => 'Masuk dari perangkat baru',
    ];

    /** Bila dikirim lewat antrean: coba 3x dengan jeda 10 & 30 detik. */
    public int $tries = 3;
    public array $backoff = [10, 30];

    public function __construct(public string $kode, public string $tujuan, public int $menit) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Kode OTP '.Pengaturan::nilai('nama_puskesmas').' — '.(self::JUDUL[$this->tujuan] ?? 'Verifikasi'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp', text: 'emails.otp-teks', with: [
            'judul' => self::JUDUL[$this->tujuan] ?? 'Verifikasi',
            'namaPkm' => Pengaturan::nilai('nama_puskesmas'),
        ]);
    }
}

