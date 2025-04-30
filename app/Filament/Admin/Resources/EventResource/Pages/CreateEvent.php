<?php

namespace App\Filament\Admin\Resources\EventResource\Pages;

use App\Models\Team;
use Filament\Actions;
use App\Models\EventVariables;
use App\Models\TicketCategory;
use Filament\Facades\Filament;
use App\Models\TimelineSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Facades\FilamentView;
use App\Models\EventCategoryTimeboundPrice;
use App\Filament\Admin\Resources\EventResource;
use App\Filament\Components\BackButtonAction;
use App\Http\Middleware\Filament\UrlHistoryStack;
use App\Models\Event;
use Filament\Support\Enums\IconPosition;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Create Event')
            ->icon('heroicon-o-plus');
    }

    protected function getCreateAnotherFormAction(): Actions\Action
    {
        return parent::getCreateAnotherFormAction()
            ->hidden()
            ->label('Create & Create Another Event')
            ->icon('heroicon-o-plus');
    }

    protected function getCancelFormAction(): Actions\Action
    {
        return parent::getCancelFormAction()->hidden();
    }

    protected function getHeaderActions(): array
    {
        return [
            BackButtonAction::make(
                Actions\Action::make('back')
            )
                ->label('Cancel')
                ->icon('heroicon-o-x-circle')
                ->iconPosition(IconPosition::After)
        ];
    }

    public function beforeCreate()
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            if (!$user) {
                throw new \Exception('User not found.');
            }

            $tenant = Filament::getTenant();
            $team = Team::where('id', $tenant->id)->lockForUpdate()->first();

            if (!$team || $team->event_quota <= 0) {
                throw new \Exception('Event Quota tidak mencukupi untuk membuat venue baru.');
            };

            $data = $this->data;

            $data['team_id'] = $tenant->id;
            $data['team_code'] = $tenant->code;

            // Create the Event with existing data
            $event = Event::create($data);
            $event_id = $event->id;

            // Create Timeline
            $ticketTimelines = $data['event_timeline'] ?? [];
            $timelineFormXDb = [];

            if (!empty($ticketTimelines)) {
                foreach ($ticketTimelines as $key => $timeline) {
                    $db_timeline = TimelineSession::create([
                        'event_id' => $event_id,
                        'name' => $timeline['name'],
                        'start_date' => $timeline['start_date'],
                        'end_date' => $timeline['end_date'],
                    ]);

                    $timelineFormXDb[$key] = $db_timeline->id;
                }
            }

            // Create Ticket Categories
            $ticketCategories = $data['ticket_categories'] ?? [];
            if (!empty($ticketCategories)) {
                foreach ($ticketCategories as $idx => $category) {
                    // Create Ticket Category
                    $ticketCategory = TicketCategory::create([
                        'event_id' => $event_id,
                        'name' => $category['name'],
                        'color' => $category['color'],
                    ]);

                    $ticketCategories[$idx]['ticket_category_id'] = $ticketCategory->id;

                    if (!empty($category['event_category_timebound_prices'])) {
                        foreach ($category['event_category_timebound_prices'] as $idx2 => $timeboundPrice) {
                            $timeboundPrice = EventCategoryTimeboundPrice::create([
                                'ticket_category_id' => $ticketCategory->id,
                                'timeline_id' => $timelineFormXDb[$timeboundPrice['timeline_id']],
                                'price' => $timeboundPrice['price'],
                                'is_active' => $timeboundPrice['is_active'],
                            ]);

                            $ticketCategories[$idx]['event_category_timebound_prices'][$idx2]['timebound_price_id'] = $timeboundPrice->id;
                        }
                    }
                }
            }

            // Create Event Variables
            $colors = Cache::get('color_preview_' . $user->id);

            // If no color, stop the request and tell the user to retry
            if (!$colors) {
                throw new \Exception('Colors expired! Please retry readjusting the colors.');
            }

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

            // Parse all the image based to get values only (because it is in array)
            $columns = ['logo', 'texture', 'favicon'];
            foreach ($columns as $column) {
                if (isset($eventVariables[$column])) {
                    if (!empty($eventVariables[$column]))
                        $eventVariables[$column] = array_values($eventVariables[$column])[0];
                    else $eventVariables[$column] = "";
                }
            }

            $eventVariables = array_merge($eventVariables, $colors);

            $newEventVariables = EventVariables::create($eventVariables);

            $team->decrement('event_quota');

            if ($team->event_quota <= 0) {
                UrlHistoryStack::popUrlStack();
            }

            DB::commit();

            // Notify success
            Notification::make()
                ->success()
                ->title('Saved')
                ->body("Event {$event->name} has been created.")
                ->send();

            // Clear cache for colors
            Cache::forget('color_preview_' . Auth::id());

            // Get the redirect URL (like getRedirectUrl)
            $redirectUrl = $this->getResource()::getUrl('view', ['record' => $event_id]);

            // Determine whether to use navigate (SPA mode)
            $navigate = FilamentView::hasSpaMode() && Filament::isAppUrl($redirectUrl);

            // Perform the redirect
            $this->redirect($redirectUrl, navigate: $navigate);
        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Error')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
        $this->halt();
    }
}
