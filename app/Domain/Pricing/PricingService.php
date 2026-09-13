<?php

namespace App\Domain\Pricing;

use App\Models\Trim;

final class PricingService
{
    public static function executivePrice(Trim $trim): Money
    {
        $official = new Money(max(0, (int) $trim->price_egp));
        $markup = (float) ($trim->markup_percentage ?? 5);

        return new Money(max(0, (int) round($official->egp * (1 + $markup / 100))));
    }
}
