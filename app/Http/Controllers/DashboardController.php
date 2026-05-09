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

        // Check if date range filter is used
        $useDateRange = $request->filled('start_date') || $request->filled('end_date');
        $startDate = $request->filled('start_date') ? \Carbon\Carbon::parse($request->start_date) : null;
        $endDate = $request->filled('end_date') ? \Carbon\Carbon::parse($request->end_date)->endOfDay() : null;

        if ($useDateRange) {
            // Use date range filter
            $baseQuery = Koi::whereNotNull('sale_status');
            
            if ($startDate) {
                $baseQuery->whereDate('sale_tanggal', '>=', $startDate);
            }
            if ($endDate) {
                $baseQuery->whereDate('sale_tanggal', '<=', $endDate);
            }

            $currentMonth = now()->month;
            $currentYear = now()->year;
            $currentMonthLabel = "Rentang: " . ($startDate?->format('d M Y') ?? 'Awal') . " - " . ($endDate?->format('d M Y') ?? 'Akhir');
        } else {
            // Use month/year filter
            $currentMonth = $request->filled('month') ? (int) $request->month : now()->month;
            $currentYear  = $request->filled('year')  ? (int) $request->year  : now()->year;

            // Clamp to valid ranges
            $currentMonth = max(1, min(12, $currentMonth));
            $currentYear  = max(2020, min((int) now()->year, $currentYear));

            $baseQuery = Koi::whereNotNull('sale_status')
                ->whereMonth('sale_tanggal', $currentMonth)
                ->whereYear('sale_tanggal', $currentYear);
            
            $currentMonthLabel = \Carbon\Carbon::createFromDate($currentYear, $currentMonth, 1)->translatedFormat('F Y');
        }

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

        // ── Breakdown status ─────────────────────────────────
        $statusBreakdown = (clone $baseQuery)
            ->selectRaw('sale_status, count(*) as total')
            ->groupBy('sale_status')
            ->pluck('total', 'sale_status');

        // ── Transaksi terbaru ────────────────────────────────
        $recentTransactions = (clone $baseQuery)->with(['breeder:id,name_breeder', 'customer:id,name'])
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

        // ── Trend (jika tidak menggunakan date range, tampilkan 6 bulan; jika date range, tampilkan per hari) ───
        if ($useDateRange && $startDate && $endDate) {
            // Show daily trend
            $trendData = collect();
            $currentDate = $startDate->copy();
            while ($currentDate <= $endDate) {
                $q = Koi::whereNotNull('sale_status')
                    ->whereDate('sale_tanggal', $currentDate->toDateString());

                $sales  = (clone $q)->sum('sell_idr');
                $profit = (clone $q)
                    ->selectRaw('SUM((COALESCE(sell_idr,0) - (COALESCE(stock_price,0) * COALESCE(kurs,0) * COALESCE(sale_qty,1))) + COALESCE(fee,0)) as profit')
                    ->value('profit') ?? 0;
                $count  = (clone $q)->count();
                $fee    = (clone $q)->sum('fee');

                $trendData->push([
                    'label'  => $currentDate->format('d M'),
                    'sales'  => (float) $sales,
                    'profit' => (float) $profit,
                    'count'  => (int) $count,
                    'fee'    => (float) $fee,
                ]);

                $currentDate->addDay();
            }
        } else {
            // Show 6 months trend
            $trendMonths = collect();
            for ($i = 5; $i >= 0; $i--) {
                $date = \Carbon\Carbon::createFromDate($currentYear, $currentMonth, 1)->subMonths($i);
                $trendMonths->push([
                    'month' => $date->month,
                    'year'  => $date->year,
                    'label' => $date->translatedFormat('M Y'),
                ]);
            }

            $trendData = $trendMonths->map(function ($m) {
                $q = Koi::whereNotNull('sale_status')
                    ->whereMonth('sale_tanggal', $m['month'])
                    ->whereYear('sale_tanggal', $m['year']);

                $sales  = (clone $q)->sum('sell_idr');
                $profit = (clone $q)
                    ->selectRaw('SUM((COALESCE(sell_idr,0) - (COALESCE(stock_price,0) * COALESCE(kurs,0) * COALESCE(sale_qty,1))) + COALESCE(fee,0)) as profit')
                    ->value('profit') ?? 0;
                $count  = (clone $q)->count();
                $fee    = (clone $q)->sum('fee');

                return [
                    'label'  => $m['label'],
                    'sales'  => (float) $sales,
                    'profit' => (float) $profit,
                    'count'  => (int) $count,
                    'fee'    => (float) $fee,
                ];
            });
        }

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
                'current_month'     => $currentMonthLabel,
                'selected_month'    => $currentMonth,
                'selected_year'     => $currentYear,
                'selected_start_date' => $startDate?->format('Y-m-d'),
                'selected_end_date'   => $endDate?->format('Y-m-d'),
            ],
            'trendData'          => $trendData,
            'recentTransactions' => $recentTransactions,
            'latestActivityLogs' => $latestActivityLogs,
        ]);
    }
}
