<?php

namespace App\Jobs;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AdjustUsersDispatcherJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle()
    {
        Log::info('AdjustUsersDispatcherJob started');

        try {
            // Get all active events
            $activeEvents = Event::where('status', 'active')->get();

            // Dispatch individual jobs for each event
            foreach ($activeEvents as $event) {
                AdjustUsersJob::dispatch($event->id)
                    ->onQueue('default');
            }

            Log::info("Dispatched adjustment jobs for " . $activeEvents->count() . " events");
        } catch (\Throwable $e) {
            Log::error("Failed to dispatch event adjustment jobs: " . $e->getMessage());
            throw $e;
        }

        // Schedule the next dispatcher to run in 5 seconds for more frequent processing
        self::dispatch()->delay(now()->addSeconds(5));

        Log::info('AdjustUsersDispatcherJob finished');
    }

    public function failed(\Throwable $exception)
    {
        Log::error('AdjustUsersDispatcherJob failed permanently: ' . $exception->getMessage());
        // Retry after a shorter delay
        self::dispatch()->delay(now()->addSeconds(30));
    }
}

// Modified AdjustUsersJob to work with single events
class AdjustUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $maxExceptions = 1;

    protected $eventId;

    public function __construct($eventId = null)
    {
        $this->eventId = $eventId;
        $this->onQueue('default');
    }

    public function handle()
    {
        if ($this->eventId) {
            // Process single event
            $event = Event::find($this->eventId);

            if (!$event || $event->status !== 'active') {
                Log::info("Event {$this->eventId} not found or not active, skipping");
                return;
            }

            Log::info("AdjustUsersJob started for event {$event->id}");

            try {
                // Use a per-event lock to prevent conflicts
                $lockKey = "adjust_users_event_{$event->id}";

                if (cache()->lock($lockKey, 30)->get()) {
                    try {
                        Event::adjustUsers($event->id);
                        Log::info("Successfully cleaned expired users for event {$event->id}");
                    } finally {
                        cache()->forget($lockKey);
                    }
                } else {
                    Log::warning("AdjustUsers for event {$event->id} skipped: lock held by another process");
                }
            } catch (\Throwable $e) {
                Log::error("Failed to clean up event {$event->id}: " . $e->getMessage());
                throw $e;
            }

            Log::info("AdjustUsersJob finished for event {$event->id}");
        } else {
            // Legacy mode - process all events (for backward compatibility)
            Log::info('AdjustUsersJob started (legacy mode - all events)');

            try {
                if (cache()->lock('adjust_users_lock', 10)->get()) {
                    try {
                        Event::adjustUsers();
                        Log::info("Successfully cleaned expired users for all events");
                    } finally {
                        cache()->forget('adjust_users_lock');
                    }
                } else {
                    Log::warning("AdjustUsers skipped: lock is currently held by another process");
                }
            } catch (\Throwable $e) {
                Log::error("Failed to clean up events: " . $e->getMessage());
                throw $e;
            }

            // Schedule the next job to run in 10 seconds
            self::dispatch()->delay(now()->addSeconds(10));
            Log::info('AdjustUsersJob finished (legacy mode)');
        }
    }

    public function failed(\Throwable $exception)
    {
        if ($this->eventId) {
            Log::error("AdjustUsersJob failed permanently for event {$this->eventId}: " . $exception->getMessage());
        } else {
            Log::error('AdjustUsersJob failed permanently: ' . $exception->getMessage());
            // Optionally schedule a retry after a longer delay (legacy mode)
            self::dispatch()->delay(now()->addMinutes(5));
        }
    }
}
