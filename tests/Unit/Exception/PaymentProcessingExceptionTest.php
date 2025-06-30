<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\Exception\PaymentProcessingException;

final class PaymentProcessingExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new PaymentProcessingException('Test message');
        
        self::assertInstanceOf(PaymentProcessingException::class, $exception);
        self::assertSame('Test message', $exception->getMessage());
    }
}