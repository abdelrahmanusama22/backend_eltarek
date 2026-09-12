<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SmokeTest extends TestCase
{
    public function test_php_runtime_is_supported(): void
    {
        $this->assertTrue(version_compare(PHP_VERSION, '8.3.0', '>='));
    }
}
