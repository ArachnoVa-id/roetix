<?php

namespace App\Filament\Admin\Resources\VenueResource\Pages;

use Filament\Actions;
use App\Models\UserContact;
use App\Models\Team;
use Illuminate\Support\Str;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Admin\Resources\VenueResource;
use Filament\Facades\Filament;

class CreateVenue extends CreateRecord
{
    protected static string $resource = VenueResource::class;

    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()->label('Create Venue');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant_id = Filament::getTenant()->team_id;
        $team = Team::where('team_id', $tenant_id)->first();

        if (!$team || $team->vendor_quota <= 0) {
            throw new \Exception('Venue Quota tidak mencukupi untuk membuat venue baru.');
        };

        $team->decrement('vendor_quota');

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

        return $data;
    }
}
