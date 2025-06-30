<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Unit\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\EventSubscriber\AgentCodeSubscriber;

final class AgentCodeSubscriberTest extends TestCase
{
    public function testSubscriberInstance(): void
    {
        $mockGenerator = $this->createMock(\Tourze\HotelAgentBundle\Service\AgentCodeGenerator::class);
        $subscriber = new AgentCodeSubscriber($mockGenerator);
        
        self::assertInstanceOf(AgentCodeSubscriber::class, $subscriber);
    }
}