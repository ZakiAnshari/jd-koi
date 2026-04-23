<?php

namespace App\Observers;

use App\Models\KatalogKoi;
use App\Models\Pesanan;

class PesananObserver
{
    private const STOCK_STATUSES = ['shipped', 'azukari'];

    /**
     * Adjust catalog stock when pesanan status changes.
     *
     * Decrement  : pending/cancelled → confirmed/completed
     * Restore    : confirmed/completed → pending/cancelled
     * No-op      : both statuses in same group (e.g. confirmed → completed)
     *
     * Must be called inside a DB::transaction() at the controller layer
     * so that lockForUpdate() is effective.
     */
    public function updating(Pesanan $pesanan): void
    {
        if (!$pesanan->isDirty('status')) {
            return;
        }

        $oldStatus   = $pesanan->getOriginal('status');
        $newStatus   = $pesanan->status;
        $wasReducing = in_array($oldStatus, self::STOCK_STATUSES);
        $isReducing  = in_array($newStatus, self::STOCK_STATUSES);

        if ($wasReducing === $isReducing) {
            return;
        }

        $katalogKoi = KatalogKoi::lockForUpdate()->findOrFail($pesanan->katalog_koi_id);

        if ($isReducing) {
            if ($katalogKoi->qty < $pesanan->qty) {
                throw new \RuntimeException(
                    "Stok tidak mencukupi untuk mengkonfirmasi pesanan. Stok tersedia: {$katalogKoi->qty}"
                );
            }
            $katalogKoi->decrement('qty', $pesanan->qty);
        } else {
            $katalogKoi->increment('qty', $pesanan->qty);
        }
    }

    /**
     * Restore catalog stock when a pesanan is deleted,
     * but only if the status had already consumed stock.
     */
    public function deleting(Pesanan $pesanan): void
    {
        if (in_array($pesanan->status, self::STOCK_STATUSES)) {
            KatalogKoi::where('id', $pesanan->katalog_koi_id)
                ->increment('qty', $pesanan->qty);
        }
    }
}
