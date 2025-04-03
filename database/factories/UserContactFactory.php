<?php

namespace Database\Factories;

use App\Models\UserContact;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserContact>
 */
class UserContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nickname' => $this->faker->userName,
            'fullname' => $this->faker->name,
            'avatar' => $this->faker->imageUrl(),
            'phone_number' => $this->faker->phoneNumber,
            'email' => $this->faker->unique()->safeEmail,
            'whatsapp_number' => $this->faker->phoneNumber,
            'instagram' => $this->faker->userName,
            'birth_date' => $this->faker->date,
            'gender' => $this->faker->randomElement(['M', 'F']),
            'address' => $this->faker->address,
        ];
    }
}
