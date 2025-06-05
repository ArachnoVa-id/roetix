<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Event;

class AdjustUsers extends Command
{
    protected $signature = 'event:adjust-users';
    protected $description = 'Removes expired users from all events';

    public function handle()
    {
        $this->info('CleanupExpiredUsers command started.');

        try {
            $this->info("Cleaning expired users for events");

            if (cache()->lock('adjust_users_lock', 10)->get()) {
                try {
                    Event::adjustUsers();
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

        $this->info('AdjustUsers command finished.');

        return 0;
    }
}
