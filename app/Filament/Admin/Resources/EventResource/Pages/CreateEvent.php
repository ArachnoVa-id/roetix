<?php

namespace App\Filament\Admin\Resources\EventResource\Pages;

use App\Filament\Admin\Resources\EventResource;
use App\Models\Event;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

use Illuminate\Database\Eloquent\Model;
use App\Models\TicketCategory;
use App\Models\EventCategoryTimeboundPrice;
use App\Models\EventVariables;
use Filament\Facades\Filament;
use Illuminate\Support\Str;


class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $tenant = Filament::getTenant();
        $data['team_id'] = $tenant->team_id;
        $data['team_code'] = $tenant->code;

        return $data;
    }

    public function afterCreate()
    {
        $data = $this->data;
        $event_id = $this->record->event_id;

        $ticketCategories = $data['ticket_categories'] ?? [];

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

        // Create Event Variables
        $eventVariables = [
            'is_locked' => $data['is_locked'] ?? false,
            'locked_password' => $data['locked_password'] ?? '',

            'is_maintenance' => $data['is_maintenance'] ?? false,
            'maintenance_title' => $data['maintenance_title'] ?? '',
            'maintenance_message' => $data['maintenance_message'] ?? '',
            'maintenance_expected_finish' => $data['maintenance_expected_finish'] ?? date('Y-m-d H:i:s', strtotime('+100 years')),

            'logo' => $data['logo'] ?? '',
            'favicon' => $data['favicon'] ?? '',
            'primary_color' => $data['primary_color'] ?? '#000000',
            'secondary_color' => $data['secondary_color'] ?? '#000000',
            'text_primary_color' => $data['text_primary_color'] ?? '#000000',
            'text_secondary_color' => $data['text_secondary_color'] ?? '#000000',
        ];

        $eventVariables = EventVariables::create($eventVariables);

        // Update Event with Event Variables
        Event::where('event_id', $event_id)->update([
            'event_variables_id' => $eventVariables->event_variables_id,
        ]);
    }
}
