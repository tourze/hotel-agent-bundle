<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\HotelAgentBundle\Enum\OrderSourceEnum;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(OrderSourceEnum::class)]
final class OrderSourceEnumTest extends AbstractEnumTestCase
{
    public function testEnumIsEnum(): void
    {
        $reflection = new \ReflectionEnum(OrderSourceEnum::class);

        self::assertTrue($reflection->isEnum());
    }

    public function testEnumValues(): void
    {
        $this->assertSame('excel_import', OrderSourceEnum::EXCEL_IMPORT->value);
        $this->assertSame('manual_input', OrderSourceEnum::MANUAL_INPUT->value);
    }

    public function testGetLabelReturnsCorrectLabels(): void
    {
        $this->assertSame('Excel导入', OrderSourceEnum::EXCEL_IMPORT->getLabel());
        $this->assertSame('后台录入', OrderSourceEnum::MANUAL_INPUT->getLabel());
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $enum = OrderSourceEnum::cases()[0];
        $result = $enum->toArray();

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
    }

    public function testToSelectItemReturnsCorrectStructure(): void
    {
        $enum = OrderSourceEnum::cases()[0];
        $result = $enum->toSelectItem();

        $this->assertCount(4, $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('name', $result);
    }
}
