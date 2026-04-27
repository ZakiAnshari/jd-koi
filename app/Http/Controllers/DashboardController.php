<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Koi;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Stock;
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

        $isAdmin = true;

        $currentMonth = now()->month;
        $currentYear  = now()->year;
        $completedSaleStatuses = ['azukari', 'shipped', 'sold'];

        $totalSales = Sale::whereIn('status', $completedSaleStatuses)
            ->whereMonth('date', $currentMonth)->whereYear('date', $currentYear)->sum('sell_idr');
        $totalProfit = Sale::whereIn('status', $completedSaleStatuses)
            ->whereMonth('date', $currentMonth)->whereYear('date', $currentYear)->sum('profit');
        $totalKoiSold = Sale::whereIn('status', $completedSaleStatuses)
            ->whereMonth('date', $currentMonth)->whereYear('date', $currentYear)->sum('qty');
        $totalStock = Stock::sum('qty');

        $latestActivityLogs = [];
        if ($isAdmin) {
            $latestActivityLogs = ActivityLog::with('user:id,name')
                ->orderByDesc('created_at')->limit(10)->get()
                ->map(fn (ActivityLog $log) => [
                    'id'          => $log->id,
                    'user'        => $log->user ? ['name' => $log->user->name] : null,
                    'action'      => $log->action,
                    'description' => $log->description,
                    'created_at'  => $log->created_at->toDateTimeString(),
                ]);
        }

        $adminStats = [];
        if ($isAdmin) {
            $customerRoleId = Role::where('name', 'customer')->value('id');

            $adminStats = [
                'total_koi'       => Koi::count(),
                'total_pembelian' => Purchase::count(),
                'total_customer'  => $customerRoleId ? User::where('role_id', $customerRoleId)->count() : 0,
            ];
        }

        return Inertia::render('Dashboard', [
            'summary'            => compact('totalSales', 'totalProfit', 'totalKoiSold', 'totalStock'),
            'latestActivityLogs' => $latestActivityLogs,
            'adminStats'         => $adminStats,
            'customerStats'      => [],
        ]);
    }
}
