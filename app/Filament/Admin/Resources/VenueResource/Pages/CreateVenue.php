<?php

namespace App\Filament\Admin\Resources\VenueResource\Pages;

use Filament\Actions;
use App\Models\UserContact;
use App\Models\Team;
use Illuminate\Support\Str;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Admin\Resources\VenueResource;
use App\Filament\Components\BackButtonAction;
use App\Http\Middleware\Filament\UrlHistoryStack;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Facades\DB;

class CreateVenue extends CreateRecord
{
    protected static string $resource = VenueResource::class;

    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Create Venue')
            ->icon('heroicon-o-plus');
    }

    protected function getCreateAnotherFormAction(): Actions\Action
    {
        return parent::getCreateAnotherFormAction()
            ->hidden()
            ->label('Create & Create Another Venue')
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            DB::beginTransaction();
            $tenant_id = Filament::getTenant()->team_id;
            $team = Team::where('team_id', $tenant_id)->lockForUpdate()->first();

            if (!$team || $team->vendor_quota <= 0) {
                throw new \Exception('Venue Quota tidak mencukupi untuk membuat venue baru.');
            };

            // Create new UserContact
            $contactInfo = $this->data['contactInfo'];
            $contact = UserContact::create([
                'contact_id' => Str::uuid(),
                'phone_number' => $contactInfo['phone_number'],
                'email' => $contactInfo['email'],
                'whatsapp_number' => $contactInfo['whatsapp_number'] ?? null,
                'instagram' => $contactInfo['instagram'] ?? null,
            ]);

            // Assign the new contact to the venue data
            $data['contact_info'] = $contact->contact_id;

            $team->decrement('vendor_quota');
            if ($team->vendor_quota <= 0) {
                UrlHistoryStack::popUrlStack();
            }

            DB::commit();
            return $data;
        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->show();

            $this->halt();
            return $data;
        }
    }
}
