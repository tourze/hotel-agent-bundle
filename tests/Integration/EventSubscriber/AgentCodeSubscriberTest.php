<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Integration\EventSubscriber;

use PHPUnit\Framework\TestCase;

final class AgentCodeSubscriberTest extends TestCase
{
    public function testClassExists(): void
    {
        self::assertTrue(class_exists('\Tourze\HotelAgentBundle\EventSubscriber\AgentCodeSubscriber'));
    }
}