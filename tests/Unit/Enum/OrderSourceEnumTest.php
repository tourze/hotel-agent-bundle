<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\Enum\OrderSourceEnum;

final class OrderSourceEnumTest extends TestCase
{
    public function testEnumIsEnum(): void
    {
        $reflection = new \ReflectionEnum(OrderSourceEnum::class);
        
        self::assertTrue($reflection->isEnum());
    }
}