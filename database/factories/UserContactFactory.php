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
            'contact_id' => (string) Str::uuid(), // ID unik untuk contact, menggunakan UUID
            'phone_number' => $this->faker->phoneNumber, // Menghasilkan nomor telepon acak
            'email' => $this->faker->unique()->safeEmail, // Menghasilkan email unik
            'whatsapp_number' => $this->faker->phoneNumber, // Menghasilkan nomor whatsapp acak (sama dengan phone_number)
            'instagram' => $this->faker->userName, // Menghasilkan nama pengguna Instagram acak
        ];
    }
}
