<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Integration\Command;

use PHPUnit\Framework\TestCase;

final class GenerateMonthlyBillsCommandTest extends TestCase
{
    public function testClassExists(): void
    {
        self::assertTrue(class_exists('\Tourze\HotelAgentBundle\Command\GenerateMonthlyBillsCommand'));
    }
}