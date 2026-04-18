<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Stock;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $completedSaleStatuses = ['azukari', 'shipped', 'sold'];

        $totalSales = Sale::whereIn('status', $completedSaleStatuses)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->sum('sell_idr');

        $totalProfit = Sale::whereIn('status', $completedSaleStatuses)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->sum('profit');

        $totalKoiSold = Sale::whereIn('status', $completedSaleStatuses)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->sum('qty');

        $totalStock = Stock::sum('qty');

        $latestActivityLogs = [];

        if ($request->user() && $request->user()->role && $request->user()->role->name === 'admin') {
            $latestActivityLogs = ActivityLog::with('user:id,name')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(function (ActivityLog $log) {
                    return [
                        'id' => $log->id,
                        'user' => $log->user ? ['name' => $log->user->name] : null,
                        'action' => $log->action,
                        'description' => $log->description,
                        'created_at' => $log->created_at->toDateTimeString(),
                    ];
                });
        }

        return Inertia::render('Dashboard', [
            'summary' => [
                'totalSales' => $totalSales,
                'totalProfit' => $totalProfit,
                'totalKoiSold' => $totalKoiSold,
                'totalStock' => $totalStock,
            ],
            'latestActivityLogs' => $latestActivityLogs,
        ]);
    }
}
