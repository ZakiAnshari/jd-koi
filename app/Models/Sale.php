<?php

namespace App\Models;

use App\Models\Customer;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    use LogsActivity;

    protected $logModule = 'Penjualan';
    protected $logExcept = ['updated_at'];

    protected $fillable = [
        'date',
        'status',
        'koi_id',
        'customer_id',
        'qty',
        'sell_jpy',
        'sell_idr',
        'stock_jpy',
        'stock_idr',
        'fee',
        'margin',
        'profit',
    ];

    protected function getActivityItemName(): ?string
    {
        $parts = [];
        if ($this->koi?->code) {
            $parts[] = $this->koi->code;
        }
        if ($this->koi?->name) {
            $parts[] = $this->koi->name;
        }
        return !empty($parts) ? implode(' - ', $parts) : null;
    }

    protected $casts = [
        'date' => 'date',
        'qty' => 'integer',
        'sell_jpy' => 'decimal:2',
        'sell_idr' => 'decimal:2',
        'stock_jpy' => 'decimal:2',
        'stock_idr' => 'decimal:2',
        'fee' => 'decimal:2',
        'margin' => 'decimal:2',
        'profit' => 'decimal:2',
    ];

    public function koi(): BelongsTo
    {
        return $this->belongsTo(Koi::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function calculateProfit(): int|float
    {
        return match ($this->status) {
            'azukari' => $this->fee ?? 0,
            'sold' => ($this->stock_idr ?? 0) * ($this->qty ?? 0),
            'shipped' => (($this->sell_idr ?? 0) - ($this->stock_idr ?? 0)) * ($this->qty ?? 0) + ($this->fee ?? 0),
            default => 0,
        };
    }

    public function calculateFee(): int|float
    {
        return round(($this->sell_idr ?? 0) * ($this->qty ?? 0) * (($this->margin ?? 0) / 100));
    }
}
