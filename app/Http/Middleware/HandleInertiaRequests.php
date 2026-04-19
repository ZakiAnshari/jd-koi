<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user()?->loadMissing('role'),
            ],
            'activityLogTodayCount' => $request->user()?->role_id === 1
                ? ActivityLog::whereDate('created_at', now()->toDateString())->count()
                : 0,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'pendingPesananCount' => function () use ($request) {
                if ($request->user()?->role_id === 1) {
                    return Pesanan::where('status', 'pending')->count();
                }
                return 0;
            },
            'unreadNotifications' => function () {
                if (auth()->check()) {
                    return Notification::where('user_id', auth()->id())
                        ->where('is_read', false)
                        ->count();
                }
                return 0;
            },
        ];
    }
}
