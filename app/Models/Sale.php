<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    protected $fillable = [
        'date',
        'status',
        'koi_id',
        'qty',
        'sell_jpy',
        'sell_idr',
        'margin',
        'fee',
        'stock_jpy',
        'stock_idr',
        'sell_to',
        'profit',
    ];

    protected $casts = [
        'date' => 'date',
        'qty' => 'integer',
        'sell_jpy' => 'decimal:2',
        'sell_idr' => 'decimal:2',
        'margin' => 'decimal:2',
        'fee' => 'decimal:2',
        'stock_jpy' => 'decimal:2',
        'stock_idr' => 'decimal:2',
        'profit' => 'decimal:2',
    ];

    public function koi(): BelongsTo
    {
        return $this->belongsTo(Koi::class);
    }
}
