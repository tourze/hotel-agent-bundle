<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\Exception\AgentBillException;

final class AgentBillExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new AgentBillException('Test message');
        
        self::assertInstanceOf(AgentBillException::class, $exception);
        self::assertSame('Test message', $exception->getMessage());
    }
}