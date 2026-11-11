<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Pembuat nomor otomatis. Seluruh aturan dibaca dari database, tidak ada format di dalam kode:
 *
 *   __autonumber_config : aturan per form_id (format_auto, number_digit, reset_by)
 *   __autonumber        : counter terakhir per form_id (last_no, last_tgl)
 *
 * Token pada format_auto:
 *   %Y = tahun 4 digit   %y = tahun 2 digit   %m = bulan 2 digit   %d = tanggal 2 digit
 *   #  = nomor urut, diisi nol di depan sebanyak number_digit
 * Contoh: 'BRG%y#' + number_digit 5 + nomor 73 pada tahun 2026  ->  BRG2600073
 *
 * reset_by: y = reset tiap tahun, m = tiap bulan, d = tiap hari, kosong = tidak pernah reset.
 *
 * Pemakaian di halaman mana pun (controller):
 *   DB::transaction(function () {
 *       $kode = AutoNumber::next('nama_form_id', auth()->user()->username ?? null);
 *       Model::create([... 'kode' => $kode]);
 *   });
 * Panggil di dalam transaksi yang sama dengan penyimpanan data supaya, bila simpan gagal,
 * counter ikut dibatalkan (tidak ada nomor bolong). Baris counter dikunci (lockForUpdate)
 * sehingga dua orang yang simpan bersamaan tidak mendapat nomor kembar.
 */
class AutoNumber
{
    public static function next(string $formId, ?string $by = null): string
    {
        return DB::transaction(function () use ($formId, $by) {
            $config = DB::table('__autonumber_config')->where('form_id', $formId)->first();
            if (! $config) {
                throw new RuntimeException(
                    "Aturan auto-number untuk '{$formId}' belum ada. Tambahkan barisnya di tabel __autonumber_config."
                );
            }

            $now = now();

            // Pastikan baris counter ada (diabaikan bila sudah ada)
            DB::table('__autonumber')->insertOrIgnore([
                'form_id'     => $formId,
                'last_no'     => 0,
                'last_tgl'    => $now->toDateString(),
                'create_by'   => $by,
                'create_time' => $now,
            ]);

            $counter = DB::table('__autonumber')->where('form_id', $formId)->lockForUpdate()->first();

            $last = (int) $counter->last_no;
            if (self::shouldReset($config->reset_by, $counter->last_tgl, $now)) {
                $last = 0;
            }
            $next = $last + 1;

            DB::table('__autonumber')->where('form_id', $formId)->update([
                'last_no'     => $next,
                'last_tgl'    => $now->toDateString(),
                'update_by'   => $by,
                'update_time' => $now,
            ]);

            return self::format($config, $next, $now);
        });
    }

    private static function shouldReset(?string $resetBy, ?string $lastTgl, Carbon $now): bool
    {
        $resetBy = strtolower(trim((string) $resetBy));
        if ($resetBy === '' || ! $lastTgl) {
            return false;
        }

        $last = Carbon::parse($lastTgl);

        return match ($resetBy) {
            'y' => $last->year !== $now->year,
            'm' => $last->format('Ym') !== $now->format('Ym'),
            'd' => $last->toDateString() !== $now->toDateString(),
            default => false,
        };
    }

    private static function format(object $config, int $next, Carbon $now): string
    {
        $digits = (int) ($config->number_digit ?: 3);
        $number = str_pad((string) $next, $digits, '0', STR_PAD_LEFT);

        $format = (string) ($config->format_auto ?: '#');
        if (! str_contains($format, '#')) {
            $format .= '#';          // format tanpa '#': nomor ditaruh di belakang
        }

        $format = strtr($format, [
            '%Y' => $now->format('Y'),
            '%y' => $now->format('y'),
            '%m' => $now->format('m'),
            '%d' => $now->format('d'),
        ]);

        return preg_replace('/#+/', $number, $format, 1);
    }
}