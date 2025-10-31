<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\HotelAgentBundle\Enum\AgentLevelEnum;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(AgentLevelEnum::class)]
final class AgentLevelEnumTest extends AbstractEnumTestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame('a', AgentLevelEnum::A->value);
        $this->assertSame('b', AgentLevelEnum::B->value);
        $this->assertSame('c', AgentLevelEnum::C->value);
    }

    public function testGetLabelReturnsCorrectLabels(): void
    {
        $this->assertSame('A级', AgentLevelEnum::A->getLabel());
        $this->assertSame('B级', AgentLevelEnum::B->getLabel());
        $this->assertSame('C级', AgentLevelEnum::C->getLabel());
    }

    public function testCasesReturnsAllEnumCases(): void
    {
        $cases = AgentLevelEnum::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(AgentLevelEnum::A, $cases);
        $this->assertContains(AgentLevelEnum::B, $cases);
        $this->assertContains(AgentLevelEnum::C, $cases);
    }

    public function testFromCreatesEnumFromValue(): void
    {
        $this->assertSame(AgentLevelEnum::A, AgentLevelEnum::from('a'));
        $this->assertSame(AgentLevelEnum::B, AgentLevelEnum::from('b'));
        $this->assertSame(AgentLevelEnum::C, AgentLevelEnum::from('c'));
    }

    public function testFromThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);

        AgentLevelEnum::from('invalid');
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $this->assertSame(null, AgentLevelEnum::tryFrom('invalid'));
        $this->assertSame(null, AgentLevelEnum::tryFrom(''));
        $this->assertSame(null, AgentLevelEnum::tryFrom('d'));
    }

    public function testTryFromReturnsEnumForValidValue(): void
    {
        $this->assertSame(AgentLevelEnum::A, AgentLevelEnum::tryFrom('a'));
        $this->assertSame(AgentLevelEnum::B, AgentLevelEnum::tryFrom('b'));
        $this->assertSame(AgentLevelEnum::C, AgentLevelEnum::tryFrom('c'));
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $enum = AgentLevelEnum::A;

        // 验证接口方法正常工作
        $this->assertSame('A级', $enum->getLabel());
    }

    public function testItemsReturnsArrayWithLabels(): void
    {
        $items = [];
        foreach (AgentLevelEnum::cases() as $case) {
            $items[$case->value] = $case->getLabel();
        }

        $expected = [
            'a' => 'A级',
            'b' => 'B级',
            'c' => 'C级',
        ];

        $this->assertSame($expected, $items);
    }

    public function testAllEnumCasesHaveLabels(): void
    {
        foreach (AgentLevelEnum::cases() as $case) {
            $label = $case->getLabel();
            $this->assertNotEmpty($label);
        }
    }

    public function testEnumCaseConsistency(): void
    {
        $cases = AgentLevelEnum::cases();
        $values = array_map(fn ($case) => $case->value, $cases);
        $labels = array_map(fn ($case) => $case->getLabel(), $cases);

        // 确保没有重复的值
        $this->assertSame(array_unique($values), $values);

        // 确保没有重复的标签
        $this->assertSame(array_unique($labels), $labels);

        // 确保值和标签的数量一致
        $this->assertSame(count($values), count($labels));
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $expected = [
            'value' => 'a',
            'label' => 'A级',
        ];

        $this->assertSame($expected, AgentLevelEnum::A->toArray());
    }

    public function testToSelectItemReturnsCorrectStructure(): void
    {
        $expected = [
            'label' => 'A级',
            'text' => 'A级',
            'value' => 'a',
            'name' => 'A级',
        ];

        $this->assertSame($expected, AgentLevelEnum::A->toSelectItem());
    }

    public function testGenOptionsReturnsAllEnumItems(): void
    {
        $expected = [
            [
                'label' => 'A级',
                'text' => 'A级',
                'value' => 'a',
                'name' => 'A级',
            ],
            [
                'label' => 'B级',
                'text' => 'B级',
                'value' => 'b',
                'name' => 'B级',
            ],
            [
                'label' => 'C级',
                'text' => 'C级',
                'value' => 'c',
                'name' => 'C级',
            ],
        ];

        $this->assertSame($expected, AgentLevelEnum::genOptions());
    }
}
