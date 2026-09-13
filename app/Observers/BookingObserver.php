<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\LoyaltyRule;
use App\Models\UserNotification;

class BookingObserver
{
    /**
     * Handle the Booking "created" event.
     */
    public function created(Booking $booking): void
    {
        //
    }

    public function updated(Booking $booking): void
    {
        if ($booking->isDirty('status') && $booking->status === 'cancelled') {
            $reason = $booking->cancellation_reason ?: 'Cancelled by our team.';
            UserNotification::create([
                'user_id'=>$booking->user_id,
                'type'=>'booking',
                'title'=>'Booking cancelled',
                'title_ar'=>'تم إلغاء الحجز',
                'body'=>"Booking {$booking->reference} was cancelled. Reason: {$reason}",
                'body_ar'=>"تم إلغاء الحجز {$booking->reference}. السبب: {$reason}",
            ]);
        }

        if ($booking->isDirty('status') && $booking->status === 'completed' && ! $booking->points_awarded_at) {
            $rule = LoyaltyRule::where('action', 'test_drive')
                ->where('is_active', true)
                ->first();

            if ($rule && $booking->user) {
                // Check if we haven't already awarded points for this booking?
                // For simplicity, we just grant it, but to prevent duplicate points we can add a flag or check transactions.
                // Assuming status goes to 'completed' once, it's fine.
                $booking->user->addPoints(
                    $rule->points_awarded,
                    "Reward for completing Test Drive #{$booking->id} (Rule: {$rule->name})",
                    'credit'
                );
                $booking->updateQuietly(['points_awarded_at' => now()]);
                UserNotification::create(['user_id'=>$booking->user_id,'type'=>'reward','title'=>'Points earned','title_ar'=>'حصلت على نقاط','body'=>"You earned {$rule->points_awarded} points for completing your test drive.",'body_ar'=>"حصلت على {$rule->points_awarded} نقطة بعد إكمال تجربة القيادة."]);
            }
        }
    }

    /**
     * Handle the Booking "deleted" event.
     */
    public function deleted(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "restored" event.
     */
    public function restored(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "force deleted" event.
     */
    public function forceDeleted(Booking $booking): void
    {
        //
    }
}
