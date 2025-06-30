<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\HotelAgentBundle;

final class HotelAgentBundleTest extends TestCase
{
    public function testBundle(): void
    {
        $bundle = new HotelAgentBundle();
        
        self::assertInstanceOf(HotelAgentBundle::class, $bundle);
    }
}