<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public const TTL_MINUTES = 10;
    public const MAX_ATTEMPTS = 5;
    public const RESEND_COOLDOWN = 60;

    /** Buat OTP baru lalu kirim ke email. false bila email gagal terkirim. */
    public function issue(User $user): bool
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('email_otps')->updateOrInsert(
            ['email' => $user->email],
            [
                'code' => $this->hash($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(self::TTL_MINUTES),
                'last_sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        try {
            Mail::to($user->email)->send(new OtpMail($user->name, $code, self::TTL_MINUTES));

            return true;
        } catch (\Throwable $e) {
            report($e); // detail error SMTP masuk storage/logs/laravel.log

            return false;
        }
    }

    /** @return 'ok'|'invalid'|'expired'|'locked'|'missing' */
    public function verify(string $email, string $code): string
    {
        $row = $this->row($email);

        if (! $row) {
            return 'missing';
        }
        if (Carbon::parse($row->expires_at)->isPast()) {
            return 'expired';
        }
        if ($row->attempts >= self::MAX_ATTEMPTS) {
            return 'locked';
        }
        if (! hash_equals($row->code, $this->hash($code))) {
            DB::table('email_otps')->where('email', $email)->increment('attempts');

            return 'invalid';
        }

        DB::table('email_otps')->where('email', $email)->delete();

        return 'ok';
    }

    public function attemptsLeft(string $email): int
    {
        $row = $this->row($email);

        return $row ? max(0, self::MAX_ATTEMPTS - $row->attempts) : 0;
    }

    public function resendIn(string $email): int
    {
        $row = $this->row($email);

        return $row ? max(0, Carbon::parse($row->last_sent_at)->timestamp + self::RESEND_COOLDOWN - now()->timestamp) : 0;
    }

    public function expiresIn(string $email): int
    {
        $row = $this->row($email);

        return $row ? max(0, Carbon::parse($row->expires_at)->timestamp - now()->timestamp) : 0;
    }

    private function row(string $email): ?object
    {
        return DB::table('email_otps')->where('email', $email)->first();
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }
}