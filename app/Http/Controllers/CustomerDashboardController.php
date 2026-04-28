<?php

namespace App\Http\Controllers;

use App\Models\Koi;
use Inertia\Inertia;

class CustomerDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->role_id === 1) {
            return redirect()->route('dashboard');
        }

        // ── Stats customer ───────────────────────────────────
        $baseQuery = Koi::whereNotNull('sale_status')->where('customer_id', $user->id);

        $totalKoi       = (clone $baseQuery)->count();
        $totalBelanjaIdr= (clone $baseQuery)->sum('sell_idr');

        $statusBreakdown = (clone $baseQuery)
            ->selectRaw('sale_status, count(*) as total')
            ->groupBy('sale_status')
            ->pluck('total', 'sale_status');

        // ── Transaksi terbaru milik customer ─────────────────
        $recentTransactions = Koi::with('breeder:id,name_breeder')
            ->whereNotNull('sale_status')
            ->where('customer_id', $user->id)
            ->orderByDesc('sale_tanggal')
            ->limit(5)
            ->get(['id', 'code', 'name', 'sale_invoice', 'sale_tanggal', 'sale_status', 'sell_idr', 'sale_qty', 'breeder_id'])
            ->map(fn ($k) => [
                'id'           => $k->id,
                'code'         => $k->code,
                'name'         => $k->name,
                'sale_invoice' => $k->sale_invoice,
                'sale_tanggal' => $k->sale_tanggal,
                'sale_status'  => $k->sale_status,
                'sell_idr'     => $k->sell_idr,
                'sale_qty'     => $k->sale_qty,
                'breeder'      => $k->breeder?->name_breeder,
            ]);

        return Inertia::render('Customer/Dashboard', [
            'customerStats' => [
                'nama'            => $user->name,
                'total_koi'       => $totalKoi,
                'total_belanja'   => $totalBelanjaIdr,
                'status_breakdown'=> $statusBreakdown,
            ],
            'recentTransactions' => $recentTransactions,
        ]);
    }
}
