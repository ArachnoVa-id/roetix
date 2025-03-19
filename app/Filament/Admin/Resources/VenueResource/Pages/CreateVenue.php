<?php

namespace App\Filament\Admin\Resources\VenueResource\Pages;

use Filament\Actions;
use App\Models\UserContact;
use Illuminate\Support\Str;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Admin\Resources\VenueResource;

class CreateVenue extends CreateRecord
{
    protected static string $resource = VenueResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
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
