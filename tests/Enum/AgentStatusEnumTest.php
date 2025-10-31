<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\HotelAgentBundle\Enum\AgentStatusEnum;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(AgentStatusEnum::class)]
final class AgentStatusEnumTest extends AbstractEnumTestCase
{
    public function testEnumIsEnum(): void
    {
        $reflection = new \ReflectionEnum(AgentStatusEnum::class);

        self::assertTrue($reflection->isEnum());
    }

    public function testEnumValues(): void
    {
        $this->assertSame('active', AgentStatusEnum::ACTIVE->value);
        $this->assertSame('frozen', AgentStatusEnum::FROZEN->value);
        $this->assertSame('disabled', AgentStatusEnum::DISABLED->value);
        $this->assertSame('expired', AgentStatusEnum::EXPIRED->value);
    }

    public function testGetLabelReturnsCorrectLabels(): void
    {
        $this->assertSame('激活', AgentStatusEnum::ACTIVE->getLabel());
        $this->assertSame('冻结', AgentStatusEnum::FROZEN->getLabel());
        $this->assertSame('禁用', AgentStatusEnum::DISABLED->getLabel());
        $this->assertSame('已过期', AgentStatusEnum::EXPIRED->getLabel());
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $result = AgentStatusEnum::ACTIVE->toArray();

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
    }

    public function testToSelectItemReturnsCorrectStructure(): void
    {
        $result = AgentStatusEnum::ACTIVE->toSelectItem();

        $this->assertCount(4, $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('name', $result);
    }
}
