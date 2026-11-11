<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomingItem extends Model
{
    protected $fillable = ['serial_number_id', 'category_id', 'item_name', 'qty', 'supplier', 'received_at', 'created_by'];

    protected function casts(): array
    {
        return ['received_at' => 'datetime'];
    }

    public function serialNumber(): BelongsTo
    {
        return $this->belongsTo(SerialNumber::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}