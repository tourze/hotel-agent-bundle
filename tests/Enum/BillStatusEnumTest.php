<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(BillStatusEnum::class)]
final class BillStatusEnumTest extends AbstractEnumTestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame('pending', BillStatusEnum::PENDING->value);
        $this->assertSame('confirmed', BillStatusEnum::CONFIRMED->value);
        $this->assertSame('paid', BillStatusEnum::PAID->value);
    }

    public function testGetLabelReturnsCorrectLabels(): void
    {
        $this->assertSame('待确认', BillStatusEnum::PENDING->getLabel());
        $this->assertSame('已确认', BillStatusEnum::CONFIRMED->getLabel());
        $this->assertSame('已支付', BillStatusEnum::PAID->getLabel());
    }

    public function testCasesReturnsAllEnumCases(): void
    {
        $cases = BillStatusEnum::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(BillStatusEnum::PENDING, $cases);
        $this->assertContains(BillStatusEnum::CONFIRMED, $cases);
        $this->assertContains(BillStatusEnum::PAID, $cases);
    }

    public function testFromCreatesEnumFromValue(): void
    {
        $this->assertSame(BillStatusEnum::PENDING, BillStatusEnum::from('pending'));
        $this->assertSame(BillStatusEnum::CONFIRMED, BillStatusEnum::from('confirmed'));
        $this->assertSame(BillStatusEnum::PAID, BillStatusEnum::from('paid'));
    }

    public function testFromThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        BillStatusEnum::from('invalid');
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $this->assertSame(null, BillStatusEnum::tryFrom('invalid'));
        $this->assertSame(null, BillStatusEnum::tryFrom(''));
        $this->assertSame(null, BillStatusEnum::tryFrom('unknown'));
    }

    public function testTryFromReturnsEnumForValidValue(): void
    {
        $this->assertSame(BillStatusEnum::PENDING, BillStatusEnum::tryFrom('pending'));
        $this->assertSame(BillStatusEnum::CONFIRMED, BillStatusEnum::tryFrom('confirmed'));
        $this->assertSame(BillStatusEnum::PAID, BillStatusEnum::tryFrom('paid'));
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $enum = BillStatusEnum::PENDING;

        // 验证接口方法正常工作
        $this->assertSame('待确认', $enum->getLabel());
    }

    public function testItemsReturnsArrayWithLabels(): void
    {
        $items = [];
        foreach (BillStatusEnum::cases() as $case) {
            $items[$case->value] = $case->getLabel();
        }
        $this->assertSame('待确认', $items['pending']);
        $this->assertSame('已确认', $items['confirmed']);
        $this->assertSame('已支付', $items['paid']);
    }

    public function testAllEnumCasesHaveLabels(): void
    {
        foreach (BillStatusEnum::cases() as $case) {
            $label = $case->getLabel();
            $this->assertNotEmpty($label);
        }
    }

    public function testEnumCaseConsistency(): void
    {
        $cases = BillStatusEnum::cases();
        $values = array_map(fn ($case) => $case->value, $cases);
        $uniqueValues = array_unique($values);

        $this->assertSame(count($cases), count($uniqueValues), '枚举值应该唯一');
    }

    public function testCaseInsensitiveValueMatching(): void
    {
        $this->assertSame(null, BillStatusEnum::tryFrom('PENDING'));
        $this->assertSame(null, BillStatusEnum::tryFrom('Pending'));
        $this->assertSame(null, BillStatusEnum::tryFrom('CONFIRMED'));
    }

    public function testEmptyAndNullValues(): void
    {
        $this->assertSame(null, BillStatusEnum::tryFrom(''));
        $this->assertSame(null, BillStatusEnum::tryFrom('0'));
        $this->assertSame(null, BillStatusEnum::tryFrom('false'));
    }

    public function testNumericValues(): void
    {
        $this->assertSame(null, BillStatusEnum::tryFrom('1'));
        $this->assertSame(null, BillStatusEnum::tryFrom('0'));
        $this->assertSame(null, BillStatusEnum::tryFrom('123'));
    }

    public function testSpecialCharacters(): void
    {
        $this->assertSame(null, BillStatusEnum::tryFrom(' pending '));
        $this->assertSame(null, BillStatusEnum::tryFrom('pending-'));
        $this->assertSame(null, BillStatusEnum::tryFrom('_pending'));
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $expected = [
            'value' => 'pending',
            'label' => '待确认',
        ];

        $this->assertSame($expected, BillStatusEnum::PENDING->toArray());
    }

    public function testToSelectItemReturnsCorrectStructure(): void
    {
        $expected = [
            'label' => '待确认',
            'text' => '待确认',
            'value' => 'pending',
            'name' => '待确认',
        ];

        $this->assertSame($expected, BillStatusEnum::PENDING->toSelectItem());
    }
}
