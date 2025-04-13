<?php

namespace App\Filament\Admin\Resources\EventResource\Pages;

use Filament\Actions;
use App\Models\EventVariables;
use App\Models\TicketCategory;
use Filament\Facades\Filament;
use App\Models\TimelineSession;
use Illuminate\Support\Facades\DB;
use Mews\Purifier\Facades\Purifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Facades\FilamentView;
use App\Models\EventCategoryTimeboundPrice;
use App\Filament\Components\BackButtonAction;
use App\Filament\Admin\Resources\EventResource;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackButtonAction::make(
                Actions\Action::make('back')
            ),
            EventResource::ChangeStatusButton(
                Actions\Action::make('changeStatus')
            ),
            EventResource::EditSeatsButton(
                Actions\Action::make('editSeats')
            ),
            EventResource::ExportOrdersButton(
                Actions\Action::make('export')
            ),
            Actions\DeleteAction::make('Delete Event')
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getSaveFormAction(): Actions\Action
    {
        return parent::getSaveFormAction()
            ->label('Update Event')
            ->icon('heroicon-o-check-circle');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $eventId = $this->record->id;
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
            $eventId = $this->data['event_id'];

            // Initialize mappings
            $timelineIdMap = [];
            $existingTimelineIds = [];
            $existingCategoryIds = [];

            // 1. Handle Timeline Sessions
            $timelineSessions = [];
            foreach ($this->data['event_timeline'] ?? [] as $key => $session) {
                $sessionData = collect($session)->except(['created_at', 'updated_at'])->toArray();

                if (!empty($sessionData['id']) && !str_starts_with($sessionData['id'], 'record-')) {
                    // Update existing timeline
                    $timeline = TimelineSession::find($sessionData['id']);
                    if ($timeline) {
                        $timeline->fill($sessionData)->save();
                        $existingTimelineIds[] = $timeline->id;
                        $timelineSessions[$key] = array_merge($sessionData, ['id' => $timeline->id]);
                    }
                } else {
                    // Create new timeline
                    $sessionData['event_id'] = $eventId;
                    $newTimeline = TimelineSession::create($sessionData);
                    $timelineIdMap[$sessionData['id'] ?? $key] = $newTimeline->id;
                    $existingTimelineIds[] = $newTimeline->id;
                    $timelineSessions[$key] = array_merge($sessionData, ['id' => $newTimeline->id]);
                }
            }

            // 2. Handle Ticket Categories
            $ticketCategories = [];
            foreach ($this->data['ticket_categories'] ?? [] as $key => $category) {
                $categoryData = collect($category)->except(['created_at', 'updated_at', 'event_category_timebound_prices'])->toArray();

                $existingCategory = !empty($category['id'])
                    ? TicketCategory::where('event_id', $eventId)->where('id', $category['id'])->first()
                    : null;

                if ($existingCategory) {
                    // Update existing category
                    $existingCategory->fill($categoryData)->save();
                    $ticketCategories[$key] = array_merge($category, ['id' => $existingCategory->id]);
                    $existingCategoryIds[] = $existingCategory->id;
                } else {
                    // Create new category
                    $categoryData['event_id'] = $eventId;
                    $newCategory = TicketCategory::create($categoryData);
                    $ticketCategories[$key] = array_merge($category, ['id' => $newCategory->id]);
                    $existingCategoryIds[] = $newCategory->id;
                }
            }

            // 3. Handle Timebound Prices
            foreach ($ticketCategories as &$category) {
                if (empty($category['ticket_category_id'])) continue;

                foreach ($category['event_category_timebound_prices'] ?? [] as $priceIndex => $price) {
                    $priceData = collect($price)->except(['created_at', 'updated_at', 'title', 'name'])->toArray();
                    $priceData['ticket_category_id'] = $category['id'];

                    // Resolve timeline_id mapping
                    $timelineId = $priceData['timeline_id'] ?? null;
                    if (isset($timelineIdMap[$timelineId])) {
                        $priceData['timeline_id'] = $timelineIdMap[$timelineId];
                    }

                    if (empty($priceData['timeline_id']) || !in_array($priceData['timeline_id'], $existingTimelineIds)) {
                        continue;
                    }

                    $existingPrice = EventCategoryTimeboundPrice::where('ticket_category_id', $priceData['ticket_category_id'])
                        ->where('timeline_id', $priceData['timeline_id'])
                        ->first();

                    if ($existingPrice) {
                        // Update existing price
                        $existingPrice->fill($priceData)->save();
                        $category['event_category_timebound_prices'][$priceIndex]['id'] = $existingPrice->id;
                    } else {
                        // Create new price
                        $newPrice = EventCategoryTimeboundPrice::create($priceData);
                        $category['event_category_timebound_prices'][$priceIndex]['id'] = $newPrice->id;
                    }
                }
            }

            // 4. Cleanup unused data (delete only those removed from the form)
            TimelineSession::where('event_id', $eventId)
                ->whereNotIn('id', $existingTimelineIds)
                ->delete();

            TicketCategory::where('event_id', $eventId)
                ->whereNotIn('id', $existingCategoryIds)
                ->delete();

            // 5. Update EventVariables
            $eventVariables = EventVariables::firstOrCreate(
                ['event_id' => $eventId],
                EventVariables::getDefaultValue()
            );

            // Handle image fields
            foreach (['logo', 'texture', 'favicon'] as $imgField) {
                $this->data[$imgField] = !empty($this->data[$imgField])
                    ? array_values($this->data[$imgField])[0]
                    : '';
            }

            // Sanitize text fields
            $this->data['terms_and_conditions'] = Purifier::clean($this->data['terms_and_conditions'] ?? '');
            $this->data['privacy_policy'] = Purifier::clean($this->data['privacy_policy'] ?? '');

            // Encrypt sensitive fields
            foreach (['midtrans_client_key', 'midtrans_server_key', 'midtrans_client_key_sb', 'midtrans_server_key_sb'] as $keyField) {
                if (!empty($this->data[$keyField])) {
                    $this->data[$keyField] = Crypt::encryptString($this->data[$keyField]);
                }
            }

            // Fill and save EventVariables
            $eventVariables->fill($this->data);

            // Handle color cache
            $colors = Cache::get('color_preview_' . $user->id);
            if (!$colors) {
                throw new \Exception('Please retry setting the colors.');
            }
            $eventVariables->fill($colors);
            $eventVariables->save();

            // 6. Final Save
            $this->data['ticket_categories'] = $ticketCategories;
            $this->data['event_timeline'] = $timelineSessions;

            $this->form->fill($this->data);

            if ($this->record) {
                $this->record->fill($this->form->getState())->save();

                Cache::forget('color_preview_' . $user->id);

                Notification::make()->success()->title('Saved')->send();

                $redirectUrl = $this->getResource()::getUrl('view', ['record' => $eventId]);
                $navigate = FilamentView::hasSpaMode() && Filament::isAppUrl($redirectUrl);
                $this->redirect($redirectUrl, navigate: $navigate);

                DB::commit();
            } else {
                throw new \Exception('Record does not exist.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            Notification::make()
                ->title('Failed to Save')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->halt();
    }
}
