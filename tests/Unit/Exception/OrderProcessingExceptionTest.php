<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\Exception\OrderProcessingException;

final class OrderProcessingExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new OrderProcessingException('Test message');
        
        self::assertInstanceOf(OrderProcessingException::class, $exception);
        self::assertSame('Test message', $exception->getMessage());
    }
}