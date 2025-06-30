<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Integration\Service;

use PHPUnit\Framework\TestCase;

final class BillAuditServiceTest extends TestCase
{
    public function testClassExists(): void
    {
        self::assertTrue(class_exists('\Tourze\HotelAgentBundle\Service\BillAuditService'));
    }
}