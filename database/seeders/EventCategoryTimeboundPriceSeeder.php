<?php
namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EventCategoryTimeboundPrice;
use App\Models\TicketCategory;
use App\Models\TimelineSession;
use App\Models\Event;

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
        
        // Make sure we have timeline sessions
        if (TimelineSession::count() === 0) {
            $this->command->info('No timeline sessions found. Please run the TimelineSessionSeeder first.');
            return;
        }
        
        // Bersihkan data yang ada
        EventCategoryTimeboundPrice::query()->delete();
        
        // Buat harga untuk setiap kategori tiket dengan setiap timeline pada event
        TicketCategory::all()->each(function ($ticketCategory) {
            // Get event ID dari ticket category
            $eventId = $ticketCategory->event_id;
            
            // Get all timelines untuk event ini
            $timelines = TimelineSession::where('event_id', $eventId)->get();
            
            if ($timelines->isEmpty()) {
                $this->command->info("No timeline sessions found for event: {$eventId}");
                return;
            }
            
            // Kategori VIP atau VVIP biasanya lebih mahal
            $isVip = str_contains(strtolower($ticketCategory->name), 'vip');
            $multiplier = $isVip ? 2 : 1;
            
            // Buat harga untuk setiap timeline
            foreach ($timelines as $timeline) {
                EventCategoryTimeboundPrice::create([
                    'ticket_category_id' => $ticketCategory->ticket_category_id,
                    'timeline_id' => $timeline->timeline_id,
                    'price' => $this->getPriceBasedOnTimelineName($timeline->name) * $multiplier,
                ]);
            }
        });
        
        $this->command->info('Created ' . EventCategoryTimeboundPrice::count() . ' timebound prices.');
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
}