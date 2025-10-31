<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\HotelAgentBundle\Entity\OrderItem;
use Tourze\HotelContractBundle\Service\InventorySummaryService;

/**
 * OrderItem 实体监听器，用于自动更新库存统计
 */
#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: OrderItem::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: OrderItem::class)]
#[AsEntityListener(event: Events::postRemove, method: 'postRemove', entity: OrderItem::class)]
#[WithMonologChannel(channel: 'hotel_agent')]
readonly class OrderItemInventoryListener
{
    public function __construct(
        private InventorySummaryService $summaryService,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * OrderItem 创建后更新库存统计
     */
    public function postPersist(OrderItem $orderItem, PostPersistEventArgs $args): void
    {
        $this->updateInventoryStatistics($orderItem, 'created');
    }

    /**
     * OrderItem 更新后更新库存统计
     */
    public function postUpdate(OrderItem $orderItem, PostUpdateEventArgs $args): void
    {
        $this->updateInventoryStatistics($orderItem, 'updated');
    }

    /**
     * OrderItem 删除后更新库存统计
     */
    public function postRemove(OrderItem $orderItem, PostRemoveEventArgs $args): void
    {
        $this->updateInventoryStatistics($orderItem, 'removed');
    }

    /**
     * 更新相关的库存统计
     */
    private function updateInventoryStatistics(OrderItem $orderItem, string $operation): void
    {
        try {
            $hotel = $orderItem->getHotel();
            $roomType = $orderItem->getRoomType();
            $checkInDate = $orderItem->getCheckInDate();
            $checkOutDate = $orderItem->getCheckOutDate();

            if (null === $hotel || null === $roomType || null === $checkInDate || null === $checkOutDate) {
                $this->logger->warning('OrderItem 数据不完整，跳过库存统计更新', [
                    'order_item_id' => $orderItem->getId(),
                    'operation' => $operation,
                ]);

                return;
            }

            // 收集需要更新统计的日期
            $currentDate = new \DateTime($checkInDate->format('Y-m-d'));
            $endDate = new \DateTime($checkOutDate->format('Y-m-d'));

            $updatedDates = [];
            while ($currentDate < $endDate) {
                // 为每个受影响的日期调用统一的同步方法
                $result = $this->summaryService->syncInventorySummary(clone $currentDate);

                $updatedDates[] = $currentDate->format('Y-m-d');
                $currentDate->modify('+1 day');
            }

            $orderItemId = $orderItem->isPersisted() ? $orderItem->getId() : 'new';
            $this->logger->info('OrderItem 库存统计更新成功', [
                'order_item_id' => $orderItemId,
                'hotel_id' => $hotel->getId(),
                'room_type_id' => $roomType->getId(),
                'operation' => $operation,
                'updated_dates' => $updatedDates,
            ]);
        } catch (\Throwable $e) {
            $orderItemId = $orderItem->isPersisted() ? $orderItem->getId() : 'new';
            $this->logger->error('OrderItem 库存统计更新失败', [
                'order_item_id' => $orderItemId,
                'operation' => $operation,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
