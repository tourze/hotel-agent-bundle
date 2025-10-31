<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\HotelAgentBundle\Enum\SettlementTypeEnum;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(SettlementTypeEnum::class)]
final class SettlementTypeEnumTest extends AbstractEnumTestCase
{
    public function testEnumIsEnum(): void
    {
        $reflection = new \ReflectionEnum(SettlementTypeEnum::class);

        self::assertTrue($reflection->isEnum());
    }

    public function testEnumValues(): void
    {
        $this->assertSame('monthly', SettlementTypeEnum::MONTHLY->value);
        $this->assertSame('half_monthly', SettlementTypeEnum::HALF_MONTHLY->value);
        $this->assertSame('weekly', SettlementTypeEnum::WEEKLY->value);
    }

    public function testGetLabelReturnsCorrectLabels(): void
    {
        $this->assertSame('月结', SettlementTypeEnum::MONTHLY->getLabel());
        $this->assertSame('半月结', SettlementTypeEnum::HALF_MONTHLY->getLabel());
        $this->assertSame('周结', SettlementTypeEnum::WEEKLY->getLabel());
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $enum = SettlementTypeEnum::cases()[0];
        $result = $enum->toArray();

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
    }

    public function testToSelectItemReturnsCorrectStructure(): void
    {
        $enum = SettlementTypeEnum::cases()[0];
        $result = $enum->toSelectItem();

        $this->assertCount(4, $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('name', $result);
    }
}
