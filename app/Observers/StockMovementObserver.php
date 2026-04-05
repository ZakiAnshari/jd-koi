<?php

namespace App\Observers;

use App\Models\Stock;
use App\Models\StockMovement;

class StockMovementObserver
{
    public function created(StockMovement $movement): void
    {
        Stock::refreshQty($movement->koi_id);
    }

    public function updating(StockMovement $movement): void
    {
        $movement->oldKoiId = $movement->getOriginal('koi_id');
    }

    public function updated(StockMovement $movement): void
    {
        if (isset($movement->oldKoiId) && $movement->oldKoiId !== $movement->koi_id) {
            Stock::refreshQty($movement->oldKoiId);
        }

        Stock::refreshQty($movement->koi_id);
    }

    public function deleted(StockMovement $movement): void
    {
        Stock::refreshQty($movement->koi_id);
    }
}
