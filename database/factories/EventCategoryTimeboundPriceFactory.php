<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\EventCategoryTimeboundPrice;
use App\Models\TicketCategory;
use App\Models\TimelineSession;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventCategoryTimeboundPrice>
 */
class EventCategoryTimeboundPriceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EventCategoryTimeboundPrice::class;
    
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ticketCategory = TicketCategory::inRandomOrder()->first();
        $eventId = $ticketCategory?->event_id;
        
        // Cari timeline session yang berhubungan dengan event dari ticket category
        $timelineSession = TimelineSession::where('event_id', $eventId)
            ->inRandomOrder()
            ->first();
            
        return [
            'timebound_price_id' => (string) Str::uuid(),
            'ticket_category_id' => $ticketCategory?->ticket_category_id,
            'timeline_id' => $timelineSession?->timeline_id,
            'price' => $this->faker->numberBetween(100000, 500000), // Harga dalam ribuan
        ];
    }
    
    /**
     * Define an early bird price variant.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function earlyBird()
    {
        return $this->state(function (array $attributes) {
            // Coba ambil timeline yang sesuai dengan "Early Bird"
            $timelineSession = TimelineSession::where('name', 'like', '%Early Bird%')
                ->orWhere('name', 'like', '%Presale 1%')
                ->inRandomOrder()
                ->first();
                
            if ($timelineSession) {
                return [
                    'timeline_id' => $timelineSession->timeline_id,
                    'price' => $this->faker->numberBetween(50000, 150000), // 50K-150K
                ];
            }
           
            return [
                'price' => $this->faker->numberBetween(50000, 150000), // 50K-150K
            ];
        });
    }
    
    /**
     * Define a regular price variant.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function regularPrice()
    {
        return $this->state(function (array $attributes) {
            // Coba ambil timeline yang sesuai dengan "Regular"
            $timelineSession = TimelineSession::where('name', 'like', '%Regular%')
                ->orWhere('name', 'like', '%General%')
                ->inRandomOrder()
                ->first();
                
            if ($timelineSession) {
                return [
                    'timeline_id' => $timelineSession->timeline_id,
                    'price' => $this->faker->numberBetween(150000, 300000), // 150K-300K
                ];
            }
           
            return [
                'price' => $this->faker->numberBetween(150000, 300000), // 150K-300K
            ];
        });
    }
    
    /**
     * Define a last minute price variant.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function lastMinute()
    {
        return $this->state(function (array $attributes) {
            // Coba ambil timeline yang sesuai dengan "Last Minute"
            $timelineSession = TimelineSession::where('name', 'like', '%Last Minute%')
                ->orWhere('name', 'like', '%Flash%')
                ->inRandomOrder()
                ->first();
                
            if ($timelineSession) {
                return [
                    'timeline_id' => $timelineSession->timeline_id,
                    'price' => $this->faker->numberBetween(250000, 500000), // 250K-500K
                ];
            }
           
            return [
                'price' => $this->faker->numberBetween(250000, 500000), // 250K-500K
            ];
        });
    }
}