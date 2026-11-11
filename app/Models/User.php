<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLES = ['Admin', 'NOC', 'Teknisi'];

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'role',
        'photo',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Pilihan role diambil langsung dari ENUM di database. */
    public static function roleOptions(): array
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return self::ROLES;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM `users` WHERE Field = 'role'");
        $type = $column ? (array_change_key_case((array) $column)['type'] ?? '') : '';

        if (preg_match('/^enum\((.*)\)$/i', $type, $m)) {
            return array_map(fn ($v) => str_replace("''", "'", trim($v, "'")), explode(',', $m[1]));
        }

        return self::ROLES;
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->photo
            ? asset('storage/' . $this->photo) . '?v=' . ($this->updated_at?->timestamp ?? 1)
            : null);
    }

    protected function initials(): Attribute
    {
        return Attribute::get(fn () => Str::of((string) $this->name)->squish()->explode(' ')->filter()
            ->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('') ?: '?');
    }
}