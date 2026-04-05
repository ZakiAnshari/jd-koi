<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogUserLogout implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        $user = $event->user;
        if (!$user) {
            return; // Sometimes logout event may not have user
        }
        $userName = $user->name ?? $user->email;

        ActivityLog::create([
            'user_id' => $user->id,
            'module' => 'Autentikasi',
            'action' => 'logout',
            'description' => "User {$userName} melakukan logout",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
