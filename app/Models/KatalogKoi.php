<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KatalogKoi extends Model
{
    protected $table = 'katalog_kois';

    protected $fillable = [
        'tanggal',
        'koi_id',
        'pembelian_id',
        'qty',
        'sell_jpy',
        'kurs',
        'sell_idr',
        'margin',
        'stock_jpy',
        'stock_idr',
        'fee',
        'profit',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'qty' => 'integer',
        'sell_jpy' => 'decimal:2',
        'kurs' => 'decimal:2',
        'sell_idr' => 'decimal:2',
        'margin' => 'decimal:2',
        'stock_jpy' => 'decimal:2',
        'stock_idr' => 'decimal:2',
        'fee' => 'decimal:2',
        'profit' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->margin = $model->sell_idr - $model->stock_idr;
            $model->profit = $model->margin - $model->fee;
        });
    }

    public function koi(): BelongsTo
    {
        return $this->belongsTo(Koi::class);
    }

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'pembelian_id');
    }
}
