<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\Enum\AgentStatusEnum;

final class AgentStatusEnumTest extends TestCase
{
    public function testEnumIsEnum(): void
    {
        $reflection = new \ReflectionEnum(AgentStatusEnum::class);
        
        self::assertTrue($reflection->isEnum());
    }
}