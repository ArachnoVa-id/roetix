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
            $activeEvents = Event::where('status', 'active')->get();
            $dispatchedJobs = 0;

            // Process events in smaller batches to reduce SQLite lock contention
            $eventBatches = $activeEvents->chunk(2); // Process 2 events per batch

            foreach ($eventBatches as $batchIndex => $eventBatch) {
                foreach ($eventBatch as $event) {
                    // Add small delay between dispatches to reduce concurrent SQLite access
                    $delay = $batchIndex * 0.5; // 500ms delay between batches

                    AdjustUsersJob::dispatch($event->id)
                        ->onQueue('default')
                        ->delay(now()->addSeconds($delay));

                    $dispatchedJobs++;
                }
            }

            Log::info("Dispatched adjustment jobs for {$dispatchedJobs} events");
        } catch (\Throwable $e) {
            Log::error("Failed to dispatch event adjustment jobs: " . $e->getMessage());
            throw $e;
        }

        // Longer delay for SQLite to reduce lock contention
        self::dispatch()->delay(now()->addSeconds(8));
        Log::info('AdjustUsersDispatcherJob finished');
    }

    public function failed(\Throwable $exception)
    {
        Log::error('AdjustUsersDispatcherJob failed permanently: ' . $exception->getMessage());
        self::dispatch()->delay(now()->addSeconds(30));
    }
}

class AdjustUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5; // More retries for SQLite lock issues
    public $timeout = 120; // Longer timeout for SQLite operations
    public $maxExceptions = 2;
    public $backoff = [1, 3, 5, 10, 20]; // Exponential backoff for retries

    protected $eventId;

    public function __construct($eventId = null)
    {
        $this->eventId = $eventId;
        $this->onQueue('default');
    }

    public function handle()
    {
        if ($this->eventId) {
            $event = Event::find($this->eventId);

            if (!$event || $event->status !== 'active') {
                Log::info("Event {$this->eventId} not found or not active, skipping");
                return;
            }

            Log::info("AdjustUsersJob started for event {$event->id}");

            try {
                // Use application-level locking with longer timeout for SQLite
                $lockKey = "adjust_users_event_{$event->id}";
                $lockTimeout = 60; // 1 minute lock timeout

                if (cache()->lock($lockKey, $lockTimeout)->get()) {
                    try {
                        Event::adjustUsers($event->id);
                        Log::info("Successfully processed users for event {$event->id}");
                    } finally {
                        cache()->forget($lockKey);
                    }
                } else {
                    Log::warning("Event {$event->id} processing skipped: locked by another process");

                    // For SQLite, we might want to retry later rather than skip
                    if ($this->attempts() < 3) {
                        $this->release(5 + ($this->attempts() * 2)); // Progressive delay
                    }
                }
            } catch (\Throwable $e) {
                Log::error("Failed to process event {$event->id}: " . $e->getMessage());

                // Check if it's a SQLite lock error
                if (
                    strpos($e->getMessage(), 'database is locked') !== false ||
                    strpos($e->getMessage(), 'SQLITE_BUSY') !== false
                ) {

                    Log::warning("SQLite lock detected for event {$event->id}, will retry");

                    // Don't fail the job immediately for lock errors
                    if ($this->attempts() < $this->tries) {
                        $this->release(2 * $this->attempts()); // Exponential backoff
                        return;
                    }
                }

                throw $e;
            }

            Log::info("AdjustUsersJob finished for event {$event->id}");
        } else {
            // Legacy mode processing with staggered execution
            Log::info('AdjustUsersJob started (legacy mode - all events)');

            try {
                $globalLockKey = 'adjust_users_global_lock';

                if (cache()->lock($globalLockKey, 30)->get()) {
                    try {
                        // Process events one by one with small delays
                        $activeEvents = Event::where('status', 'active')->get();

                        foreach ($activeEvents as $index => $event) {
                            if ($index > 0) {
                                usleep(200000); // 200ms delay between events
                            }

                            try {
                                Event::adjustUsers($event->id);
                            } catch (\Exception $e) {
                                Log::error("Failed to process event {$event->id} in legacy mode: " . $e->getMessage());
                                // Continue with other events
                            }
                        }

                        Log::info("Successfully processed all events in legacy mode");
                    } finally {
                        cache()->forget($globalLockKey);
                    }
                } else {
                    Log::warning("Legacy mode processing skipped: locked by another process");
                }
            } catch (\Throwable $e) {
                Log::error("Failed to process events in legacy mode: " . $e->getMessage());
                throw $e;
            }

            // Schedule next job with longer delay for legacy mode
            self::dispatch()->delay(now()->addSeconds(15));
            Log::info('AdjustUsersJob finished (legacy mode)');
        }
    }

    public function failed(\Throwable $exception)
    {
        if ($this->eventId) {
            Log::error("AdjustUsersJob failed permanently for event {$this->eventId}: " . $exception->getMessage());

            // For critical failures, we might want to schedule a single retry after a longer delay
            if (strpos($exception->getMessage(), 'database is locked') === false) {
                // Don't retry lock errors, but retry other failures
                self::dispatch($this->eventId)->delay(now()->addMinutes(2));
            }
        } else {
            Log::error('AdjustUsersJob failed permanently (legacy mode): ' . $exception->getMessage());
            self::dispatch()->delay(now()->addMinutes(5));
        }
    }
}
