<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            abort_if(!$user || !$user->role || $user->role->name !== 'admin', 403);
            return $next($request);
        });
    }

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

        $activityLogs = $query->paginate(25)->withQueryString();

        $users = User::select(['id', 'name'])->orderBy('name')->get();

        return Inertia::render('ActivityLogs/Index', [
            'activityLogs' => $activityLogs,
            'users' => $users,
            'modules' => $modules,
            'actions' => $actions,
            'filters' => $filters,
        ]);
    }
}
