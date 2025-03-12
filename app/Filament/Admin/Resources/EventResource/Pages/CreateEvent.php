<?php

namespace App\Filament\Admin\Resources\EventResource\Pages;

use App\Filament\Admin\Resources\EventResource;
use App\Models\Event;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

use Illuminate\Database\Eloquent\Model;
use App\Models\TicketCategory;
use App\Models\EventCategoryTimeboundPrice;
use Illuminate\Support\Str;


class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    public function afterCreate()
    {
        $data = $this->data;
        $event_id = $this->record->event_id;

        $ticketCategories = $data['ticket_categories'] ?? [];

        // dd($event_id);

        // Create Ticket Categories
        if (!empty($ticketCategories)) {
            foreach ($ticketCategories as $category) {
                // Create Ticket Category
                $ticketCategory = TicketCategory::create([
                    'ticket_category_id' => Str::uuid(),
                    'event_id' => $event_id,
                    'name' => $category['name'],
                    'color' => $category['color'],
                ]);

                $ticketCategories[$category['name']] = $ticketCategory->ticket_category_id;

                if (!empty($category['event_category_timebound_prices'])) {
                    foreach ($category['event_category_timebound_prices'] as $timeboundPrice) {
                        EventCategoryTimeboundPrice::create([
                            'timebound_price_id' => Str::uuid(),
                            'ticket_category_id' => $ticketCategory->ticket_category_id,
                            'start_date' => $timeboundPrice['start_date'],
                            'end_date' => $timeboundPrice['end_date'],
                            'price' => $timeboundPrice['price'],
                        ]);
                    }
                }
            }
        }
    }
}
