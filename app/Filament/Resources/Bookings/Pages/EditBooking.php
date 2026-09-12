<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $currentStatus = $this->record->status;
        $nextStatus = $data['status'] ?? $currentStatus;

        if (in_array($currentStatus, ['completed', 'cancelled'], true) && $nextStatus !== $currentStatus) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data.status' => 'Completed or cancelled bookings cannot be reopened.',
            ]);
        }

        return [
            'status' => $nextStatus,
            'cancellation_reason' => $nextStatus === 'cancelled'
                ? ($data['cancellation_reason'] ?? null)
                : null,
            'admin_notes' => $data['admin_notes'] ?? null,
            'slot_key' => $nextStatus === 'cancelled' ? null : $this->record->slot_key,
        ];
    }
}
