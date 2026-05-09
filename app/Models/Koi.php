<?php

namespace App\Models;

use App\Traits\LogsActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Koi extends Model
{
    use LogsActivity;

    protected $logModule = 'Data Koi';
    protected $logExcept = ['updated_at'];

    protected $fillable = [
        'breeder_id',
        'jenis_koi_id',
        'code',
        'name',
        'photo',
        'description',
        'age',
        'size',
        'box_qty',
        'weight',
        'total_weight',
        'customer_id',
        'sale_tanggal',
        'sale_qty',
        'sell_jpy',
        'kurs',
        'sell_idr',
        'buy_jpy',
        'unit_price',
        'stock_price',
        'fee',
        'sale_status',
        'sale_invoice',
        'sale_shipped_date',
        'shipping_cost',
        'shipping_qty',
        'shipping_note',
        'shipping_batch',
        'shipping_date',
        'azukari_period',
        'azukari_qty',
        'azukari_price_jpy',
    ];

    protected $casts = [
        'age'          => 'integer',
        'size'         => 'decimal:2',
        'box_qty'      => 'integer',
        'weight'       => 'decimal:3',
        'total_weight' => 'decimal:3',
        'sale_tanggal'      => 'date:Y-m-d',
        'sale_shipped_date' => 'date:Y-m-d',
        'shipping_date'     => 'date:Y-m-d',
        'sale_qty'     => 'integer',
        'sell_jpy'     => 'decimal:2',
        'buy_jpy'      => 'decimal:2',
        'kurs'         => 'decimal:2',
        'sell_idr'    => 'decimal:2',
        'unit_price'  => 'decimal:2',
        'stock_price' => 'decimal:2',
        'fee'         => 'decimal:2',
    ];

    protected function getActivityItemName(): ?string
    {
        return "{$this->code} - {$this->name}";
    }

    public function breeder(): BelongsTo
    {
        return $this->belongsTo(Breeder::class);
    }

    public function jenisKoi(): BelongsTo
    {
        return $this->belongsTo(JenisKoi::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(KoiImage::class)->orderBy('sort_order');
    }
}
