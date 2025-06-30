<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\Enum\OrderItemStatusEnum;

final class OrderItemStatusEnumTest extends TestCase
{
    public function testEnumIsEnum(): void
    {
        $reflection = new \ReflectionEnum(OrderItemStatusEnum::class);
        
        self::assertTrue($reflection->isEnum());
    }
}