<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Integration\Repository;

use PHPUnit\Framework\TestCase;

final class BillAuditLogRepositoryTest extends TestCase
{
    public function testClassExists(): void
    {
        self::assertTrue(class_exists('\Tourze\HotelAgentBundle\Repository\BillAuditLogRepository'));
    }
}