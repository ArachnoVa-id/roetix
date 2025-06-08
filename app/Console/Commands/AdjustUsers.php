<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\AdjustUsersJob;

class AdjustUsers extends Command
{
    protected $signature = 'event:adjust-users {--continuous : Run continuously via jobs}';
    protected $description = 'Removes expired users from all events';

    public function handle()
    {
        if ($this->option('continuous')) {
            $this->info('Starting continuous user adjustment via queue jobs...');

            // Dispatch the first job, which will schedule subsequent ones
            AdjustUsersJob::dispatch();

            $this->info('Job dispatched. Use "php artisan queue:work" to process jobs.');
        } else {
            // Original synchronous behavior for manual testing
            $this->info('Running one-time user adjustment...');

            try {
                if (cache()->lock('adjust_users_lock', 10)->get()) {
                    try {
                        \App\Models\Event::adjustUsers();
                        $this->info("Successfully cleaned expired users for events");
                    } finally {
                        cache()->forget('adjust_users_lock');
                    }
                } else {
                    $this->warn("AdjustUsers skipped: lock is currently held by another process.");
                }
            } catch (\Throwable $e) {
                $this->error("Failed to clean up events " . $e->getMessage());
            }
        }

        return 0;
    }
}
