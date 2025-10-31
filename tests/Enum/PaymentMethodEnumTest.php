<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\HotelAgentBundle\Enum\PaymentMethodEnum;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(PaymentMethodEnum::class)]
final class PaymentMethodEnumTest extends AbstractEnumTestCase
{
    public function testEnumIsEnum(): void
    {
        $reflection = new \ReflectionEnum(PaymentMethodEnum::class);

        self::assertTrue($reflection->isEnum());
    }

    public function testEnumValues(): void
    {
        $this->assertSame('bank_transfer', PaymentMethodEnum::BANK_TRANSFER->value);
        $this->assertSame('alipay', PaymentMethodEnum::ALIPAY->value);
        $this->assertSame('wechat', PaymentMethodEnum::WECHAT->value);
        $this->assertSame('cash', PaymentMethodEnum::CASH->value);
        $this->assertSame('check', PaymentMethodEnum::CHECK->value);
        $this->assertSame('credit_card', PaymentMethodEnum::CREDIT_CARD->value);
        $this->assertSame('online_banking', PaymentMethodEnum::ONLINE_BANKING->value);
        $this->assertSame('monthly_settlement', PaymentMethodEnum::MONTHLY_SETTLEMENT->value);
    }

    public function testGetLabelReturnsCorrectLabels(): void
    {
        $this->assertSame('银行转账', PaymentMethodEnum::BANK_TRANSFER->getLabel());
        $this->assertSame('支付宝', PaymentMethodEnum::ALIPAY->getLabel());
        $this->assertSame('微信支付', PaymentMethodEnum::WECHAT->getLabel());
        $this->assertSame('现金', PaymentMethodEnum::CASH->getLabel());
        $this->assertSame('支票', PaymentMethodEnum::CHECK->getLabel());
        $this->assertSame('信用卡', PaymentMethodEnum::CREDIT_CARD->getLabel());
        $this->assertSame('网银支付', PaymentMethodEnum::ONLINE_BANKING->getLabel());
        $this->assertSame('月结', PaymentMethodEnum::MONTHLY_SETTLEMENT->getLabel());
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $enum = PaymentMethodEnum::cases()[0];
        $result = $enum->toArray();

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
    }

    public function testToSelectItemReturnsCorrectStructure(): void
    {
        $enum = PaymentMethodEnum::cases()[0];
        $result = $enum->toSelectItem();

        $this->assertCount(4, $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('name', $result);
    }
}
