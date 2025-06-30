<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\Enum\PaymentStatusEnum;

final class PaymentStatusEnumTest extends TestCase
{
    public function testEnumIsEnum(): void
    {
        $reflection = new \ReflectionEnum(PaymentStatusEnum::class);
        
        self::assertTrue($reflection->isEnum());
    }
}