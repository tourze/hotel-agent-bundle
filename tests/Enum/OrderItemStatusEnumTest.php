<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\HotelAgentBundle\Enum\OrderItemStatusEnum;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(OrderItemStatusEnum::class)]
final class OrderItemStatusEnumTest extends AbstractEnumTestCase
{
    public function testEnumIsEnum(): void
    {
        $reflection = new \ReflectionEnum(OrderItemStatusEnum::class);

        self::assertTrue($reflection->isEnum());
    }

    public function testEnumValues(): void
    {
        $this->assertSame('pending', OrderItemStatusEnum::PENDING->value);
        $this->assertSame('confirmed', OrderItemStatusEnum::CONFIRMED->value);
        $this->assertSame('canceled', OrderItemStatusEnum::CANCELED->value);
        $this->assertSame('completed', OrderItemStatusEnum::COMPLETED->value);
    }

    public function testGetLabelReturnsCorrectLabels(): void
    {
        $this->assertSame('待确认', OrderItemStatusEnum::PENDING->getLabel());
        $this->assertSame('已确认', OrderItemStatusEnum::CONFIRMED->getLabel());
        $this->assertSame('已取消', OrderItemStatusEnum::CANCELED->getLabel());
        $this->assertSame('已完成', OrderItemStatusEnum::COMPLETED->getLabel());
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $enum = OrderItemStatusEnum::cases()[0];
        $result = $enum->toArray();

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
    }

    public function testToSelectItemReturnsCorrectStructure(): void
    {
        $enum = OrderItemStatusEnum::cases()[0];
        $result = $enum->toSelectItem();

        $this->assertCount(4, $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('name', $result);
    }
}
