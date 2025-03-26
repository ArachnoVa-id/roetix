<?php

namespace App\Filament\Admin\Resources\EventResource\Pages;

use App\Filament\Admin\Resources\EventResource;
use Filament\Resources\Pages\CreateRecord;

use App\Models\TicketCategory;
use App\Models\Team;
use App\Models\EventCategoryTimeboundPrice;
use App\Models\EventVariables;
use App\Models\TimelineSession;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;


class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant_id = Filament::getTenant()->team_id;
        $team = Team::where('team_id', $tenant_id)->first();

        if (!$team || $team->vendor_quota <= 0) {
            throw new \Exception('Vendor quota tidak mencukupi untuk membuat venue baru.');
        };

        $team->decrement('event_quota');
        $tenant = Filament::getTenant();
        $data['team_id'] = $tenant->team_id;
        $data['team_code'] = $tenant->code;

        $this->data['temp_data'] = [
            'event_timeline' => $this->data['event_timeline'] ?? [],
            'ticket_categories' => $this->data['ticket_categories'] ?? [],
        ];

        // Remove them from the main insert
        unset($this->data['event_timeline'], $this->data['ticket_categories']);

        return $data;
    }

    public function afterCreate()
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $data = $this->data;
        $event_id = $this->record->event_id;

        // Create Timeline
        $ticketTimelines = $data['temp_data']['event_timeline'] ?? [];
        $timelineFormXDb = [];

        if (!empty($ticketTimelines)) {
            foreach ($ticketTimelines as $key => $timeline) {
                $db_timeline = TimelineSession::create([
                    'event_id' => $event_id,
                    'name' => $timeline['name'],
                    'start_date' => $timeline['start_date'],
                    'end_date' => $timeline['end_date'],
                ]);

                $timelineFormXDb[$key] = $db_timeline->timeline_id;
            }
        }

        // Create Ticket Categories
        $ticketCategories = $data['temp_data']['ticket_categories'] ?? [];
        if (!empty($ticketCategories)) {
            foreach ($ticketCategories as $category) {
                // Create Ticket Category
                $ticketCategory = TicketCategory::create([
                    'event_id' => $event_id,
                    'name' => $category['name'],
                    'color' => $category['color'],
                ]);

                $ticketCategories[$category['name']] = $ticketCategory->ticket_category_id;

                if (!empty($category['event_category_timebound_prices'])) {
                    foreach ($category['event_category_timebound_prices'] as $timeboundPrice) {
                        EventCategoryTimeboundPrice::create([
                            'ticket_category_id' => $ticketCategory->ticket_category_id,
                            'timeline_id' => $timelineFormXDb[$timeboundPrice['timeline_id']],
                            'price' => $timeboundPrice['price'],
                            'is_active' => $timeboundPrice['is_active'],
                        ]);
                    }
                }
            }
        }

        // Create Event Variables
        $colors = Cache::get('color_preview_' . $user->user_id);

        $eventVariables = [
            'event_id' => $event_id,

            'is_locked' => $data['is_locked'] ? (int) $data['is_locked'] : 0,
            'locked_password' => $data['locked_password'] ?? '',

            'is_maintenance' => $data['is_maintenance'] ? (int) $data['is_locked'] : 0,
            'maintenance_title' => $data['maintenance_title'] ?? '',
            'maintenance_message' => $data['maintenance_message'] ?? '',
            'maintenance_expected_finish' => !empty($data['maintenance_expected_finish'])
                ? $data['maintenance_expected_finish']
                : now(), // Set to current timestamp if empty

            'logo' => $data['logo'] ?? '',
            'logo_alt' => $data['logo_alt'] ?? '',
            'favicon' => $data['favicon'] ?? '',
        ];

        $eventVariables = array_merge($eventVariables, $colors);

        EventVariables::create($eventVariables);

        // Clear cache for colors
        Cache::forget('color_preview_' . Auth::user()->user_id);
    }
}
