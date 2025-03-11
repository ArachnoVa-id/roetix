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
        $contact = UserContact::create([
            'contact_id' => Str::uuid(),
            'phone_number' => $data['phone_number'],
            'email' => $data['email'],
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
            'instagram' => $data['instagram'] ?? null,
        ]);

        // Assign the new contact to the venue data
        $data['contact_info'] = $contact->contact_id;

        return $data;
    }
}
