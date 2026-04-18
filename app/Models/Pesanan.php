<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pesanan extends Model
{
    protected $table = 'pesanans';

    protected $fillable = [
        'kode_pesanan',
        'customer_id',
        'katalog_koi_id',
        'qty',
        'harga_satuan',
        'total_harga',
        'status',
        'catatan',
    ];

    protected $casts = [
        'harga_satuan' => 'decimal:2',
        'total_harga' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->kode_pesanan) {
                $date = date('Ymd');
                $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $model->kode_pesanan = "ORD-{$date}-{$random}";
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function katalogKoi(): BelongsTo
    {
        return $this->belongsTo(KatalogKoi::class, 'katalog_koi_id');
    }
}
