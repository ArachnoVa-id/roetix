<?php

namespace App\Jobs;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AdjustUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $maxExceptions = 1;

    public function __construct()
    {
        // Set the queue using the Queueable trait method
        $this->onQueue('default');
    }

    public function handle()
    {
        Log::info('AdjustUsersJob started');

        try {
            if (cache()->lock('adjust_users_lock', 10)->get()) {
                try {
                    Event::adjustUsers();
                    Log::info("Successfully cleaned expired users for events");
                } finally {
                    cache()->forget('adjust_users_lock');
                }
            } else {
                Log::warning("AdjustUsers skipped: lock is currently held by another process");
            }
        } catch (\Throwable $e) {
            Log::error("Failed to clean up events: " . $e->getMessage());
            throw $e; // Re-throw to trigger retry mechanism
        }

        // Schedule the next job to run in 10 seconds
        self::dispatch()->delay(now()->addSeconds(10));

        Log::info('AdjustUsersJob finished');
    }

    public function failed(\Throwable $exception)
    {
        Log::error('AdjustUsersJob failed permanently: ' . $exception->getMessage());

        // Optionally schedule a retry after a longer delay
        self::dispatch()->delay(now()->addMinutes(5));
    }
}
