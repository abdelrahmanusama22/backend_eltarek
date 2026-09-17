<?php

namespace App\Http\Controllers\Api;

use App\Domain\Booking\BookingStatus;
use App\Models\Booking;
use App\Models\Trim;
use App\Models\UserNotification;
use App\Services\SlotService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TestDriveController extends ApiController
{
    /** GET /test-drives/fleet */
    public function fleet(): JsonResponse
    {
        $fleet = Trim::with('vehicle')
            ->where('active', true)
            ->where('in_test_drive_fleet', true)
            ->orderBy('fleet_sort')
            ->get();

        return $this->ok($fleet->map(fn (Trim $t) => [
            'trim_id' => $t->id,
            'name' => $t->vehicle->model,
            'name_ar' => $t->vehicle->model_ar,
            'subtitle' => $t->subtitle,
            'image_url' => $t->vehicle->resolved_image_url,
        ]));
    }

    /** GET /test-drives/slots */
    public function slots(): JsonResponse
    {
        return $this->ok(SlotService::upcoming());
    }

    /** GET /test-drives?status=upcoming|past|all */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->bookings()->with(['trim.vehicle', 'branch']);

        match ($request->string('status')->toString()) {
            'upcoming' => $query->where('status', BookingStatus::Confirmed->value),
            'past' => $query->whereIn('status', [BookingStatus::Completed->value, BookingStatus::Cancelled->value]),
            default => null,
        };

        return $this->ok($query->get()->map->toApi());
    }

    /** POST /test-drives */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trim_id' => ['required', 'integer', Rule::exists('trims', 'id')->where(
                fn ($query) => $query->where('active', true)->where('in_test_drive_fleet', true)
            )],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('active', true)],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'day_label' => ['nullable', 'string', 'max:40'],
            'time' => ['required', 'string', 'max:20'],
        ]);

        $user = $request->user();

        $availableDay = collect(SlotService::upcoming())->firstWhere('date', $validated['date']);
        if (! $availableDay || ! in_array($validated['time'], $availableDay['times'], true)) {
            return $this->fail('The selected test-drive slot is not available.', 422, [
                'time' => ['Select one of the currently available slots.'],
            ]);
        }

        $slotKey = hash('sha256', implode('|', [
            $validated['trim_id'],
            $validated['branch_id'],
            $validated['date'],
            $validated['time'],
        ]));

        try {
            $booking = DB::transaction(function () use ($validated, $user, $slotKey) {
                // Same-slot double booking guard with lock
                $clash = Booking::where('status', BookingStatus::Confirmed->value)
                    ->where('branch_id', $validated['branch_id'])
                    ->where('time', $validated['time'])
                    ->whereDate('date', $validated['date'])
                    ->where('trim_id', $validated['trim_id'])
                    ->lockForUpdate()
                    ->exists();

                if ($clash) {
                    return null;
                }

                $dayLabel = $validated['day_label'] ?? '';
                $dayLabelAr = match ($dayLabel) {
                    'Today' => 'اليوم',
                    'Tomorrow' => 'غداً',
                    default => $dayLabel,
                };

                $ref = 'TD-'.now()->year.'-'.Str::upper((string) Str::ulid());

                $created = $user->bookings()->create([
                    'trim_id' => $validated['trim_id'],
                    'branch_id' => $validated['branch_id'],
                    'date' => $validated['date'] ?? null,
                    'day_label' => $dayLabel,
                    'day_label_ar' => $dayLabelAr,
                    'time' => $validated['time'],
                    'reference' => $ref,
                    'slot_key' => $slotKey,
                ]);

                UserNotification::create([
                    'user_id' => $user->id,
                    'type' => 'booking_reminder',
                    'title' => 'Test drive confirmed',
                    'title_ar' => 'تم تأكيد موعد تجربة القيادة',
                    'body' => "Reference {$created->reference} — our team will contact you shortly.",
                    'body_ar' => "رقم الحجز {$created->reference} — سيتواصل معك فريقنا في أقرب وقت.",
                ]);

                return $created;
            }, 3);
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return $this->fail('This time slot was just taken. Please pick another slot.', 409);
            }

            throw $exception;
        }

        if (! $booking) {
            return $this->fail('This time slot was just taken. Please pick another slot.', 409);
        }

        return $this->ok(
            $booking->load(['trim.vehicle', 'branch'])->toApi(),
            'Test drive confirmed! Our team will contact you shortly.',
            status: 201,
        );
    }

    /** DELETE /test-drives/{booking} */
    public function destroy(Request $request, $bookingId): JsonResponse
    {
        $booking = $request->user()->bookings()->findOrFail($bookingId);
        if ($booking->status !== BookingStatus::Confirmed->value) {
            return $this->fail('Booking not found or already cancelled.', 404);
        }

        $booking->update(['status' => BookingStatus::Cancelled->value, 'slot_key' => null]);
        UserNotification::create(['user_id'=>$booking->user_id,'type'=>'booking','title'=>'Booking cancelled','title_ar'=>'تم إلغاء الحجز','body'=>"Booking {$booking->reference} was cancelled.",'body_ar'=>"تم إلغاء الحجز {$booking->reference}."]);

        return $this->ok(null, 'Booking cancelled.');
    }
}
