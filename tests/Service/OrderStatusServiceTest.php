<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Service;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Enum\OrderItemStatusEnum;
use Tourze\HotelAgentBundle\Enum\OrderStatusEnum;
use Tourze\HotelAgentBundle\Exception\OrderProcessingException;
use Tourze\HotelAgentBundle\Service\OrderStatusService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(OrderStatusService::class)]
#[RunTestsInSeparateProcesses]
final class OrderStatusServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 空实现，这个测试类不需要额外的初始化
    }

    public function testServiceInstance(): void
    {
        $this->assertInstanceOf(OrderStatusService::class, self::getService(OrderStatusService::class));
    }

    public function testCanCancel(): void
    {
        $service = self::getService(OrderStatusService::class);

        // 创建测试订单
        $pendingOrder = $this->createMockOrder(OrderStatusEnum::PENDING);
        $confirmedOrder = $this->createMockOrder(OrderStatusEnum::CONFIRMED);
        $canceledOrder = $this->createMockOrder(OrderStatusEnum::CANCELED);
        $closedOrder = $this->createMockOrder(OrderStatusEnum::CLOSED);

        // 测试可以取消的状态
        $this->assertTrue($service->canCancel($pendingOrder));
        $this->assertTrue($service->canCancel($confirmedOrder));

        // 测试不能取消的状态
        $this->assertFalse($service->canCancel($canceledOrder));
        $this->assertFalse($service->canCancel($closedOrder));
    }

    public function testCanConfirm(): void
    {
        $service = self::getService(OrderStatusService::class);

        // 创建测试订单
        $pendingOrder = $this->createMockOrder(OrderStatusEnum::PENDING);
        $confirmedOrder = $this->createMockOrder(OrderStatusEnum::CONFIRMED);
        $canceledOrder = $this->createMockOrder(OrderStatusEnum::CANCELED);

        // 只有待确认状态的订单可以确认
        $this->assertTrue($service->canConfirm($pendingOrder));
        $this->assertFalse($service->canConfirm($confirmedOrder));
        $this->assertFalse($service->canConfirm($canceledOrder));
    }

    public function testCanClose(): void
    {
        $service = self::getService(OrderStatusService::class);

        // 创建测试订单
        $pendingOrder = $this->createMockOrder(OrderStatusEnum::PENDING);
        $confirmedOrder = $this->createMockOrder(OrderStatusEnum::CONFIRMED);
        $canceledOrder = $this->createMockOrder(OrderStatusEnum::CANCELED);
        $closedOrder = $this->createMockOrder(OrderStatusEnum::CLOSED);

        // 只有已确认状态的订单可以关闭
        $this->assertFalse($service->canClose($pendingOrder));
        $this->assertTrue($service->canClose($confirmedOrder));
        $this->assertFalse($service->canClose($canceledOrder));
        $this->assertFalse($service->canClose($closedOrder));
    }

    public function testCancelOrder(): void
    {
        $service = self::getService(OrderStatusService::class);

        // 测试状态不允许取消的情况
        $canceledOrder = $this->createMockOrder(OrderStatusEnum::CANCELED);

        $this->expectException(OrderProcessingException::class);
        $this->expectExceptionMessage('订单状态不允许取消');
        $service->cancelOrder($canceledOrder, '测试取消', 1);
    }

    public function testCloseOrder(): void
    {
        $service = self::getService(OrderStatusService::class);

        // 测试非确认状态订单无法关闭
        $pendingOrder = $this->createMockOrder(OrderStatusEnum::PENDING);

        $this->expectException(OrderProcessingException::class);
        $this->expectExceptionMessage('只有已确认状态的订单才能关闭');
        $service->closeOrder($pendingOrder, '测试关闭', 1);
    }

    public function testConfirmOrder(): void
    {
        $service = self::getService(OrderStatusService::class);

        // 测试非待确认状态订单无法确认
        $confirmedOrder = $this->createMockOrder(OrderStatusEnum::CONFIRMED);

        $this->expectException(OrderProcessingException::class);
        $this->expectExceptionMessage('只有待确认状态的订单才能确认');
        $service->confirmOrder($confirmedOrder, 1);
    }

    public function testUpdateOrderItemsStatus(): void
    {
        $service = self::getService(OrderStatusService::class);

        // 简单的业务逻辑测试 - 测试此方法存在且可调用
        $order = $this->createMock(Order::class);
        $order->method('getOrderItems')->willReturn(new ArrayCollection([]));

        // 对于空订单项集合，此方法应该正常执行而不抛出异常
        $service->updateOrderItemsStatus($order, OrderItemStatusEnum::CONFIRMED);

        // 使用 expectNotToPerformAssertions 明确表示此测试验证不抛出异常
        $this->expectNotToPerformAssertions();
    }

    /**
     * 创建模拟订单
     */
    private function createMockOrder(OrderStatusEnum $status): MockObject&Order
    {
        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn($status);

        return $order;
    }
}
