<?php

namespace App\Filament\Admin\Resources\EventResource\Pages;

use App\Filament\Admin\Resources\EventResource;
use App\Models\EventCategoryTimeboundPrice;
use App\Models\EventVariables;
use App\Models\TicketCategory;
use App\Models\TimelineSession;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery\Matcher\Not;

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
            $params = EventVariables::getDefaultValue();
            $params['event_id'] = $eventId;
            $eventVariables = EventVariables::create($params);
        }

        return $data;
    }

    protected function beforeSave()
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        DB::beginTransaction();
        try {
            $ticketCategories = $this->data['ticket_categories'];
            $timelineSessions = $this->data['event_timeline'];

            $eventId = $this->data['event_id'];

            // Track valid timeline sessions
            $existingTimelineSessionIds = [];
            $existingCategoryIds = [];

            $timelineIdMap = []; // To map dummy keys to actual IDs

            // Process timelineSessions
            foreach ($timelineSessions as $key => $session) {
                unset($session['created_at'], $session['updated_at']);

                if (isset($session['timeline_id']) && strpos($session['timeline_id'], 'record-') !== 0) {
                    // Existing timeline session
                    $timelineSession = TimelineSession::find($session['timeline_id']);
                    if ($timelineSession) {
                        $existingTimelineSessionIds[] = $session['timeline_id'];
                        // Save changes
                        $timelineSession->fill($session);
                        $timelineSession->save();
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

            // Process ticketCategories
            foreach ($ticketCategories as $key => $category) {
                unset($category['event_category_timebound_prices'], $category['created_at'], $category['updated_at']);

                // Check if the category exists by name and event_id
                $existingCategory = TicketCategory::where('event_id', $eventId)
                    ->where('ticket_category_id', $category['ticket_category_id'])
                    ->first();

                if ($existingCategory) {
                    // Update existing category
                    $existingCategory->fill($category);
                    $existingCategory->save();
                    $ticketCategories[$key]['ticket_category_id'] = $existingCategory->ticket_category_id;
                    // Store real ID mapping
                    $existingCategoryIds[] = $existingCategory->ticket_category_id;
                } else {
                    // Create a new category only if it doesn't exist
                    $category['event_id'] = $eventId;
                    $newCategory = TicketCategory::create($category);
                    $ticketCategories[$key]['ticket_category_id'] = $newCategory->ticket_category_id;
                    // Store real ID mapping
                    $existingCategoryIds[] = $newCategory->ticket_category_id;
                }
            }

            // Process event_category_timebound_prices
            foreach ($ticketCategories as $key1 => $category) {
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

                    if ($existingPrice) {
                        // Update existing price
                        $existingPrice->fill($price);
                        $existingPrice->save();
                        $ticketCategories[$key1]['event_category_timebound_prices'][$key2]['timebound_price_id'] = $existingPrice->timebound_price_id;
                    } else {
                        // Create a new price
                        $newEventCategoryTimebound = EventCategoryTimeboundPrice::create($price);
                        $ticketCategories[$key1]['event_category_timebound_prices'][$key2]['timebound_price_id'] = $newEventCategoryTimebound->timebound_price_id;
                    }
                }
            }

            // Delete unused timeline
            TimelineSession::where('event_id', $eventId)
                ->whereNotIn('timeline_id', $existingTimelineSessionIds)
                ->delete();

            // Delete unused categories
            TicketCategory::where('event_id', $eventId)
                ->whereNotIn('ticket_category_id', $existingCategoryIds)
                ->delete();

            // Ensure updated data is reflected in the form
            $this->data['ticket_categories'] = $ticketCategories;
            $this->data['event_timeline'] = $timelineSessions;

            // Update all the event variables
            $eventVariables = EventVariables::where('event_id', $eventId)->first();

            $eventVariables->fill($this->data);
            $colors = Cache::get('color_preview_' . $user->id);
            if (!$colors) {
                throw new \Exception('Please retry setting the colors.');
            }

            $eventVariables->fill($colors);

            $eventVariables->save();

            // Sync the form state
            $this->form->fill($this->data);

            if ($this->record) {
                $this->record->fill($this->form->getState());
                $this->record->save();

                // Clear cache for colors
                Cache::forget('color_preview_' . Auth::user()->id);

                // Get the redirect URL (like getRedirectUrl)
                $redirectUrl = $this->getResource()::getUrl('view', ['record' => $eventId]);

                // Determine whether to use navigate (SPA mode)
                $navigate = FilamentView::hasSpaMode() && Filament::isAppUrl($redirectUrl);

                // Perform the redirect
                $this->redirect($redirectUrl, navigate: $navigate);

                Notification::make()
                    ->success()
                    ->title('Saved')
                    ->send();

                DB::commit();
            } else {
                Notification::make()
                    ->title('Failed to Save')
                    ->body('Unknown error occurred')
                    ->danger()
                    ->send();

                DB::rollBack();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to Save')
                ->body($e->getMessage())
                ->danger()
                ->send();

            DB::rollBack();
        }

        $this->halt();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Back')
                ->url(fn() => url()->previous())
                ->icon('heroicon-o-arrow-left')
                ->color('info'),
            EventResource::ChangeStatusButton(
                Actions\Action::make('changeStatus')
            ),
            EventResource::EditSeatsButton(
                Actions\Action::make('editSeats')
            )->button(),
            Actions\DeleteAction::make('Delete Event')
                ->icon('heroicon-o-trash'),
        ];
    }
}
