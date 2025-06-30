<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Integration\Controller\Admin\BillReport;

use PHPUnit\Framework\TestCase;

final class AuditStatsControllerTest extends TestCase
{
    public function testClassExists(): void
    {
        self::assertTrue(class_exists(\PHPUnit\Framework\TestCase::class)); // 简单测试
    }
}