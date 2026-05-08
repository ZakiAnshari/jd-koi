<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{

    public function index(Request $request): Response
    {
        $modules = [
            'Dashboard',
            'Data Koi',
            'Breeder',
            'Customer',
            'Pembelian',
            'Penjualan',
            'Stok Koi',
            'Autentikasi',
        ];

        $actions = ['create', 'update', 'delete', 'login', 'logout', 'export', 'import', 'print'];

        $filters = [
            'module' => $request->query('module', 'all'),
            'action' => $request->query('action', 'all'),
            'user_id' => $request->query('user_id', 'all'),
            'from' => $request->query('from', ''),
            'to' => $request->query('to', ''),
        ];

        $query = ActivityLog::with('user')->orderByDesc('created_at');

        if ($filters['module'] !== 'all' && $filters['module'] !== '') {
            $query->where('module', $filters['module']);
        }

        if ($filters['action'] !== 'all' && $filters['action'] !== '') {
            $query->where('action', $filters['action']);
        }

        if ($filters['user_id'] !== 'all' && $filters['user_id'] !== '') {
            $query->where('user_id', $filters['user_id']);
        }

        if ($filters['from']) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if ($filters['to']) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        $activityLogs = $query->paginate(5)->withQueryString();

        $users = User::select(['id', 'name'])->orderBy('name')->get();

        return Inertia::render('ActivityLogs/Index', [
            'activityLogs' => $activityLogs,
            'users' => $users,
            'modules' => $modules,
            'actions' => $actions,
            'filters' => $filters,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $log = ActivityLog::with('user')->findOrFail($id);

        return response()->json([
            'id' => $log->id,
            'user' => $log->user ? ['id' => $log->user->id, 'name' => $log->user->name] : null,
            'module' => $log->module,
            'action' => $log->action,
            'description' => $log->description,
            'old_values' => $log->old_values,
            'new_values' => $log->new_values,
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
            'created_at' => $log->created_at->toDateTimeString(),
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $days = (int) $request->input('days', 30);

        if ($days <= 0) {
            return back()->with('error', 'Parameter hari harus lebih besar dari 0.');
        }

        $threshold = now()->subDays($days);
        ActivityLog::where('created_at', '<', $threshold)->delete();

        return back()->with('success', "Log aktivitas lebih lama dari {$days} hari berhasil dihapus.");
    }
}
