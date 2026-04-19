<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Koi;
use App\Models\KatalogKoi;
use App\Models\Notification;
use App\Models\Pesanan;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // --- Shared legacy summary (kept for backward compat) ---
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

        // --- Admin-specific stats ---
        $adminStats = [];
        if ($isAdmin) {
            $customerRoleId = Role::where('name', 'customer')->value('id');

            $pesananTerbaru = Pesanan::with(['customer:id,name,email', 'katalogKoi.koi:id,name'])
                ->latest()->limit(10)->get()
                ->map(fn (Pesanan $p) => [
                    'id'           => $p->id,
                    'kode_pesanan' => $p->kode_pesanan,
                    'qty'          => $p->qty,
                    'total_harga'  => $p->total_harga,
                    'status'       => $p->status,
                    'created_at'   => $p->created_at->toDateTimeString(),
                    'customer'     => $p->customer ? ['name' => $p->customer->name, 'email' => $p->customer->email] : null,
                    'koi_nama'     => $p->katalogKoi?->koi?->name ?? '-',
                ]);

            $lastMonth = now()->subMonth();

            $koiTerlaris = Pesanan::whereIn('pesanans.status', ['confirmed', 'completed'])
                ->join('katalog_kois', 'pesanans.katalog_koi_id', '=', 'katalog_kois.id')
                ->join('kois', 'katalog_kois.koi_id', '=', 'kois.id')
                ->select('kois.id', 'kois.name', DB::raw('SUM(pesanans.qty) as total_dipesan'))
                ->groupBy('kois.id', 'kois.name')
                ->orderByDesc('total_dipesan')
                ->limit(5)
                ->get()
                ->map(fn ($item) => [
                    'id'            => $item->id,
                    'name'          => $item->name,
                    'total_dipesan' => (int) $item->total_dipesan,
                ]);

            $adminStats = [
                'total_koi'          => Koi::count(),
                'total_pembelian'    => Purchase::count(),
                'total_katalog'      => KatalogKoi::count(),
                'total_pesanan'      => Pesanan::count(),
                'pesanan_pending'    => Pesanan::where('status', 'pending')->count(),
                'pesanan_confirmed'  => Pesanan::where('status', 'confirmed')->count(),
                'pesanan_completed'  => Pesanan::where('status', 'completed')->count(),
                'pesanan_hari_ini'   => Pesanan::whereDate('created_at', today())->count(),
                'total_customer'     => $customerRoleId ? User::where('role_id', $customerRoleId)->count() : 0,
                'total_koi_tersedia' => KatalogKoi::where('qty', '>', 0)->count(),
                'total_revenue'      => Pesanan::whereIn('status', ['confirmed', 'completed'])->sum('total_harga'),
                'revenue_bulan_ini'  => Pesanan::whereIn('status', ['confirmed', 'completed'])
                    ->whereMonth('created_at', $currentMonth)->whereYear('created_at', $currentYear)
                    ->sum('total_harga'),
                'revenue_bulan_lalu' => Pesanan::whereIn('status', ['confirmed', 'completed'])
                    ->whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year)
                    ->sum('total_harga'),
                'koi_terlaris'       => $koiTerlaris,
                'pesanan_terbaru'    => $pesananTerbaru,
            ];
        }

        // --- Customer-specific stats ---
        $customerStats = [];
        if ($user && !$isAdmin) {
            $pesananTerbaru = $user->pesanans()
                ->with(['katalogKoi.koi:id,name'])
                ->latest()->limit(5)->get()
                ->map(fn (Pesanan $p) => [
                    'id'           => $p->id,
                    'kode_pesanan' => $p->kode_pesanan,
                    'qty'          => $p->qty,
                    'total_harga'  => $p->total_harga,
                    'status'       => $p->status,
                    'created_at'   => $p->created_at->toDateTimeString(),
                    'koi_nama'     => $p->katalogKoi?->koi?->name ?? '-',
                ]);

            $notifikasiTerbaru = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->latest()->limit(3)->get()
                ->map(fn (Notification $n) => [
                    'id'         => $n->id,
                    'title'      => $n->title,
                    'message'    => $n->message,
                    'type'       => $n->type,
                    'created_at' => $n->created_at->toDateTimeString(),
                ]);

            $customerStats = [
                'nama'              => $user->name,
                'total_pesanan'     => $user->pesanans()->count(),
                'pesanan_diproses'  => $user->pesanans()->whereIn('status', ['pending', 'confirmed'])->count(),
                'pesanan_completed' => $user->pesanans()->where('status', 'completed')->count(),
                'pesanan_cancelled' => $user->pesanans()->where('status', 'cancelled')->count(),
                'total_belanja'     => $user->pesanans()->where('status', 'completed')->sum('total_harga'),
                'pesanan_terbaru'   => $pesananTerbaru,
                'notifikasi_terbaru' => $notifikasiTerbaru,
            ];
        }

        return Inertia::render('Dashboard', [
            'summary'            => compact('totalSales', 'totalProfit', 'totalKoiSold', 'totalStock'),
            'latestActivityLogs' => $latestActivityLogs,
            'adminStats'         => $adminStats,
            'customerStats'      => $customerStats,
        ]);
    }
}
