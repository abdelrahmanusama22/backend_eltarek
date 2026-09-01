<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\Trim;
use App\Models\UserNotification;
use App\Services\SlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            'upcoming' => $query->where('status', 'confirmed'),
            'past' => $query->whereIn('status', ['completed', 'cancelled']),
            default => null,
        };

        return $this->ok($query->get()->map->toApi());
    }

    /** POST /test-drives */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trim_id' => ['required', 'integer', 'exists:trims,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'date' => ['nullable', 'date', 'after_or_equal:today'],
            'day_label' => ['nullable', 'string', 'max:40'],
            'time' => ['required', 'string', 'max:20'],
        ]);

        $user = $request->user();

        // Same-slot double booking guard.
        $clash = Booking::where('status', 'confirmed')
            ->where('branch_id', $validated['branch_id'])
            ->where('time', $validated['time'])
            ->when(isset($validated['date']), fn ($q) => $q->whereDate('date', $validated['date']))
            ->where('trim_id', $validated['trim_id'])
            ->exists();
        if ($clash) {
            return $this->fail('This time slot was just taken. Please pick another slot.', 409);
        }

        $booking = $user->bookings()->create([
            'trim_id' => $validated['trim_id'],
            'branch_id' => $validated['branch_id'],
            'date' => $validated['date'] ?? null,
            'day_label' => $validated['day_label'] ?? '',
            'day_label_ar' => '',
            'time' => $validated['time'],
            'reference' => 'TD-'.now()->year.'-'.Str::upper(Str::random(4)),
        ]);

        UserNotification::create([
            'user_id' => $user->id,
            'type' => 'booking_reminder',
            'title' => 'Test drive confirmed',
            'title_ar' => '???? ?????????? ?????????? ??????????????',
            'body' => "Reference {$booking->reference} ??? our team will contact you shortly.",
            'body_ar' => "?????? ?????????? {$booking->reference} ??? ?????????????? ?????? ???????????? ????????????.",
        ]);

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
        if ($booking->status !== 'confirmed') {
            return $this->fail('Booking not found or already cancelled.', 404);
        }

        $booking->update(['status' => 'cancelled']);

        return $this->ok(null, 'Booking cancelled.');
    }
}
