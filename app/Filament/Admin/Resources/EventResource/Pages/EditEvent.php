<?php

namespace App\Filament\Admin\Resources\EventResource\Pages;

use App\Filament\Admin\Resources\EventResource;
use App\Models\EventCategoryTimeboundPrice;
use App\Models\EventVariables;
use App\Models\TicketCategory;
use App\Models\TimelineSession;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $eventId = $this->record->event_id;

        // Get event variable based on eventId
        $eventVariables = EventVariables::where('event_id', $eventId)->first();

        if ($eventVariables) {
            // apply all eventVariables data
            foreach ($eventVariables->toArray() as $key => $value) {
                $data[$key] = $value;
            }
        } else {
            // make new and assign
            $eventVariables = EventVariables::create([
                'event_id' => $eventId,
                // default values for all props
                'is_locked' => false,
                'locked_password' => '',

                'is_maintenance' => false,
                'maintenance_title' => '',
                'maintenance_message' => '',
                'maintenance_expected_finish' => now(),

                'logo' => '',
                'favicon' => '',
                'primary_color' => '#000000',
                'secondary_color' => '#000000',
                'text_primary_color' => '#000000',
                'text_secondary_color' => '#000000',
            ]);
        }

        return $data;
    }

    protected function beforeSave()
    {
        $ticketCategories = $this->data['ticket_categories'];
        $timelineSessions = $this->data['event_timeline'];

        $eventId = $this->data['event_id'];

        // Track valid timeline sessions
        $existingTimelineSessionIds = [];
        $timelineIdMap = []; // To map dummy keys to actual IDs

        // Process timelineSessions
        foreach ($timelineSessions as $key => $session) {
            unset($session['created_at'], $session['updated_at']);

            if (isset($session['timeline_id']) && strpos($session['timeline_id'], 'record-') !== 0) {
                // Existing timeline session
                $timelineSession = TimelineSession::find($session['timeline_id']);
                if ($timelineSession) {
                    $existingTimelineSessionIds[] = $session['timeline_id'];
                }
            } else {
                // Create a new timeline session
                $session['event_id'] = $eventId;
                $newTimelineSession = TimelineSession::create($session);

                // Store real ID mapping
                $timelineIdMap[$key] = $newTimelineSession->timeline_id;
                $existingTimelineSessionIds[] = $newTimelineSession->timeline_id;
                $timelineSessions[$key]['timeline_id'] = $newTimelineSession->timeline_id;
            }
        }

        // Timeline Sessions Manual Handling because Filament is Stupid on Handling it

        // Process ticketCategories
        foreach ($ticketCategories as $key => $category) {
            unset($category['event_category_timebound_prices'], $category['created_at'], $category['updated_at']);

            // Check if the category exists by name and event_id
            $existingCategory = TicketCategory::where('event_id', $eventId)
                ->where('ticket_category_id', $category['ticket_category_id']) // Adjust based on unique attributes
                ->first();

            if (!$existingCategory) {
                // Create a new category only if it doesn't exist
                $category['event_id'] = $eventId;
                $newCategory = TicketCategory::create($category);
                $ticketCategories[$key]['ticket_category_id'] = $newCategory->ticket_category_id;
            }
        }

        // Process event_category_timebound_prices
        foreach ($ticketCategories as $key1 => $category) {
            // Ensure ticket_category_id is available
            if (!isset($category['ticket_category_id']) || empty($category['ticket_category_id'])) {
                continue; // Skip this category if ID is missing
            }

            foreach ($category['event_category_timebound_prices'] as $key2 => $price) {
                unset($price['created_at'], $price['updated_at'], $price['title'], $price['name']);

                // Ensure ticket_category_id is explicitly set
                $price['ticket_category_id'] = $category['ticket_category_id'];

                // Ensure valid timeline_id
                if (!isset($price['timeline_id']) || !in_array($price['timeline_id'], $existingTimelineSessionIds)) {
                    if (isset($timelineIdMap[$price['timeline_id']])) {
                        $price['timeline_id'] = $timelineIdMap[$price['timeline_id']];
                    } else {
                        continue;
                    }
                }

                $existingPrice = EventCategoryTimeboundPrice::where('ticket_category_id', $price['ticket_category_id'])
                    ->where('timeline_id', $price['timeline_id'])
                    ->first();

                if (!$existingPrice) {
                    $newEventCategoryTimebound = EventCategoryTimeboundPrice::create($price);
                    $ticketCategories[$key1]['event_category_timebound_prices'][$key2]['timebound_price_id'] = $newEventCategoryTimebound->timebound_price_id;
                } else {
                    $ticketCategories[$key1]['event_category_timebound_prices'][$key2]['timebound_price_id'] = $existingPrice->timebound_price_id;
                }
            }
        }

        // Cut off $ticketCategories from $this->data
        unset($this->data['ticket_categories']);
    }

    protected function getHeaderActions(): array
    {
        return [
            EventResource::EditSeatsButton(
                Actions\Action::make('Edit Seats')
            )->button(),
            Actions\DeleteAction::make(),
        ];
    }
}
