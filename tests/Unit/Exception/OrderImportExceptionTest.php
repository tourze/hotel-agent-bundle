<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\Exception\OrderImportException;

final class OrderImportExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new OrderImportException('Test message');
        
        self::assertInstanceOf(OrderImportException::class, $exception);
        self::assertSame('Test message', $exception->getMessage());
    }
}