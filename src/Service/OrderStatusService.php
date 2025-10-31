<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Enum\OrderItemStatusEnum;
use Tourze\HotelAgentBundle\Enum\OrderStatusEnum;
use Tourze\HotelAgentBundle\Exception\OrderProcessingException;
use Tourze\HotelContractBundle\Enum\DailyInventoryStatusEnum;
use Tourze\HotelContractBundle\Service\InventorySummaryService;

/**
 * 订单状态管理服务
 */
#[WithMonologChannel(channel: 'hotel_agent')]
readonly class OrderStatusService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private InventorySummaryService $summaryService,
    ) {
    }

    /**
     * 确认订单
     */
    public function confirmOrder(Order $order, int $operatorId): void
    {
        if (OrderStatusEnum::PENDING !== $order->getStatus()) {
            throw new OrderProcessingException('只有待确认状态的订单才能确认');
        }

        $this->entityManager->beginTransaction();

        try {
            // 更新订单状态
            $order->confirm($operatorId);

            // 更新所有订单项状态并确认库存占用
            foreach ($order->getOrderItems() as $orderItem) {
                $orderItem->setStatus(OrderItemStatusEnum::CONFIRMED);
                $orderItem->setLastModifiedBy($operatorId);

                // 将对应的库存状态从 PENDING 改为 SOLD
                if (null !== $orderItem->getDailyInventory()) {
                    $dailyInventory = $orderItem->getDailyInventory();
                    $dailyInventory->setStatus(DailyInventoryStatusEnum::SOLD);
                    $this->entityManager->persist($dailyInventory);
                }
            }

            // 记录变更历史
            $order->addChangeRecord('confirm', [
                'status' => 'confirmed',
                'operator_id' => $operatorId,
                'timestamp' => new \DateTime(),
            ], $operatorId);

            $this->entityManager->persist($order);
            $this->entityManager->flush();
            $this->entityManager->commit();

            // 更新库存统计（在事务提交后）
            $this->updateInventoryStatisticsForOrder($order);

            $this->logger->info('订单确认成功', [
                'order_id' => $order->getId(),
                'order_no' => $order->getOrderNo(),
                'operator_id' => $operatorId,
            ]);
        } catch (\Throwable $e) {
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }
            $this->logger->error('订单确认失败', [
                'order_id' => $order->getId(),
                'order_no' => $order->getOrderNo(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 取消订单
     */
    public function cancelOrder(Order $order, string $reason, int $operatorId): void
    {
        if (!in_array($order->getStatus(), [OrderStatusEnum::PENDING, OrderStatusEnum::CONFIRMED], true)) {
            throw new OrderProcessingException('订单状态不允许取消');
        }

        $this->entityManager->beginTransaction();

        try {
            // 释放已分配的库存
            $this->releaseOrderItemInventory($order);

            // 更新订单状态
            $order->cancel($reason, $operatorId);

            // 更新所有订单项状态
            foreach ($order->getOrderItems() as $orderItem) {
                $orderItem->setStatus(OrderItemStatusEnum::CANCELED);
                $orderItem->setLastModifiedBy($operatorId);
            }

            // 记录变更历史
            $order->addChangeRecord('cancel', [
                'status' => 'canceled',
                'reason' => $reason,
                'operator_id' => $operatorId,
                'timestamp' => new \DateTime(),
            ], $operatorId);

            $this->entityManager->persist($order);
            $this->entityManager->flush();
            $this->entityManager->commit();

            // 更新库存统计（在事务提交后）
            $this->updateInventoryStatisticsForOrder($order);

            $this->logger->info('订单取消成功', [
                'order_id' => $order->getId(),
                'order_no' => $order->getOrderNo(),
                'reason' => $reason,
                'operator_id' => $operatorId,
            ]);
        } catch (\Throwable $e) {
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }
            $this->logger->error('订单取消失败', [
                'order_id' => $order->getId(),
                'order_no' => $order->getOrderNo(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 关闭订单
     */
    public function closeOrder(Order $order, string $reason, int $operatorId): void
    {
        if (OrderStatusEnum::CONFIRMED !== $order->getStatus()) {
            throw new OrderProcessingException('只有已确认状态的订单才能关闭');
        }

        $this->entityManager->beginTransaction();

        try {
            // 释放已分配的库存
            $this->releaseOrderItemInventory($order);

            // 更新订单状态
            $order->close($reason, $operatorId);

            // 更新所有订单项状态
            foreach ($order->getOrderItems() as $orderItem) {
                $orderItem->setStatus(OrderItemStatusEnum::COMPLETED);
                $orderItem->setLastModifiedBy($operatorId);
            }

            // 记录变更历史
            $order->addChangeRecord('close', [
                'status' => 'closed',
                'reason' => $reason,
                'operator_id' => $operatorId,
                'timestamp' => new \DateTime(),
            ], $operatorId);

            $this->entityManager->persist($order);
            $this->entityManager->flush();
            $this->entityManager->commit();

            // 更新库存统计（在事务提交后）
            $this->updateInventoryStatisticsForOrder($order);

            $this->logger->info('订单关闭成功', [
                'order_id' => $order->getId(),
                'order_no' => $order->getOrderNo(),
                'reason' => $reason,
                'operator_id' => $operatorId,
            ]);
        } catch (\Throwable $e) {
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }
            $this->logger->error('订单关闭失败', [
                'order_id' => $order->getId(),
                'order_no' => $order->getOrderNo(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 释放订单项的库存分配
     *
     * 不考虑并发：此方法在事务内执行，由调用方确保事务隔离
     */
    private function releaseOrderItemInventory(Order $order): void
    {
        foreach ($order->getOrderItems() as $orderItem) {
            // 如果订单项有分配的库存，则释放它
            if (null !== $orderItem->getDailyInventory()) {
                $dailyInventory = $orderItem->getDailyInventory();
                // 将库存状态重置为可用
                $dailyInventory->setStatus(DailyInventoryStatusEnum::AVAILABLE);
                $this->entityManager->persist($dailyInventory);

                // 解除订单项与库存的关联
                $orderItem->setDailyInventory(null);
                $this->entityManager->persist($orderItem);
            }
        }
    }

    /**
     * 批量更新订单项状态
     */
    public function updateOrderItemsStatus(Order $order, OrderItemStatusEnum $status): void
    {
        foreach ($order->getOrderItems() as $orderItem) {
            $orderItem->setStatus($status);
            $this->entityManager->persist($orderItem);
        }

        $this->entityManager->flush();
    }

    /**
     * 检查订单是否可以确认
     */
    public function canConfirm(Order $order): bool
    {
        return OrderStatusEnum::PENDING === $order->getStatus();
    }

    /**
     * 检查订单是否可以取消
     */
    public function canCancel(Order $order): bool
    {
        return in_array($order->getStatus(), [OrderStatusEnum::PENDING, OrderStatusEnum::CONFIRMED], true);
    }

    /**
     * 检查订单是否可以关闭
     */
    public function canClose(Order $order): bool
    {
        return OrderStatusEnum::CONFIRMED === $order->getStatus();
    }

    /**
     * 更新订单相关的库存统计
     *
     * 不考虑并发：此方法在事务内执行，由调用方确保事务隔离
     */
    private function updateInventoryStatisticsForOrder(Order $order): void
    {
        try {
            $updatedDates = [];

            foreach ($order->getOrderItems() as $orderItem) {
                $hotel = $orderItem->getHotel();
                $roomType = $orderItem->getRoomType();
                $checkInDate = $orderItem->getCheckInDate();
                $checkOutDate = $orderItem->getCheckOutDate();

                if (null === $hotel || null === $roomType || null === $checkInDate || null === $checkOutDate) {
                    continue;
                }

                // 为入住日期到退房日期之间的每一天更新统计
                $currentDate = new \DateTime($checkInDate->format('Y-m-d'));
                $endDate = new \DateTime($checkOutDate->format('Y-m-d'));

                while ($currentDate < $endDate) {
                    $this->summaryService->syncInventorySummary(clone $currentDate);
                    $updatedDates[] = $currentDate->format('Y-m-d');
                    $currentDate->modify('+1 day');
                }
            }

            $this->logger->info('订单库存统计更新成功', [
                'order_id' => $order->getId(),
                'order_no' => $order->getOrderNo(),
                'updated_dates' => array_unique($updatedDates),
            ]);
        } catch (\Throwable $e) {
            // 库存统计更新失败不影响订单操作，只记录日志
            $this->logger->error('订单库存统计更新失败', [
                'order_id' => $order->getId(),
                'order_no' => $order->getOrderNo(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
