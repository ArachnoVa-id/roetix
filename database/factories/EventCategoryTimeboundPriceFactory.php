<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\EventCategoryTimeboundPrice;
use App\Models\TicketCategory;
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
        $start_date = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $end_date = $this->faker->dateTimeBetween($start_date, '+3 months');

        return [
            'timebound_price_id' => (string) Str::uuid(),
            'ticket_category_id' => TicketCategory::inRandomOrder()->first()?->ticket_category_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'price' => $this->faker->randomFloat(2, 10, 500),
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
            $start_date = $this->faker->dateTimeBetween('-1 month', 'now');
            $end_date = $this->faker->dateTimeBetween('+1 day', '+2 weeks');
            
            return [
                'start_date' => $start_date,
                'end_date' => $end_date,
                'price' => $this->faker->randomFloat(2, 10, 100),
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
            $start_date = $this->faker->dateTimeBetween('+1 day', '+2 weeks');
            $end_date = $this->faker->dateTimeBetween('+3 weeks', '+2 months');
            
            return [
                'start_date' => $start_date,
                'end_date' => $end_date,
                'price' => $this->faker->randomFloat(2, 100, 300),
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
            // Menggunakan Carbon untuk menghindari masalah interval
            $now = Carbon::now();
            $start_date = $now->copy()->addDays(30); // 1 bulan dari sekarang
            $end_date = $now->copy()->addDays(60);   // 2 bulan dari sekarang
            
            return [
                'start_date' => $start_date,
                'end_date' => $end_date,
                'price' => $this->faker->randomFloat(2, 300, 500),
            ];
        });
    }
}