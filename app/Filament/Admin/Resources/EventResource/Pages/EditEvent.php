<?php

namespace App\Filament\Admin\Resources\EventResource\Pages;

use App\Filament\Admin\Resources\EventResource;
use App\Models\EventCategoryTimeboundPrice;
use App\Models\TicketCategory;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Ambil event_id dari record yang sedang diedit
        $eventId = $this->record->event_id;

        // Ambil semua Ticket Categories yang terkait dengan event ini
        $ticketCategories = TicketCategory::where('event_id', $eventId)->get()->map(function ($category) {
            return [
                'ticket_category_id' => $category->ticket_category_id,
                'name' => $category->name,
                'color' => $category->color,
                'event_category_timebound_prices' => EventCategoryTimeboundPrice::where('ticket_category_id', $category->ticket_category_id)->get()->map(function ($price) {
                    return [
                        'timebound_price_id' => $price->timebound_price_id,
                        'start_date' => $price->start_date,
                        'end_date' => $price->end_date,
                        'price' => $price->price,
                    ];
                })->toArray(),
            ];
        })->toArray();

        // Masukkan data kategori tiket ke dalam form
        $data['ticket_categories'] = $ticketCategories;

        return $data;
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
