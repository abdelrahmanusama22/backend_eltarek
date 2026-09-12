<?php

namespace Tests\Unit;

use App\Domain\Pricing\PricingService;
use App\Models\Trim;
use PHPUnit\Framework\TestCase;

class PricingServiceTest extends TestCase
{
    public function test_markup_and_discount_are_calculated_without_negative_money(): void
    {
        $markup = new Trim(['price_egp' => 1_000_000, 'markup_percentage' => 5]);
        $discount = new Trim(['price_egp' => 1_000_000, 'markup_percentage' => -4.5]);

        $this->assertSame(1_050_000, PricingService::executivePrice($markup)->egp);
        $this->assertSame(955_000, PricingService::executivePrice($discount)->egp);
    }
}
