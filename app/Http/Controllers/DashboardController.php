<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Breeder;
use App\Models\Koi;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user && $user->role_id !== 1) {
            return redirect()->route('customer.dashboard');
        }

        $currentMonth = now()->month;
        $currentYear  = now()->year;

        // ── Stats bulan ini ──────────────────────────────────
        $baseQuery = Koi::whereNotNull('sale_status')
            ->whereMonth('sale_tanggal', $currentMonth)
            ->whereYear('sale_tanggal', $currentYear);

        $totalSalesIdr  = (clone $baseQuery)->sum('sell_idr');
        $totalKoiSold   = (clone $baseQuery)->count();
        $totalFee       = (clone $baseQuery)->sum('fee');

        // Modal = stock_price (JPY) × kurs × sale_qty
        $totalModal = (clone $baseQuery)
            ->selectRaw('SUM(COALESCE(stock_price,0) * COALESCE(kurs,0) * COALESCE(sale_qty,1)) as modal')
            ->value('modal') ?? 0;

        // Profit = (sell_idr - modal_terjual) + fee
        $totalProfit = (clone $baseQuery)
            ->selectRaw('SUM((COALESCE(sell_idr,0) - (COALESCE(stock_price,0) * COALESCE(kurs,0) * COALESCE(sale_qty,1))) + COALESCE(fee,0)) as profit')
            ->value('profit') ?? 0;

        // Margin lama (legacy)
        $totalMargin = (clone $baseQuery)
            ->selectRaw('SUM(sell_idr - (COALESCE(buy_jpy,0) * COALESCE(kurs,0) * COALESCE(sale_qty,1))) as margin')
            ->value('margin') ?? 0;

        // ── Stats keseluruhan ────────────────────────────────
        $totalKoi      = Koi::count();
        $totalCustomer = User::where('role_id', 2)->count();
        $totalBreeder  = Breeder::count();

        // ── Breakdown status bulan ini ───────────────────────
        $statusBreakdown = Koi::whereNotNull('sale_status')
            ->whereMonth('sale_tanggal', $currentMonth)
            ->whereYear('sale_tanggal', $currentYear)
            ->selectRaw('sale_status, count(*) as total')
            ->groupBy('sale_status')
            ->pluck('total', 'sale_status');

        // ── Transaksi terbaru ────────────────────────────────
        $recentTransactions = Koi::with(['breeder:id,name_breeder', 'customer:id,name'])
            ->whereNotNull('sale_status')
            ->orderByDesc('sale_tanggal')
            ->limit(8)
            ->get(['id', 'code', 'name', 'sale_invoice', 'sale_tanggal', 'sale_status', 'sell_idr', 'breeder_id', 'customer_id'])
            ->map(fn ($k) => [
                'id'           => $k->id,
                'code'         => $k->code,
                'name'         => $k->name,
                'sale_invoice' => $k->sale_invoice,
                'sale_tanggal' => $k->sale_tanggal,
                'sale_status'  => $k->sale_status,
                'sell_idr'     => $k->sell_idr,
                'breeder'      => $k->breeder?->name_breeder,
                'customer'     => $k->customer?->name,
            ]);

        // ── Activity log ─────────────────────────────────────
        $latestActivityLogs = ActivityLog::with('user:id,name')
            ->orderByDesc('created_at')->limit(10)->get()
            ->map(fn (ActivityLog $log) => [
                'id'          => $log->id,
                'user'        => $log->user ? ['name' => $log->user->name] : null,
                'action'      => $log->action,
                'description' => $log->description,
                'created_at'  => $log->created_at->toDateTimeString(),
            ]);

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_sales_idr'   => $totalSalesIdr,
                'total_modal'       => $totalModal,
                'total_profit'      => $totalProfit,
                'total_margin'      => $totalMargin,
                'total_koi_sold'    => $totalKoiSold,
                'total_fee'         => $totalFee,
                'total_koi'         => $totalKoi,
                'total_customer'    => $totalCustomer,
                'total_breeder'     => $totalBreeder,
                'status_breakdown'  => $statusBreakdown,
                'current_month'     => now()->translatedFormat('F Y'),
            ],
            'recentTransactions' => $recentTransactions,
            'latestActivityLogs' => $latestActivityLogs,
        ]);
    }
}
