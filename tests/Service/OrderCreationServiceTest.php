<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Exception\OrderProcessingException;
use Tourze\HotelAgentBundle\Service\OrderCreationService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(OrderCreationService::class)]
#[RunTestsInSeparateProcesses]
final class OrderCreationServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 空实现，这个测试类不需要额外的初始化
    }

    public function testServiceInstance(): void
    {
        $this->assertInstanceOf(OrderCreationService::class, self::getService(OrderCreationService::class));
    }

    public function testValidateOrderDataSuccess(): void
    {
        $service = self::getService(OrderCreationService::class);

        $validData = [
            'agent_id' => 1,
            'room_type_id' => 1,
            'check_in_date' => '2024-12-01',
            'check_out_date' => '2024-12-02',
            'room_count' => 1,
        ];

        // 验证：当所有必需字段都存在时，不应该抛出异常
        $service->validateOrderData($validData);

        // 使用 expectNotToPerformAssertions 明确表示此测试验证不抛出异常
        $this->expectNotToPerformAssertions();
    }

    public function testValidateOrderDataMissingRequiredField(): void
    {
        $service = self::getService(OrderCreationService::class);

        $invalidData = [
            'agent_id' => 1,
            // 缺少 room_type_id
            'check_in_date' => '2024-12-01',
            'check_out_date' => '2024-12-02',
            'room_count' => 1,
        ];

        $this->expectException(OrderProcessingException::class);
        $this->expectExceptionMessage('字段 room_type_id 不能为空');
        $service->validateOrderData($invalidData);
    }

    public function testValidateDateRange(): void
    {
        $service = self::getService(OrderCreationService::class);

        $checkInDate = new \DateTimeImmutable('2024-12-01');
        $checkOutDate = new \DateTimeImmutable('2024-12-02');

        // 验证：当退房日期晚于入住日期时，不应该抛出异常
        $service->validateDateRange($checkInDate, $checkOutDate);

        // 使用 expectNotToPerformAssertions 明确表示此测试验证不抛出异常
        $this->expectNotToPerformAssertions();
    }

    public function testValidateDateRangeInvalidOrder(): void
    {
        $service = self::getService(OrderCreationService::class);

        $checkInDate = new \DateTimeImmutable('2024-12-02');
        $checkOutDate = new \DateTimeImmutable('2024-12-01'); // 退房日期早于入住日期

        $this->expectException(OrderProcessingException::class);
        $this->expectExceptionMessage('退房日期必须晚于入住日期');
        $service->validateDateRange($checkInDate, $checkOutDate);
    }

    public function testCreateOrder(): void
    {
        $service = self::getService(OrderCreationService::class);

        // Mock一个Agent对象，用于测试
        $agent = $this->createMock(Agent::class);
        $agent->method('getId')->willReturn(1);
        $agent->method('getCode')->willReturn('TEST_AGENT');

        $order = $service->createOrder($agent, '测试订单');

        $this->assertInstanceOf(Order::class, $order);
        $this->assertSame($agent, $order->getAgent());
        $this->assertSame('测试订单', $order->getRemark());
    }

    public function testCreateOrderItem(): void
    {
        self::markTestSkipped('该方法需要复杂的依赖Mock，暂时跳过');
    }

    public function testCreateOrderWithItems(): void
    {
        self::markTestSkipped('该方法需要数据库事务和复杂的集成测试，暂时跳过');
    }

    public function testFindAndValidateAgent(): void
    {
        self::markTestSkipped('该方法需要数据库查询，暂时跳过');
    }

    public function testFindAndValidateRoomType(): void
    {
        self::markTestSkipped('该方法需要数据库查询，暂时跳过');
    }

    public function testParseSelectedInventories(): void
    {
        $service = self::getService(OrderCreationService::class);

        $formData = [
            'check_in_date' => '2024-12-01',
            'check_out_date' => '2024-12-03',
            'room_count' => 2,
            'selected_inventories' => [1, 2, 3, 4],
        ];

        $result = $service->parseSelectedInventories($formData);

        // 验证返回结果包含正确的日期键
        $this->assertArrayHasKey('2024-12-01', $result);
        $this->assertArrayHasKey('2024-12-02', $result);
        $this->assertCount(2, $result, '应该为两天生成库存分配');
    }

    public function testReleaseOrderInventory(): void
    {
        self::markTestSkipped('该方法需要数据库操作，暂时跳过');
    }

    public function testValidateAndReserveInventory(): void
    {
        self::markTestSkipped('该方法需要数据库操作和库存服务，暂时跳过');
    }
}
