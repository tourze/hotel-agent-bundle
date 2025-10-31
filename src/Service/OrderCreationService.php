<?php

declare(strict_types=1);

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
use Tourze\HotelAgentBundle\Exception\OrderProcessingException;
use Tourze\HotelAgentBundle\Repository\AgentRepository;
use Tourze\HotelContractBundle\Entity\DailyInventory;
use Tourze\HotelContractBundle\Service\RoomTypeInventoryService;
use Tourze\HotelProfileBundle\Entity\RoomType;
use Tourze\HotelProfileBundle\Service\RoomTypeService;

/**
 * 订单创建服务
 */
readonly class OrderCreationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AgentRepository $agentRepository,
        private RoomTypeService $roomTypeService,
        private RoomTypeInventoryService $roomTypeInventoryService,
    ) {
    }

    /**
     * 验证订单创建数据
     *
     * @param array<string, mixed> $formData
     */
    public function validateOrderData(array $formData): void
    {
        $requiredFields = ['agent_id', 'room_type_id', 'check_in_date', 'check_out_date', 'room_count'];
        foreach ($requiredFields as $field) {
            if (!isset($formData[$field]) || '' === $formData[$field]) {
                throw new OrderProcessingException("字段 {$field} 不能为空");
            }
        }
    }

    /**
     * 解析用户选择的库存ID
     *
     * @param array<string, mixed> $formData
     * @return array<string, int[]>
     */
    public function parseSelectedInventories(array $formData): array
    {
        if (isset($formData['selected_inventories']) && is_array($formData['selected_inventories'])) {
            return $this->parseNewFormatInventories($formData);
        }

        return $this->parseLegacyFormatInventories($formData);
    }

    /**
     * 解析新格式的库存数据（推荐格式）
     *
     * @param array<string, mixed> $formData
     * @return array<string, int[]>
     */
    private function parseNewFormatInventories(array $formData): array
    {
        $checkInDateStr = is_string($formData['check_in_date'] ?? null) ? $formData['check_in_date'] : throw new \InvalidArgumentException('Invalid check_in_date');
        $checkOutDateStr = is_string($formData['check_out_date'] ?? null) ? $formData['check_out_date'] : throw new \InvalidArgumentException('Invalid check_out_date');
        $roomCount = is_numeric($formData['room_count'] ?? null) ? (int) $formData['room_count'] : throw new \InvalidArgumentException('Invalid room_count');

        $checkInDate = new \DateTimeImmutable($checkInDateStr);
        $checkOutDate = new \DateTimeImmutable($checkOutDateStr);

        $selectedInventories = [];
        $inventoryIndex = 0;
        $currentDate = $checkInDate;

        $rawInventories = $formData['selected_inventories'] ?? [];
        if (!is_array($rawInventories)) {
            throw new \InvalidArgumentException('Invalid selected_inventories');
        }
        /** @var int[] $inventoryIds */
        $inventoryIds = array_map(fn ($v) => is_numeric($v) ? (int) $v : 0, $rawInventories);

        while ($currentDate < $checkOutDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $selectedInventories[$dateStr] = $this->allocateInventoriesForDate(
                $inventoryIds,
                $inventoryIndex,
                $roomCount
            );
            $inventoryIndex += $roomCount;
            $currentDate = $currentDate->modify('+1 day');
        }

        return $selectedInventories;
    }

    /**
     * 为指定日期分配库存
     *
     * 不考虑并发：此方法在事务内执行，由调用方确保事务隔离
     *
     * @param int[] $inventories
     * @return int[]
     */
    private function allocateInventoriesForDate(array $inventories, int $startIndex, int $roomCount): array
    {
        $dayInventories = [];
        for ($i = 0; $i < $roomCount; ++$i) {
            $index = $startIndex + $i;
            if (isset($inventories[$index])) {
                $dayInventories[] = (int) $inventories[$index];
            }
        }

        return $dayInventories;
    }

    /**
     * 解析旧格式的库存数据（兼容性支持）
     *
     * @param array<string, mixed> $formData
     * @return array<string, int[]>
     */
    private function parseLegacyFormatInventories(array $formData): array
    {
        $selectedInventories = [];

        foreach ($formData as $key => $value) {
            if (0 === strpos($key, 'inventory_')) {
                $date = $this->extractDateFromLegacyKey($key);
                $selectedInventories[$date] = $this->convertValueToIntArray($value);
            }
        }

        return $selectedInventories;
    }

    /**
     * 从旧格式的键中提取日期
     */
    private function extractDateFromLegacyKey(string $key): string
    {
        $datePart = substr($key, 10); // 去掉 'inventory_' 前缀

        return str_replace('_', '-', $datePart);
    }

    /**
     * 将值转换为整数数组
     *
     * @return int[]
     */
    private function convertValueToIntArray(mixed $value): array
    {
        if (is_array($value)) {
            return array_map(fn ($v) => is_numeric($v) ? (int) $v : 0, $value);
        }

        return is_numeric($value) ? [(int) $value] : [0];
    }

    /**
     * 查找并验证代理商
     */
    public function findAndValidateAgent(int $agentId): Agent
    {
        $agent = $this->agentRepository->find($agentId);
        if (null === $agent) {
            throw new OrderProcessingException('代理商不存在');
        }

        return $agent;
    }

    /**
     * 查找并验证房型
     */
    public function findAndValidateRoomType(int $roomTypeId): RoomType
    {
        $roomType = $this->roomTypeService->findRoomTypeById($roomTypeId);
        if (null === $roomType) {
            throw new OrderProcessingException('房型不存在');
        }

        return $roomType;
    }

    /**
     * 验证日期范围
     */
    public function validateDateRange(\DateTimeImmutable $checkInDate, \DateTimeImmutable $checkOutDate): void
    {
        if ($checkOutDate <= $checkInDate) {
            throw new OrderProcessingException('退房日期必须晚于入住日期');
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
     *
     * 不考虑并发：此方法在事务内执行，由调用方确保事务隔离
     */
    public function validateAndReserveInventory(
        int $inventoryId,
        RoomType $roomType,
        string $dateStr,
    ): DailyInventory {
        return $this->roomTypeInventoryService->validateAndReserveInventoryById(
            $inventoryId,
            $roomType,
            $dateStr
        );
    }

    /**
     * 创建订单项
     */
    public function createOrderItem(
        Order $order,
        RoomType $roomType,
        \DateTimeImmutable $checkInDate,
        \DateTimeImmutable $checkOutDate,
        DailyInventory $dailyInventory,
    ): OrderItem {
        $orderItem = new OrderItem();
        $orderItem->setOrder($order);
        $orderItem->setHotel($roomType->getHotel());
        $orderItem->setRoomType($roomType);
        $orderItem->setCheckInDate($checkInDate);
        $orderItem->setCheckOutDate($checkOutDate);
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
     *
     * @param array<string, mixed> $formData
     */
    public function createOrderWithItems(array $formData): Order
    {
        // 验证数据
        $this->validateOrderData($formData);

        $agentId = is_numeric($formData['agent_id'] ?? null) ? (int) $formData['agent_id'] : throw new \InvalidArgumentException('Invalid agent_id');
        $roomTypeId = is_numeric($formData['room_type_id'] ?? null) ? (int) $formData['room_type_id'] : throw new \InvalidArgumentException('Invalid room_type_id');
        $checkInDateStr = is_string($formData['check_in_date'] ?? null) ? $formData['check_in_date'] : throw new \InvalidArgumentException('Invalid check_in_date');
        $checkOutDateStr = is_string($formData['check_out_date'] ?? null) ? $formData['check_out_date'] : throw new \InvalidArgumentException('Invalid check_out_date');
        $roomCount = is_numeric($formData['room_count'] ?? null) ? (int) $formData['room_count'] : throw new \InvalidArgumentException('Invalid room_count');

        $remarkRaw = $formData['remark'] ?? '';
        $remark = is_string($remarkRaw) ? $remarkRaw : '';

        $checkInDate = new \DateTimeImmutable($checkInDateStr);
        $checkOutDate = new \DateTimeImmutable($checkOutDateStr);

        // 验证基础数据
        $agent = $this->findAndValidateAgent($agentId);
        $roomType = $this->findAndValidateRoomType($roomTypeId);
        $this->validateDateRange($checkInDate, $checkOutDate);

        // 解析用户选择的库存
        $selectedInventories = $this->parseSelectedInventories($formData);

        $this->entityManager->beginTransaction();

        try {
            $order = $this->createOrder($agent, $remark);
            $totalOrderAmount = $this->processOrderItemsByDate(
                $order,
                $roomType,
                $checkInDate,
                $checkOutDate,
                $selectedInventories,
                $roomCount
            );

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
     * 逐日处理订单项创建
     *
     * @param array<string, int[]> $selectedInventories
     * @return BigDecimal
     */
    private function processOrderItemsByDate(
        Order $order,
        RoomType $roomType,
        \DateTimeImmutable $checkInDate,
        \DateTimeImmutable $checkOutDate,
        array $selectedInventories,
        int $roomCount,
    ): BigDecimal {
        $totalOrderAmount = BigDecimal::zero();
        $currentDate = $checkInDate;

        while ($currentDate < $checkOutDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayAmount = $this->createOrderItemsForDate(
                $order,
                $roomType,
                $currentDate,
                $selectedInventories,
                $dateStr,
                $roomCount
            );
            $totalOrderAmount = $totalOrderAmount->plus($dayAmount);
            $currentDate = $currentDate->modify('+1 day');
        }

        return $totalOrderAmount;
    }

    /**
     * 为指定日期创建订单项
     *
     * @param array<string, int[]> $selectedInventories
     * @return BigDecimal
     */
    private function createOrderItemsForDate(
        Order $order,
        RoomType $roomType,
        \DateTimeImmutable $currentDate,
        array $selectedInventories,
        string $dateStr,
        int $roomCount,
    ): BigDecimal {
        // 检查用户是否选择了该日期的库存
        if (!isset($selectedInventories[$dateStr]) || [] === $selectedInventories[$dateStr]) {
            throw new OrderProcessingException("请为日期 {$dateStr} 选择库存方案");
        }

        // 验证选择的库存数量是否等于房间数量
        $selectedInventoryIds = $selectedInventories[$dateStr];
        if (count($selectedInventoryIds) !== $roomCount) {
            throw new OrderProcessingException("日期 {$dateStr} 选择的库存数量（" . count($selectedInventoryIds) . "）与房间数量（{$roomCount}）不匹配");
        }

        $dayAmount = BigDecimal::zero();
        $nextDay = $currentDate->modify('+1 day');

        // 为每个选择的库存创建OrderItem
        foreach ($selectedInventoryIds as $inventoryId) {
            // 验证并占用库存
            $dailyInventory = $this->validateAndReserveInventory(
                $inventoryId,
                $roomType,
                $dateStr
            );

            $orderItem = $this->createOrderItem(
                $order,
                $roomType,
                $currentDate,
                $nextDay,
                $dailyInventory
            );

            // 将 OrderItem 添加到 Order 的集合中
            $order->addOrderItem($orderItem);

            $dayAmount = $dayAmount->plus(BigDecimal::of($dailyInventory->getSellingPrice()));
        }

        return $dayAmount;
    }

    /**
     * 释放订单的库存占用（订单创建失败时调用）
     */
    public function releaseOrderInventory(Order $order): void
    {
        foreach ($order->getOrderItems() as $orderItem) {
            if (null !== $orderItem->getDailyInventory()) {
                $dailyInventory = $orderItem->getDailyInventory();
                // 恢复为可用状态
                $this->roomTypeInventoryService->releaseInventory($dailyInventory);
            }
        }
        $this->entityManager->flush();
    }
}
