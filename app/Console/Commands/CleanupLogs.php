<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

class CleanupLogs extends Command
{
    protected $signature = 'logs:cleanup {--days=90 : Number of days to keep activity logs}';

    protected $description = 'Delete old activity logs older than the configured number of days.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $threshold = now()->subDays($days);

        $deleted = ActivityLog::where('created_at', '<', $threshold)->delete();

        $this->info("Berhasil menghapus {$deleted} log aktivitas yang lebih lama dari {$days} hari");

        return self::SUCCESS;
    }
}
