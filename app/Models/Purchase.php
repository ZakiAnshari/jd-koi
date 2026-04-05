<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    use LogsActivity;

    protected $logModule = 'Pembelian';
    protected $logExcept = ['updated_at'];

    protected $fillable = [
        'date',
        'koi_id',
        'qty',
        'price_jpy',
        'price_idr',
        'status',
    ];

    protected function getActivityItemName(): ?string
    {
        $koiName = $this->koi?->name;
        return $koiName ? "Koi {$koiName}" : null;
    }

    protected $casts = [
        'date' => 'date',
        'qty' => 'integer',
        'price_jpy' => 'decimal:2',
        'price_idr' => 'decimal:2',
    ];

    public function koi(): BelongsTo
    {
        return $this->belongsTo(Koi::class);
    }
}
