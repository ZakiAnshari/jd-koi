<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    protected $fillable = [
        'koi_id',
        'date',
        'qty',
        'price_jpy',
        'price_idr',
        'total_jpy',
        'total_idr',
    ];

    protected $casts = [
        'date' => 'date',
        'qty' => 'integer',
        'price_jpy' => 'decimal:2',
        'price_idr' => 'decimal:2',
        'total_jpy' => 'decimal:2',
        'total_idr' => 'decimal:2',
    ];

    public function koi(): BelongsTo
    {
        return $this->belongsTo(Koi::class);
    }
}
