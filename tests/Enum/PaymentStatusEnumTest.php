<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\HotelAgentBundle\Enum\PaymentStatusEnum;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(PaymentStatusEnum::class)]
final class PaymentStatusEnumTest extends AbstractEnumTestCase
{
    public function testEnumIsEnum(): void
    {
        $reflection = new \ReflectionEnum(PaymentStatusEnum::class);

        self::assertTrue($reflection->isEnum());
    }

    public function testEnumValues(): void
    {
        $this->assertSame('pending', PaymentStatusEnum::PENDING->value);
        $this->assertSame('success', PaymentStatusEnum::SUCCESS->value);
        $this->assertSame('failed', PaymentStatusEnum::FAILED->value);
        $this->assertSame('refunded', PaymentStatusEnum::REFUNDED->value);
        $this->assertSame('cancelled', PaymentStatusEnum::CANCELLED->value);
    }

    public function testGetLabelReturnsCorrectLabels(): void
    {
        $this->assertSame('待支付', PaymentStatusEnum::PENDING->getLabel());
        $this->assertSame('支付成功', PaymentStatusEnum::SUCCESS->getLabel());
        $this->assertSame('支付失败', PaymentStatusEnum::FAILED->getLabel());
        $this->assertSame('已退款', PaymentStatusEnum::REFUNDED->getLabel());
        $this->assertSame('已取消', PaymentStatusEnum::CANCELLED->getLabel());
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $enum = PaymentStatusEnum::cases()[0];
        $result = $enum->toArray();

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
    }

    public function testToSelectItemReturnsCorrectStructure(): void
    {
        $enum = PaymentStatusEnum::cases()[0];
        $result = $enum->toSelectItem();

        $this->assertCount(4, $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('name', $result);
    }
}
