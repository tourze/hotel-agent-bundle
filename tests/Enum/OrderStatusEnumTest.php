<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\HotelAgentBundle\Enum\OrderStatusEnum;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(OrderStatusEnum::class)]
final class OrderStatusEnumTest extends AbstractEnumTestCase
{
    public function testEnumIsEnum(): void
    {
        $reflection = new \ReflectionEnum(OrderStatusEnum::class);

        self::assertTrue($reflection->isEnum());
    }

    public function testEnumValues(): void
    {
        $this->assertSame('pending', OrderStatusEnum::PENDING->value);
        $this->assertSame('confirmed', OrderStatusEnum::CONFIRMED->value);
        $this->assertSame('completed', OrderStatusEnum::COMPLETED->value);
        $this->assertSame('canceled', OrderStatusEnum::CANCELED->value);
        $this->assertSame('closed', OrderStatusEnum::CLOSED->value);
    }

    public function testGetLabelReturnsCorrectLabels(): void
    {
        $this->assertSame('待确认', OrderStatusEnum::PENDING->getLabel());
        $this->assertSame('已确认', OrderStatusEnum::CONFIRMED->getLabel());
        $this->assertSame('已完成', OrderStatusEnum::COMPLETED->getLabel());
        $this->assertSame('已取消', OrderStatusEnum::CANCELED->getLabel());
        $this->assertSame('已关闭', OrderStatusEnum::CLOSED->getLabel());
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $enum = OrderStatusEnum::cases()[0];
        $result = $enum->toArray();

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
    }

    public function testToSelectItemReturnsCorrectStructure(): void
    {
        $enum = OrderStatusEnum::cases()[0];
        $result = $enum->toSelectItem();

        $this->assertCount(4, $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('name', $result);
    }
}
