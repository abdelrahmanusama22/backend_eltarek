<?php

namespace App\Domain\Pricing;

use InvalidArgumentException;

final readonly class Money
{
    public function __construct(public int $egp)
    {
        if ($egp < 0) {
            throw new InvalidArgumentException('Money cannot be negative.');
        }
    }

}
