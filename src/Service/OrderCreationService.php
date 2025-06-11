<?php

namespace Tourze\HotelAgentBundle\Service;

use Brick\Math\BigDecimal;
use Doctrine\ORM\EntityManagerInterface;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Entity\OrderItem;
use Tourze\HotelAgentBundle\Enum\AuditStatusEnum;
use Tourze\HotelAgentBundle\Enum\OrderItemStatusEnum;
use Tourze\HotelAgentBundle\Enum\OrderSourceEnum;
use Tourze\HotelAgentBundle\Enum\OrderStatusEnum;
use Tourze\HotelContractBundle\Entity\DailyInventory;
use Tourze\HotelContractBundle\Enum\DailyInventoryStatusEnum;
use Tourze\HotelProfileBundle\Entity\RoomType;

/**
 * 订单创建服务
 */
class OrderCreationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * 验证订单创建数据
     */
    public function validateOrderData(array $formData): void
    {
        $requiredFields = ['agent_id', 'room_type_id', 'check_in_date', 'check_out_date', 'room_count'];
        foreach ($requiredFields as $field) {
            if (empty($formData[$field])) {
                throw new \Exception("字段 {$field} 不能为空");
            }
        }
    }

    /**
     * 解析用户选择的库存ID
     */
    public function parseSelectedInventories(array $formData): array
    {
        $selectedInventories = [];

        // 方式1：检查 selected_inventories 数组格式（推荐）
        if (isset($formData['selected_inventories']) && is_array($formData['selected_inventories'])) {
            // 根据入住日期和退房日期确定每天需要的库存数量
            $checkInDate = new \DateTime($formData['check_in_date']);
            $checkOutDate = new \DateTime($formData['check_out_date']);
            $roomCount = (int)$formData['room_count'];
            
            $inventoryIndex = 0;
            $currentDate = clone $checkInDate;
            
            while ($currentDate < $checkOutDate) {
                $dateStr = $currentDate->format('Y-m-d');
                $selectedInventories[$dateStr] = [];
                
                // 为这一天分配对应数量的库存
                for ($i = 0; $i < $roomCount; $i++) {
                    if (isset($formData['selected_inventories'][$inventoryIndex])) {
                        $selectedInventories[$dateStr][] = (int)$formData['selected_inventories'][$inventoryIndex];
                        $inventoryIndex++;
                    }
                }
                
                $currentDate->modify('+1 day');
            }
        } else {
            // 方式2：兼容旧的 inventory_日期 格式
            foreach ($formData as $key => $value) {
                if (strpos($key, 'inventory_') === 0) {
                    // 提取日期：inventory_2025-06-04 -> 2025-06-04
                    $datePart = substr($key, 10); // 去掉 'inventory_' 前缀
                    $date = str_replace('_', '-', $datePart);

                    if (is_array($value)) {
                        $selectedInventories[$date] = array_map('intval', $value);
                    } else {
                        $selectedInventories[$date] = [(int)$value];
                    }
                }
            }
        }

        return $selectedInventories;
    }

    /**
     * 查找并验证代理商
     */
    public function findAndValidateAgent(int $agentId): Agent
    {
        $agent = $this->entityManager->getRepository(Agent::class)->find($agentId);
        if (!$agent) {
            throw new \Exception('代理商不存在');
        }
        return $agent;
    }

    /**
     * 查找并验证房型
     */
    public function findAndValidateRoomType(int $roomTypeId): RoomType
    {
        $roomType = $this->entityManager->getRepository(RoomType::class)->find($roomTypeId);
        if (!$roomType) {
            throw new \Exception('房型不存在');
        }
        return $roomType;
    }

    /**
     * 验证日期范围
     */
    public function validateDateRange(\DateTime $checkInDate, \DateTime $checkOutDate): void
    {
        if ($checkOutDate <= $checkInDate) {
            throw new \Exception('退房日期必须晚于入住日期');
        }
    }

    /**
     * 创建订单基础信息
     */
    public function createOrder(Agent $agent, string $remark = ''): Order
    {
        $order = new Order();
        $order->setAgent($agent);
        $order->setSource(OrderSourceEnum::MANUAL_INPUT);
        $order->setStatus(OrderStatusEnum::PENDING);
        $order->setAuditStatus(AuditStatusEnum::PENDING);
        $order->setIsComplex(true);
        $order->setRemark($remark);

        // 生成订单编号
        $orderNo = 'ORD' . date('YmdHis') . sprintf('%04d', rand(1, 9999));
        $order->setOrderNo($orderNo);

        $this->entityManager->persist($order);
        return $order;
    }

    /**
     * 验证选择的库存并占用
     */
    public function validateAndReserveInventory(
        int $inventoryId,
        RoomType $roomType,
        string $dateStr
    ): DailyInventory {
        $dailyInventory = $this->entityManager->getRepository(DailyInventory::class)
            ->find($inventoryId);

        if (!$dailyInventory) {
            throw new \Exception("日期 {$dateStr} 选择的库存不存在");
        }

        // 验证库存是否匹配房型和日期
        if ($dailyInventory->getRoomType()->getId() !== $roomType->getId() ||
            $dailyInventory->getDate()->format('Y-m-d') !== $dateStr) {
            throw new \Exception("日期 {$dateStr} 选择的库存不匹配");
        }

        // 检查库存状态 - 只能选择可用状态的库存
        if ($dailyInventory->getStatus() !== DailyInventoryStatusEnum::AVAILABLE) {
            throw new \Exception("日期 {$dateStr} 选择的库存已被占用或不可用，当前状态：{$dailyInventory->getStatus()->getLabel()}");
        }

        // 占用库存 - 设置为待确认状态
        $dailyInventory->setStatus(DailyInventoryStatusEnum::PENDING);
        $this->entityManager->persist($dailyInventory);

        return $dailyInventory;
    }

    /**
     * 创建订单项
     */
    public function createOrderItem(
        Order $order,
        RoomType $roomType,
        \DateTime $checkInDate,
        \DateTime $checkOutDate,
        DailyInventory $dailyInventory
    ): OrderItem {
        $orderItem = new OrderItem();
        $orderItem->setOrder($order);
        $orderItem->setHotel($roomType->getHotel());
        $orderItem->setRoomType($roomType);
        $orderItem->setCheckInDate(clone $checkInDate);
        $orderItem->setCheckOutDate(clone $checkOutDate);
        $orderItem->setStatus(OrderItemStatusEnum::PENDING);
        $orderItem->setDailyInventory($dailyInventory);
        $orderItem->setContract($dailyInventory->getContract());

        // 设置价格信息
        $orderItem->setUnitPrice($dailyInventory->getSellingPrice());
        $orderItem->setCostPrice($dailyInventory->getCostPrice());

        $this->entityManager->persist($orderItem);
        return $orderItem;
    }

    /**
     * 创建完整的订单（包含订单项）
     */
    public function createOrderWithItems(array $formData): Order
    {
        // 验证数据
        $this->validateOrderData($formData);

        $agentId = (int)$formData['agent_id'];
        $roomTypeId = (int)$formData['room_type_id'];
        $checkInDate = new \DateTime($formData['check_in_date']);
        $checkOutDate = new \DateTime($formData['check_out_date']);
        $roomCount = (int)$formData['room_count'];
        $remark = $formData['remark'] ?? '';

        // 验证基础数据
        $agent = $this->findAndValidateAgent($agentId);
        $roomType = $this->findAndValidateRoomType($roomTypeId);
        $this->validateDateRange($checkInDate, $checkOutDate);

        // 解析用户选择的库存
        $selectedInventories = $this->parseSelectedInventories($formData);

        $this->entityManager->beginTransaction();
        
        try {
            // 创建订单
            $order = $this->createOrder($agent, $remark);
            $totalOrderAmount = BigDecimal::zero();

            // 逐日创建订单项
            $currentDate = clone $checkInDate;
            while ($currentDate < $checkOutDate) {
                $dateStr = $currentDate->format('Y-m-d');

                // 检查用户是否选择了该日期的库存
                if (!isset($selectedInventories[$dateStr]) || empty($selectedInventories[$dateStr])) {
                    throw new \Exception("请为日期 {$dateStr} 选择库存方案");
                }

                // 验证选择的库存数量是否等于房间数量
                $selectedInventoryIds = $selectedInventories[$dateStr];
                if (count($selectedInventoryIds) !== $roomCount) {
                    throw new \Exception("日期 {$dateStr} 选择的库存数量（".count($selectedInventoryIds)."）与房间数量（{$roomCount}）不匹配");
                }

                // 为每个选择的库存创建OrderItem
                foreach ($selectedInventoryIds as $inventoryId) {
                    // 验证并占用库存
                    $dailyInventory = $this->validateAndReserveInventory(
                        $inventoryId,
                        $roomType,
                        $dateStr
                    );

                    $nextDay = clone $currentDate;
                    $nextDay->modify('+1 day');

                    $orderItem = $this->createOrderItem(
                        $order,
                        $roomType,
                        $currentDate,
                        $nextDay,
                        $dailyInventory
                    );

                    $totalOrderAmount = $totalOrderAmount->plus(BigDecimal::of($dailyInventory->getSellingPrice()));
                }

                $currentDate->modify('+1 day');
            }

            // 设置订单总金额
            $order->setTotalAmount($totalOrderAmount->toScale(2)->__toString());

            $this->entityManager->flush();
            $this->entityManager->commit();

            return $order;

        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    /**
     * 释放订单的库存占用（订单创建失败时调用）
     */
    public function releaseOrderInventory(Order $order): void
    {
        foreach ($order->getOrderItems() as $orderItem) {
            if ($orderItem->getDailyInventory()) {
                $dailyInventory = $orderItem->getDailyInventory();
                // 恢复为可用状态
                $dailyInventory->setStatus(DailyInventoryStatusEnum::AVAILABLE);
                $this->entityManager->persist($dailyInventory);
            }
        }
        $this->entityManager->flush();
    }
}
