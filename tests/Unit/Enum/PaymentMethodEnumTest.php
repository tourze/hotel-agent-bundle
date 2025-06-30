<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\Enum\PaymentMethodEnum;

final class PaymentMethodEnumTest extends TestCase
{
    public function testEnumIsEnum(): void
    {
        $reflection = new \ReflectionEnum(PaymentMethodEnum::class);
        
        self::assertTrue($reflection->isEnum());
    }
}