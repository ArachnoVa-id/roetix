<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EventCategoryTimeboundPrice;
use App\Models\TicketCategory;

class EventCategoryTimeboundPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Make sure we have ticket categories first
        if (TicketCategory::count() === 0) {
            $this->command->info('No ticket categories found. Please run the TicketCategorySeeder first.');
            return;
        }

        // Create early bird prices for each ticket category
        TicketCategory::all()->each(function ($ticketCategory) {
            EventCategoryTimeboundPrice::factory()
                ->earlyBird()
                ->count(1)
                ->create([
                    'ticket_category_id' => $ticketCategory->ticket_category_id,
                ]);
        });

        // Create regular prices for each ticket category
        TicketCategory::all()->each(function ($ticketCategory) {
            EventCategoryTimeboundPrice::factory()
                ->regularPrice()
                ->count(1)
                ->create([
                    'ticket_category_id' => $ticketCategory->ticket_category_id,
                ]);
        });

        // Create last minute prices for each ticket category
        TicketCategory::all()->each(function ($ticketCategory) {
            EventCategoryTimeboundPrice::factory()
                ->lastMinute()
                ->count(1)
                ->create([
                    'ticket_category_id' => $ticketCategory->ticket_category_id,
                ]);
        });

        // Create some random prices
        EventCategoryTimeboundPrice::factory()
            ->count(15)
            ->create();

        $this->command->info('Created ' . EventCategoryTimeboundPrice::count() . ' timebound prices.');
    }
}