<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Event;
use App\Models\Seat;
use App\Models\Team;
use App\Models\TimelineSession;
use App\Models\Venue;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Event::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->sentence(3);
        $slug = Str::slug($name);

        // Create end_date 1-2 months in the future
        $endDate = $this->faker->dateTimeBetween('+1 month', '+2 months');

        return [
            'team_id' => Team::inRandomOrder()->first()?->team_id,
            'venue_id' => Venue::inRandomOrder()->first()?->venue_id,
            'name' => $name,
            'slug' => $slug,
            'start_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'event_date' => $endDate,
            'location' => $this->faker->address(),
            'status' => $this->faker->randomElement(EventStatus::values()),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Event $event) {
            // Event Variables Creation
            $this->eventVariables($event);

            // Timeline Parting Creation
            $this->timelineParting($event);

            // Category Segmentation Creation
            $this->categorySegmentation($event);

            // Timebound Prices Creation
            $this->timeboundPrices($event);

            // Tickets Generation
            $this->ticketsGeneration($event);
        });
    }

    private function eventVariables(Event $event)
    {
        $event->eventVariables()->create([
            'is_locked' => false,
            'locked_password' => '',
            'is_maintenance' => false,
            'maintenance_title' => '',
            'maintenance_message' => '',
            'maintenance_expected_finish' => now(),
            'logo' => '/images/novatix-logo/favicon-32x32.png',
            'favicon' => '/images/novatix-logo/favicon.ico',
            'primary_color' => $this->randomColor(),
            'secondary_color' => $this->randomColor(),
            'text_primary_color' => $this->randomColor(),
            'text_secondary_color' => $this->randomColor(),
        ]);
    }

    private $sessionNames = [
        'Early Bird',
        'Presale 1',
        'Presale 2',
        'Member Access',
        'VIP Access',
        'Regular Sale',
        'Flash Sale',
        'General Sale',
        'Last Minute Sale'
    ];

    private function timelineParting(Event $event)
    {
        $eventStartDate = Carbon::parse($event->start_date);
        $eventEndDate = Carbon::parse($event->event_date);

        $timelineStartDate = $eventEndDate->clone()->subDays(30);
        if ($timelineStartDate->lessThanOrEqualTo($eventStartDate)) {
            $timelineStartDate = $eventStartDate->clone();
        }

        $timelineEndDate = $eventEndDate->clone();
        $availableDays = $timelineStartDate->diffInDays($timelineEndDate);

        if ($availableDays < 2) return;

        $sessionCount = min(5, max(1, (int) floor($availableDays / 3)));

        // Calculate base duration for each session
        $baseDuration = floor($availableDays / $sessionCount);
        $extraDays = $availableDays % $sessionCount; // Distribute these across sessions

        $currentDate = $timelineStartDate->clone();

        for ($i = 0; $i < $sessionCount; $i++) {
            // Distribute remaining days across initial sessions
            $durationDays = $baseDuration + ($i < $extraDays ? 1 : 0);

            $nextEndDate = $currentDate->clone()->addDays($durationDays);

            // Ensure the last session ends exactly at eventStartDate
            if ($i === $sessionCount - 1 || $nextEndDate->greaterThan($eventEndDate)) {
                $nextEndDate = $eventEndDate->clone();
            }

            TimelineSession::create([
                'event_id' => $event->event_id,
                'name' => $this->sessionNames[$i % count($this->sessionNames)],
                'start_date' => $currentDate,
                'end_date' => $nextEndDate,
            ]);

            // Move to the next session start date
            $currentDate = $nextEndDate->clone()->addDay();
        }
    }

    // Generate a random color
    private function randomColor(): string
    {
        return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
    }

    private function categorySegmentation(Event $event)
    {
        $categoryCount = rand(3, 5);
        for ($i = 0; $i < $categoryCount; $i++) {
            $event->ticketCategories()->create([
                'event_id' => $event->event_id,
                'name' => $this->faker->sentence(2),
                'color' => $this->randomColor(),
            ]);
        }
    }

    /**
     * Determine price based on timeline name
     */
    private function getPriceBasedOnTimelineName($name)
    {
        $nameInLowerCase = strtolower($name);

        // Harga dasar berdasarkan nama timeline
        if (str_contains($nameInLowerCase, 'early') || str_contains($nameInLowerCase, 'presale 1')) {
            // Early Bird atau Presale 1 - harga terendah
            return rand(50000, 150000); // 50K - 150K
        } elseif (str_contains($nameInLowerCase, 'presale 2') || str_contains($nameInLowerCase, 'member')) {
            // Presale 2 atau Member Access - sedikit lebih tinggi
            return rand(100000, 200000); // 100K - 200K
        } elseif (str_contains($nameInLowerCase, 'regular') || str_contains($nameInLowerCase, 'general')) {
            // Regular atau General - harga standar
            return rand(150000, 250000); // 150K - 250K
        } elseif (str_contains($nameInLowerCase, 'vip')) {
            // VIP Access - harga premium
            return rand(300000, 500000); // 300K - 500K
        } elseif (str_contains($nameInLowerCase, 'last') || str_contains($nameInLowerCase, 'flash')) {
            // Last Minute atau Flash Sale - bisa lebih rendah atau lebih tinggi
            return rand(200000, 300000); // 200K - 300K
        } else {
            // Harga default
            return rand(100000, 300000); // 100K - 300K
        }
    }

    private function timeboundPrices(Event $event)
    {
        // Make sure we have ticket categories first
        if ($event->ticketCategories->isEmpty()) return;

        // Make sure we have timeline sessions
        if ($event->timelineSessions->isEmpty()) return;

        // Pricing for each category
        foreach ($event->ticketCategories as $ticketCategory) {
            foreach ($event->timelineSessions as $timeline) {
                $timeline->eventCategoryTimeboundPrices()->create([
                    'ticket_category_id' => $ticketCategory->ticket_category_id,
                    'price' => $this->getPriceBasedOnTimelineName($timeline->name),
                ]);
            }
        }
    }

    private function ticketsGeneration(Event $event)
    {
        // seats in that events venue
        $seatIds = Seat::where('venue_id', Event::find($event->event_id)->venue_id)
            ->pluck('seat_id')
            ->toArray();

        if (empty($seatIds)) return;

        // Random number of tickets (1 to 80% of total seats)
        $numTickets = rand(1, (int) (count($seatIds) * 0.8));

        // Randomly select seats
        $selectedSeatIds = collect($seatIds)->random($numTickets);

        // Get ticket categories for this event
        $ticketCategories = $event->ticketCategories;

        // If no categories exist, return early
        if ($ticketCategories->isEmpty()) return;

        // Get current active timeline
        $currentDate = Carbon::now();
        $activeTimeline = TimelineSession::where('event_id', $event->event_id)
            ->where('start_date', '<=', $currentDate)
            ->where('end_date', '>=', $currentDate)
            ->first();

        // If no active timeline, get the earliest one
        if (!$activeTimeline) {
            $activeTimeline = $event->timelineSessions()->orderBy('start_date')->first();
        }

        foreach ($selectedSeatIds as $seatId) {
            // Select a random category
            $category = $ticketCategories->random();

            // Get appropriate price based on category and timeline
            $price = 0;
            if ($activeTimeline) {
                $priceData = $activeTimeline->eventCategoryTimeboundPrices()
                    ->where('ticket_category_id', $category->ticket_category_id)
                    ->first();

                if ($priceData) {
                    $price = $priceData->price;
                }
            }

            // If no price found, use a random value
            if ($price == 0) {
                $price = $this->faker->randomFloat(2, 10, 100);
            }

            $event->tickets()->create([
                'seat_id' => $seatId,
                'team_id' => $event->team_id,
                'ticket_category_id' => $category->ticket_category_id,
                'ticket_type' => $category->name,
                'price' => $price,
                'status' => TicketStatus::AVAILABLE,
            ]);
        }
    }
}
