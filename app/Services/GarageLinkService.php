<?php

namespace App\Services;

use App\Models\GarageCar;
use App\Models\GarageLinkRequest;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GarageLinkService
{
    public function approve(GarageLinkRequest $request, int $reviewerId, array $carData): GarageCar
    {
        return DB::transaction(function () use ($request, $reviewerId, $carData) {
            $request = GarageLinkRequest::lockForUpdate()->findOrFail($request->id);
            if ($request->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'This request has already been reviewed.']);
            }

            $owned = GarageCar::where('tracking_code', $request->identifier)->lockForUpdate()->first();
            if ($owned && $owned->user_id !== $request->user_id) {
                throw ValidationException::withMessages(['identifier' => 'This vehicle is already linked to another customer.']);
            }

            $car = GarageCar::updateOrCreate(
                ['tracking_code' => $request->identifier],
                array_merge($carData, ['user_id' => $request->user_id]),
            );
            $request->update(['status' => 'approved', 'reviewed_by' => $reviewerId, 'reviewed_at' => now()]);
            UserNotification::create([
                'user_id' => $request->user_id, 'type' => 'garage',
                'title' => 'Vehicle added to your garage', 'title_ar' => 'تمت إضافة السيارة إلى كراجك',
                'body' => "{$car->name} is now linked to your account.",
                'body_ar' => "تم ربط سيارة {$car->name} بحسابك بنجاح.",
            ]);
            return $car;
        }, 3);
    }

    public function reject(GarageLinkRequest $request, int $reviewerId, string $notes): void
    {
        DB::transaction(function () use ($request, $reviewerId, $notes) {
            $request = GarageLinkRequest::lockForUpdate()->findOrFail($request->id);
            if ($request->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'This request has already been reviewed.']);
            }
            $request->update(['status' => 'rejected', 'admin_notes' => $notes, 'reviewed_by' => $reviewerId, 'reviewed_at' => now()]);
            UserNotification::create([
                'user_id' => $request->user_id, 'type' => 'garage',
                'title' => 'Vehicle link request needs attention', 'title_ar' => 'طلب ربط السيارة يحتاج إلى مراجعة',
                'body' => $notes, 'body_ar' => $notes,
            ]);
        });
    }
}
