<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\HotelAgentBundle\Service\BillAuditService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(BillAuditService::class)]
#[RunTestsInSeparateProcesses]
final class BillAuditServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 空实现，这个测试类不需要额外的初始化
    }

    public function testServiceInstance(): void
    {
        $this->assertInstanceOf(BillAuditService::class, self::getService(BillAuditService::class));
    }

    public function testLogStatusChangeMethodExists(): void
    {
        $billAuditService = self::getService(BillAuditService::class);

        $this->assertInstanceOf(BillAuditService::class, $billAuditService);
    }

    public function testLogStatusChangeParameters(): void
    {
        $billAuditService = self::getService(BillAuditService::class);

        // 验证 logStatusChange 方法接受正确的参数
        $reflection = new \ReflectionMethod($billAuditService, 'logStatusChange');
        $parameters = $reflection->getParameters();

        $this->assertCount(4, $parameters);
        $this->assertEquals('agentBill', $parameters[0]->getName());
        $this->assertEquals('fromStatus', $parameters[1]->getName());
        $this->assertEquals('toStatus', $parameters[2]->getName());
        $this->assertEquals('remarks', $parameters[3]->getName());
        $this->assertTrue($parameters[1]->allowsNull());
        $this->assertTrue($parameters[3]->isOptional());
    }

    public function testLogRecalculationParameters(): void
    {
        $billAuditService = self::getService(BillAuditService::class);

        // 验证 logRecalculation 方法接受正确的参数
        $reflection = new \ReflectionMethod($billAuditService, 'logRecalculation');
        $parameters = $reflection->getParameters();

        $this->assertCount(4, $parameters);
        $this->assertEquals('agentBill', $parameters[0]->getName());
        $this->assertEquals('oldData', $parameters[1]->getName());
        $this->assertEquals('newData', $parameters[2]->getName());
        $this->assertEquals('remarks', $parameters[3]->getName());
        $this->assertTrue($parameters[3]->isOptional());
    }

    public function testLogAuditActionParameters(): void
    {
        $billAuditService = self::getService(BillAuditService::class);

        // 验证 logAuditAction 方法接受正确的参数
        $reflection = new \ReflectionMethod($billAuditService, 'logAuditAction');
        $parameters = $reflection->getParameters();

        $this->assertCount(3, $parameters);
        $this->assertEquals('agentBill', $parameters[0]->getName());
        $this->assertEquals('action', $parameters[1]->getName());
        $this->assertEquals('remarks', $parameters[2]->getName());
        $this->assertTrue($parameters[2]->isOptional());
    }

    public function testBatchAuditBillsParameters(): void
    {
        $billAuditService = self::getService(BillAuditService::class);

        // 验证 batchAuditBills 方法接受正确的参数
        $reflection = new \ReflectionMethod($billAuditService, 'batchAuditBills');
        $parameters = $reflection->getParameters();

        $this->assertCount(3, $parameters);
        $this->assertEquals('billIds', $parameters[0]->getName());
        $this->assertEquals('action', $parameters[1]->getName());
        $this->assertEquals('remarks', $parameters[2]->getName());
        $this->assertTrue($parameters[2]->isOptional());
    }

    public function testCanAuditBillParameters(): void
    {
        $billAuditService = self::getService(BillAuditService::class);

        // 验证 canAuditBill 方法接受正确的参数
        $reflection = new \ReflectionMethod($billAuditService, 'canAuditBill');
        $parameters = $reflection->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('agentBill', $parameters[0]->getName());
        $this->assertEquals('action', $parameters[1]->getName());
    }

    public function testExportAuditLogsParameters(): void
    {
        $billAuditService = self::getService(BillAuditService::class);

        // 验证 exportAuditLogs 方法接受正确的参数
        $reflection = new \ReflectionMethod($billAuditService, 'exportAuditLogs');
        $parameters = $reflection->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('startDate', $parameters[0]->getName());
        $this->assertEquals('endDate', $parameters[1]->getName());
    }

    public function testExportAuditLogsReturnsArray(): void
    {
        $billAuditService = self::getService(BillAuditService::class);

        $startDate = new \DateTime('2024-01-01');
        $endDate = new \DateTime('2024-01-31');

        $result = $billAuditService->exportAuditLogs($startDate, $endDate);

        // 在空数据库情况下，应该返回空数组
        $this->assertSame([], $result);
    }
}
