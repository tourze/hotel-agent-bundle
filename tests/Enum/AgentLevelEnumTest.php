<?php

namespace Tourze\HotelAgentBundle\Tests\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\Enum\AgentLevelEnum;

class AgentLevelEnumTest extends TestCase
{
    public function test_enum_values(): void
    {
        $this->assertSame('a', AgentLevelEnum::A->value);
        $this->assertSame('b', AgentLevelEnum::B->value);
        $this->assertSame('c', AgentLevelEnum::C->value);
    }

    public function test_getLabel_returns_correct_labels(): void
    {
        $this->assertSame('A级', AgentLevelEnum::A->getLabel());
        $this->assertSame('B级', AgentLevelEnum::B->getLabel());
        $this->assertSame('C级', AgentLevelEnum::C->getLabel());
    }

    public function test_cases_returns_all_enum_cases(): void
    {
        $cases = AgentLevelEnum::cases();
        
        $this->assertCount(3, $cases);
        $this->assertContains(AgentLevelEnum::A, $cases);
        $this->assertContains(AgentLevelEnum::B, $cases);
        $this->assertContains(AgentLevelEnum::C, $cases);
    }

    public function test_from_creates_enum_from_value(): void
    {
        $this->assertSame(AgentLevelEnum::A, AgentLevelEnum::from('a'));
        $this->assertSame(AgentLevelEnum::B, AgentLevelEnum::from('b'));
        $this->assertSame(AgentLevelEnum::C, AgentLevelEnum::from('c'));
    }

    public function test_from_throws_exception_for_invalid_value(): void
    {
        $this->expectException(\ValueError::class);
        
        AgentLevelEnum::from('invalid');
    }

    public function test_tryFrom_returns_null_for_invalid_value(): void
    {
        $this->assertNull(AgentLevelEnum::tryFrom('invalid'));
        $this->assertNull(AgentLevelEnum::tryFrom(''));
        $this->assertNull(AgentLevelEnum::tryFrom('d'));
    }

    public function test_tryFrom_returns_enum_for_valid_value(): void
    {
        $this->assertSame(AgentLevelEnum::A, AgentLevelEnum::tryFrom('a'));
        $this->assertSame(AgentLevelEnum::B, AgentLevelEnum::tryFrom('b'));
        $this->assertSame(AgentLevelEnum::C, AgentLevelEnum::tryFrom('c'));
    }

    public function test_implements_required_interfaces(): void
    {
        $enum = AgentLevelEnum::A;
        
        $this->assertInstanceOf(\Tourze\EnumExtra\Labelable::class, $enum);
        $this->assertInstanceOf(\Tourze\EnumExtra\Itemable::class, $enum);
        $this->assertInstanceOf(\Tourze\EnumExtra\Selectable::class, $enum);
    }

    public function test_getItems_returns_array_with_labels(): void
    {
        $items = AgentLevelEnum::getItems();
        
        $expected = [
            'a' => 'A级',
            'b' => 'B级',
            'c' => 'C级',
        ];
        
        $this->assertSame($expected, $items);
    }

    public function test_getSelectOptions_returns_array_for_select(): void
    {
        $options = AgentLevelEnum::getSelectOptions();
        
        $expected = [
            'A级' => 'a',
            'B级' => 'b',
            'C级' => 'c',
        ];
        
        $this->assertSame($expected, $options);
    }

    public function test_all_enum_cases_have_labels(): void
    {
        foreach (AgentLevelEnum::cases() as $case) {
            $label = $case->getLabel();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }

    public function test_enum_case_consistency(): void
    {
        $cases = AgentLevelEnum::cases();
        $values = array_map(fn($case) => $case->value, $cases);
        $labels = array_map(fn($case) => $case->getLabel(), $cases);
        
        // 确保没有重复的值
        $this->assertSame(array_unique($values), $values);
        
        // 确保没有重复的标签
        $this->assertSame(array_unique($labels), $labels);
        
        // 确保值和标签的数量一致
        $this->assertSame(count($values), count($labels));
    }
} 