<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\Exception\ExportException;

final class ExportExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new ExportException('Test message');
        
        self::assertInstanceOf(ExportException::class, $exception);
        self::assertSame('Test message', $exception->getMessage());
    }
}