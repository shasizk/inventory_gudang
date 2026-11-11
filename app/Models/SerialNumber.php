<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SerialNumber extends Model
{
    protected $fillable = ['sn_no', 'serial', 'item_name', 'category_id', 'source', 'status', 'created_by'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function incomingItem(): HasOne
    {
        return $this->hasOne(IncomingItem::class);
    }
}