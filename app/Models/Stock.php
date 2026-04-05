<?php

namespace App\Models;

use App\Models\StockMovement;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    use LogsActivity;

    protected $logModule = 'Stok Koi';
    protected $logExcept = ['updated_at'];

    protected $fillable = [
        'koi_id',
        'qty',
    ];

    protected function getActivityItemName(): ?string
    {
        $koiCode = $this->koi?->code;
        return $koiCode ? "Koi {$koiCode}" : null;
    }

    protected $casts = [
        'qty' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function koi(): BelongsTo
    {
        return $this->belongsTo(Koi::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public static function refreshQty(int $koiId): void
    {
        $total = StockMovement::where('koi_id', $koiId)
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'in' THEN qty ELSE -qty END), 0) as total")
            ->value('total');

        $qty = (int) $total;

        $stock = static::firstOrNew(['koi_id' => $koiId]);
        $stock->qty = $qty;
        $stock->save();
    }

    public static function currentQty(int $koiId): int
    {
        $total = StockMovement::where('koi_id', $koiId)
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'in' THEN qty ELSE -qty END), 0) as total")
            ->value('total');

        return (int) $total;
    }
}
