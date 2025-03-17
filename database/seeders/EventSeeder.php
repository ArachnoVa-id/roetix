<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use Illuminate\Support\Str;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Event::factory()->count(10)->create()->each(function ($event) {
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
        });
    }

    private function randomColor(): string
    {
        return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
    }
}
