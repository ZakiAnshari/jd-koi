<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Koi;
use App\Exports\SalesReportExport;
use App\Exports\PurchasesReportExport;
use App\Exports\ProfitLossReportExport;
use App\Exports\StockReportExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Sales Report
     */
    public function salesReport(Request $request)
    {
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);

        $query = Sale::with(['koi:id,code,name', 'customer:id,name'])
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'asc');

        $sales = $query->get();

        // Group by date
        $salesByDate = $sales->groupBy(function ($item) {
            return $item->date->format('Y-m-d');
        })->map(function ($group) {
            return [
                'date' => $group->first()->date->format('Y-m-d'),
                'total_qty' => $group->sum('qty'),
                'total_jpy' => $group->sum('sell_jpy'),
                'total_idr' => $group->sum('sell_idr'),
                'items' => $group->map(fn($sale) => [
                    'id' => $sale->id,
                    'koi_code' => $sale->koi?->code,
                    'koi_name' => $sale->koi?->name,
                    'customer_name' => $sale->customer?->name,
                    'qty' => $sale->qty,
                    'sell_jpy' => $sale->sell_jpy,
                    'sell_idr' => $sale->sell_idr,
                ])->toArray(),
            ];
        })->values();

        // Group by koi
        $salesByKoi = $sales->groupBy('koi_id')->map(function ($group) {
            $koi = $group->first()->koi;
            return [
                'koi_id' => $koi->id,
                'koi_code' => $koi->code,
                'koi_name' => $koi->name,
                'total_qty' => $group->sum('qty'),
                'total_jpy' => $group->sum('sell_jpy'),
                'total_idr' => $group->sum('sell_idr'),
                'avg_price_jpy' => round($group->avg('sell_jpy'), 2),
                'avg_price_idr' => round($group->avg('sell_idr'), 2),
            ];
        })->values();

        $summary = [
            'total_qty' => $sales->sum('qty'),
            'total_jpy' => $sales->sum('sell_jpy'),
            'total_idr' => $sales->sum('sell_idr'),
            'avg_price_jpy' => $sales->count() > 0 ? round($sales->avg('sell_jpy'), 2) : 0,
            'avg_price_idr' => $sales->count() > 0 ? round($sales->avg('sell_idr'), 2) : 0,
        ];

        return Inertia::render('Reports/Sales', [
            'salesByDate' => $salesByDate,
            'salesByKoi' => $salesByKoi,
            'summary' => $summary,
            'month' => $month,
            'year' => $year,
        ]);
    }

    /**
     * Purchase Report
     */
    public function purchaseReport(Request $request)
    {
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);

        $query = Purchase::with(['koi:id,code,name'])
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'asc');

        $purchases = $query->get();

        // Group by date
        $purchasesByDate = $purchases->groupBy(function ($item) {
            return $item->date->format('Y-m-d');
        })->map(function ($group) {
            return [
                'date' => $group->first()->date->format('Y-m-d'),
                'total_qty' => $group->sum('qty'),
                'total_jpy' => $group->sum('price_jpy'),
                'total_idr' => $group->sum('price_idr'),
                'items' => $group->map(fn($purchase) => [
                    'id' => $purchase->id,
                    'koi_code' => $purchase->koi?->code,
                    'koi_name' => $purchase->koi?->name,
                    'qty' => $purchase->qty,
                    'price_jpy' => $purchase->price_jpy,
                    'price_idr' => $purchase->price_idr,
                ])->toArray(),
            ];
        })->values();

        // Group by koi
        $purchasesByKoi = $purchases->groupBy('koi_id')->map(function ($group) {
            $koi = $group->first()->koi;
            return [
                'koi_id' => $koi->id,
                'koi_code' => $koi->code,
                'koi_name' => $koi->name,
                'total_qty' => $group->sum('qty'),
                'total_jpy' => $group->sum('price_jpy'),
                'total_idr' => $group->sum('price_idr'),
            ];
        })->values();

        $summary = [
            'total_qty' => $purchases->sum('qty'),
            'total_jpy' => $purchases->sum('price_jpy'),
            'total_idr' => $purchases->sum('price_idr'),
        ];

        return Inertia::render('Reports/Purchases', [
            'purchasesByDate' => $purchasesByDate,
            'purchasesByKoi' => $purchasesByKoi,
            'summary' => $summary,
            'month' => $month,
            'year' => $year,
        ]);
    }

    /**
     * Profit & Loss Report
     */
    public function profitLossReport(Request $request)
    {
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);

        // Get sales data
        $sales = Sale::with(['koi:id,code,name'])
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        // Get purchase data
        $purchases = Purchase::with(['koi:id,code,name'])
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        // Summary
        $totalRevenue = $sales->sum('sell_idr');
        $totalProfit = $sales->sum('profit');
        $totalFee = $sales->sum('fee');
        $totalMargin = $sales->sum('margin');
        $totalExpense = $purchases->sum('price_idr');
        $netProfit = $totalRevenue - $totalExpense;

        // Breakdown per koi
        $koiBreakdown = [];
        $allKoiIds = collect();
        $allKoiIds = $allKoiIds->merge($sales->pluck('koi_id'))->merge($purchases->pluck('koi_id'))->unique();

        foreach ($allKoiIds as $koiId) {
            $koi = Koi::find($koiId);
            $koiSales = $sales->where('koi_id', $koiId);
            $koiPurchases = $purchases->where('koi_id', $koiId);

            $qtyBuy = $koiPurchases->sum('qty');
            $qtySell = $koiSales->sum('qty');
            $totalBuy = $koiPurchases->sum('price_idr');
            $totalSell = $koiSales->sum('sell_idr');
            $profit = $totalSell - $totalBuy;

            $koiBreakdown[] = [
                'koi_id' => $koi->id,
                'koi_code' => $koi->code,
                'koi_name' => $koi->name,
                'qty_buy' => $qtyBuy,
                'qty_sell' => $qtySell,
                'total_buy' => $totalBuy,
                'total_sell' => $totalSell,
                'profit' => $profit,
            ];
        }

        return Inertia::render('Reports/ProfitLoss', [
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_profit' => $totalProfit,
                'total_fee' => $totalFee,
                'total_margin' => $totalMargin,
                'total_expense' => $totalExpense,
                'net_profit' => $netProfit,
            ],
            'koiBreakdown' => $koiBreakdown,
            'month' => $month,
            'year' => $year,
        ]);
    }

    /**
     * Stock Report
     */
    public function stockReport(Request $request)
    {
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // Get all koi
        $kois = Koi::select(['id', 'code', 'name'])->orderBy('code')->get();

        $stockReports = [];

        foreach ($kois as $koi) {
            $movements = StockMovement::where('koi_id', $koi->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('created_at', 'asc')
                ->get();

            // Calculate stock movements
            $totalIn = $movements->where('type', 'in')->sum('qty');
            $totalOut = $movements->where('type', 'out')->sum('qty');

            // Get current stock
            $currentStock = Stock::where('koi_id', $koi->id)->first()?->qty ?? 0;

            // Calculate opening stock
            $openingStock = $currentStock - $totalIn + $totalOut;

            $stockReports[] = [
                'koi_id' => $koi->id,
                'koi_code' => $koi->code,
                'koi_name' => $koi->name,
                'opening_stock' => max(0, $openingStock),
                'stock_in' => $totalIn,
                'stock_out' => $totalOut,
                'closing_stock' => $currentStock,
                'movements' => $movements->map(fn($m) => [
                    'date' => $m->created_at->format('Y-m-d'),
                    'type' => $m->type,
                    'qty' => $m->qty,
                    'notes' => $m->notes,
                ])->toArray(),
            ];
        }

        return Inertia::render('Reports/Stock', [
            'stocks' => $stockReports,
            'month' => $month,
            'year' => $year,
        ]);
    }

    /**
     * Export Sales to Excel
     */
    public function exportSalesExcel(Request $request)
    {
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);

        return Excel::download(new SalesReportExport($month, $year), "Laporan-Penjualan-{$month}-{$year}.xlsx");
    }

    /**
     * Export Purchases to Excel
     */
    public function exportPurchasesExcel(Request $request)
    {
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);

        return Excel::download(new PurchasesReportExport($month, $year), "Laporan-Pembelian-{$month}-{$year}.xlsx");
    }

    /**
     * Export Profit & Loss to Excel
     */
    public function exportProfitLossExcel(Request $request)
    {
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);

        return Excel::download(new ProfitLossReportExport($month, $year), "Laporan-Laba-Rugi-{$month}-{$year}.xlsx");
    }

    /**
     * Export Stock to Excel
     */
    public function exportStockExcel(Request $request)
    {
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);

        return Excel::download(new StockReportExport($month, $year), "Laporan-Stok-{$month}-{$year}.xlsx");
    }
}
