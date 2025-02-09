<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Event;
use App\Models\TicketCategory;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "coupon_id" => (string) Str::uuid(),
            "event_id" => Event::inRandomOrder()->first()?->event_id ?? Event::factory(),
            "name" => $this->faker->sentence(2),
            "code" => $this->faker->bothify('??##??'),
            "discount_amount" => $this->faker->randomFloat(2, 0, 10),
            "expiry_date" => $this->faker->dateTimeBetween('+1 year', '+2 years'),
            "quantity" => $this->faker->numberBetween(1, 100),
            "applicable_categories" => TicketCategory::inRandomOrder()->first()?->ticket_category_id ?? TicketCategory::factory(),
            "status" => $this->faker->randomElement(['active', 'expired', 'used']),
        ];
    }
}
