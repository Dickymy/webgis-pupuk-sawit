<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi terkait Realisasi Pemupukan.
 *
 * Tipe event:
 * - tahap_siap: Blok siap untuk realisasi tahap tertentu
 * - interval_terpenuhi: Interval tahap terpenuhi, Tahap 2 siap
 * - realisasi_dicatat: Realisasi berhasil dicatat
 * - program_selesai: Program pemupukan tahun ini selesai
 * - realisasi_sebagian: Realisasi sebagian, perlu dilengkapi
 */
class RealisasiNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $tipe,
        private string $judul,
        private string $pesan,
        private ?string $url = null,
        private ?array $meta = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipe' => $this->tipe,
            'judul' => $this->judul,
            'pesan' => $this->pesan,
            'url' => $this->url,
            'meta' => $this->meta,
        ];
    }

    // ─── Factory Methods ─────────────────────────────────────

    public static function tahapSiap(string $namaBlok, int $tahap, string $url): self
    {
        return new self(
            tipe: 'tahap_siap',
            judul: "Tahap {$tahap} Siap",
            pesan: "{$namaBlok} siap untuk pemupukan Tahap {$tahap}.",
            url: $url,
            meta: ['blok' => $namaBlok, 'tahap' => $tahap],
        );
    }

    public static function intervalTerpenuhi(string $namaBlok, string $url): self
    {
        $intervalHari = (int) config('fertilization.window.min_interval_days', 120);

        return new self(
            tipe: 'interval_terpenuhi',
            judul: 'Tahap 2 Siap',
            pesan: "{$namaBlok} — interval {$intervalHari} hari terpenuhi, Tahap 2 dapat dilakukan.",
            url: $url,
            meta: ['blok' => $namaBlok],
        );
    }

    public static function realisasiDicatat(string $namaBlok, int $tahap, string $url): self
    {
        return new self(
            tipe: 'realisasi_dicatat',
            judul: 'Realisasi Dicatat',
            pesan: "{$namaBlok} — realisasi Tahap {$tahap} berhasil dicatat.",
            url: $url,
            meta: ['blok' => $namaBlok, 'tahap' => $tahap],
        );
    }

    public static function programSelesai(string $namaBlok, string $url): self
    {
        return new self(
            tipe: 'program_selesai',
            judul: 'Program Selesai',
            pesan: "{$namaBlok} — kebutuhan pemupukan tahunan telah terpenuhi.",
            url: $url,
            meta: ['blok' => $namaBlok],
        );
    }

    public static function realisasiSebagian(string $namaBlok, int $tahap, string $url): self
    {
        return new self(
            tipe: 'realisasi_sebagian',
            judul: 'Realisasi Sebagian',
            pesan: "{$namaBlok} — Tahap {$tahap} belum selesai, perlu dilengkapi.",
            url: $url,
            meta: ['blok' => $namaBlok, 'tahap' => $tahap],
        );
    }
}
