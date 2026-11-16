<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid',
        'name',
        'role',
        'door',
        'direction',
        'method',
        'companion',
        'result',
        'reason',
        'at',
    ];

    protected $casts = [
        'at' => 'datetime',
    ];
}

