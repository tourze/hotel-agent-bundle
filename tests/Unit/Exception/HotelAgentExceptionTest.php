<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\Exception\HotelAgentException;

final class HotelAgentExceptionTest extends TestCase
{
    public function testExceptionIsAbstract(): void
    {
        $reflection = new \ReflectionClass(HotelAgentException::class);
        
        self::assertTrue($reflection->isAbstract());
    }
}