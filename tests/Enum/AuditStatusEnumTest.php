<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\HotelAgentBundle\Enum\AuditStatusEnum;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(AuditStatusEnum::class)]
final class AuditStatusEnumTest extends AbstractEnumTestCase
{
    public function testEnumIsEnum(): void
    {
        $reflection = new \ReflectionEnum(AuditStatusEnum::class);

        self::assertTrue($reflection->isEnum());
    }

    public function testEnumValues(): void
    {
        $this->assertSame('pending', AuditStatusEnum::PENDING->value);
        $this->assertSame('approved', AuditStatusEnum::APPROVED->value);
        $this->assertSame('risk_review', AuditStatusEnum::RISK_REVIEW->value);
        $this->assertSame('rejected', AuditStatusEnum::REJECTED->value);
        $this->assertSame('completed', AuditStatusEnum::COMPLETED->value);
    }

    public function testGetLabelReturnsCorrectLabels(): void
    {
        $this->assertSame('待审核', AuditStatusEnum::PENDING->getLabel());
        $this->assertSame('通过', AuditStatusEnum::APPROVED->getLabel());
        $this->assertSame('风险审核', AuditStatusEnum::RISK_REVIEW->getLabel());
        $this->assertSame('拒绝', AuditStatusEnum::REJECTED->getLabel());
        $this->assertSame('已完成', AuditStatusEnum::COMPLETED->getLabel());
    }

    public function testGetSelectOptionsReturnsCorrectStructure(): void
    {
        $result = AuditStatusEnum::getSelectOptions();

        $this->assertCount(5, $result);

        $expected = [
            '待审核' => 'pending',
            '通过' => 'approved',
            '风险审核' => 'risk_review',
            '拒绝' => 'rejected',
            '已完成' => 'completed',
        ];

        $this->assertSame($expected, $result);
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $enum = AuditStatusEnum::cases()[0];
        $result = $enum->toArray();

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
    }

    public function testToSelectItemReturnsCorrectStructure(): void
    {
        $enum = AuditStatusEnum::cases()[0];
        $result = $enum->toSelectItem();

        $this->assertCount(4, $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('name', $result);
    }
}
