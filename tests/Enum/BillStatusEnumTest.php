<?php

namespace Tourze\HotelAgentBundle\Tests\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;

class BillStatusEnumTest extends TestCase
{
    public function test_enum_values(): void
    {
        $this->assertSame('pending', BillStatusEnum::PENDING->value);
        $this->assertSame('confirmed', BillStatusEnum::CONFIRMED->value);
        $this->assertSame('paid', BillStatusEnum::PAID->value);
    }

    public function test_getLabel_returns_correct_labels(): void
    {
        $this->assertSame('待确认', BillStatusEnum::PENDING->getLabel());
        $this->assertSame('已确认', BillStatusEnum::CONFIRMED->getLabel());
        $this->assertSame('已支付', BillStatusEnum::PAID->getLabel());
    }

    public function test_cases_returns_all_enum_cases(): void
    {
        $cases = BillStatusEnum::cases();
        
        $this->assertCount(3, $cases);
        $this->assertContains(BillStatusEnum::PENDING, $cases);
        $this->assertContains(BillStatusEnum::CONFIRMED, $cases);
        $this->assertContains(BillStatusEnum::PAID, $cases);
    }

    public function test_from_creates_enum_from_value(): void
    {
        $this->assertSame(BillStatusEnum::PENDING, BillStatusEnum::from('pending'));
        $this->assertSame(BillStatusEnum::CONFIRMED, BillStatusEnum::from('confirmed'));
        $this->assertSame(BillStatusEnum::PAID, BillStatusEnum::from('paid'));
    }

    public function test_from_throws_exception_for_invalid_value(): void
    {
        $this->expectException(\ValueError::class);
        BillStatusEnum::from('invalid');
    }

    public function test_tryFrom_returns_null_for_invalid_value(): void
    {
        $this->assertNull(BillStatusEnum::tryFrom('invalid'));
        $this->assertNull(BillStatusEnum::tryFrom(''));
        $this->assertNull(BillStatusEnum::tryFrom('unknown'));
    }

    public function test_tryFrom_returns_enum_for_valid_value(): void
    {
        $this->assertSame(BillStatusEnum::PENDING, BillStatusEnum::tryFrom('pending'));
        $this->assertSame(BillStatusEnum::CONFIRMED, BillStatusEnum::tryFrom('confirmed'));
        $this->assertSame(BillStatusEnum::PAID, BillStatusEnum::tryFrom('paid'));
    }

    public function test_implements_required_interfaces(): void
    {
        $this->assertInstanceOf(\Tourze\EnumExtra\Labelable::class, BillStatusEnum::PENDING);
        $this->assertInstanceOf(\Tourze\EnumExtra\Itemable::class, BillStatusEnum::PENDING);
        $this->assertInstanceOf(\Tourze\EnumExtra\Selectable::class, BillStatusEnum::PENDING);
    }

    public function test_getItems_returns_array_with_labels(): void
    {
        $items = BillStatusEnum::getItems();
        
        $this->assertIsArray($items);
        $this->assertSame('待确认', $items['pending']);
        $this->assertSame('已确认', $items['confirmed']);
        $this->assertSame('已支付', $items['paid']);
    }

    public function test_getOptions_returns_array_for_select(): void
    {
        $options = BillStatusEnum::getOptions();
        
        $this->assertIsArray($options);
        $this->assertSame('pending', $options['待确认']);
        $this->assertSame('confirmed', $options['已确认']);
        $this->assertSame('paid', $options['已支付']);
    }

    public function test_all_enum_cases_have_labels(): void
    {
        foreach (BillStatusEnum::cases() as $case) {
            $label = $case->getLabel();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }

    public function test_enum_case_consistency(): void
    {
        $cases = BillStatusEnum::cases();
        $values = array_map(fn($case) => $case->value, $cases);
        $uniqueValues = array_unique($values);
        
        $this->assertSame(count($cases), count($uniqueValues), '枚举值应该唯一');
    }

    public function test_case_insensitive_value_matching(): void
    {
        $this->assertNull(BillStatusEnum::tryFrom('PENDING'));
        $this->assertNull(BillStatusEnum::tryFrom('Pending'));
        $this->assertNull(BillStatusEnum::tryFrom('CONFIRMED'));
    }

    public function test_empty_and_null_values(): void
    {
        $this->assertNull(BillStatusEnum::tryFrom(''));
        $this->assertNull(BillStatusEnum::tryFrom('0'));
        $this->assertNull(BillStatusEnum::tryFrom('false'));
    }

    public function test_numeric_values(): void
    {
        $this->assertNull(BillStatusEnum::tryFrom('1'));
        $this->assertNull(BillStatusEnum::tryFrom('0'));
        $this->assertNull(BillStatusEnum::tryFrom('123'));
    }

    public function test_special_characters(): void
    {
        $this->assertNull(BillStatusEnum::tryFrom(' pending '));
        $this->assertNull(BillStatusEnum::tryFrom('pending-'));
        $this->assertNull(BillStatusEnum::tryFrom('_pending'));
    }
} 