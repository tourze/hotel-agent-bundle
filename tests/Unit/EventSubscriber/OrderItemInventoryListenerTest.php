<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Unit\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\EventSubscriber\OrderItemInventoryListener;

final class OrderItemInventoryListenerTest extends TestCase
{
    public function testListenerInstance(): void
    {
        $mockService = $this->createMock(\Tourze\HotelContractBundle\Service\InventorySummaryService::class);
        $mockLogger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $listener = new OrderItemInventoryListener($mockService, $mockLogger);
        
        self::assertInstanceOf(OrderItemInventoryListener::class, $listener);
    }
}