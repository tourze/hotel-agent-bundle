<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\HotelAgentBundle\Exception\OrderImportException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(OrderImportException::class)]
final class OrderImportExceptionTest extends AbstractExceptionTestCase
{
    public function testException(): void
    {
        $exception = new OrderImportException('Test message');

        self::assertSame('Test message', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
    }

    public function testExceptionWithCodeAndPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new OrderImportException('Test message', 123, $previous);

        self::assertSame('Test message', $exception->getMessage());
        self::assertSame(123, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
    }
}
