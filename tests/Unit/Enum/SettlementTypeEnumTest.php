<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\Enum\SettlementTypeEnum;

final class SettlementTypeEnumTest extends TestCase
{
    public function testEnumIsEnum(): void
    {
        $reflection = new \ReflectionEnum(SettlementTypeEnum::class);
        
        self::assertTrue($reflection->isEnum());
    }
}