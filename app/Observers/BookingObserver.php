<?php

namespace App\Observers;

use App\Models\Booking;

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
        if ($booking->isDirty('status') && $booking->status === 'completed') {
            $rule = \App\Models\LoyaltyRule::where('action', 'test_drive')
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
