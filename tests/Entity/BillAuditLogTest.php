<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Entity\BillAuditLog;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(BillAuditLog::class)]
final class BillAuditLogTest extends AbstractEntityTestCase
{
    public function testToStringReturnsActionAndTime(): void
    {
        $time = new \DateTimeImmutable('2024-01-01 10:00:00');
        $auditLog = new BillAuditLog();
        $auditLog->setAction('测试操作');
        $auditLog->setCreateTime($time);

        $this->assertSame('审核日志 测试操作 (2024-01-01 10:00:00)', $auditLog->__toString());
    }

    public function testToStringWithNullTime(): void
    {
        $auditLog = new BillAuditLog();
        $auditLog->setAction('测试操作');

        $this->assertSame('审核日志 测试操作 ()', $auditLog->__toString());
    }

    public function testSetAgentBillWithValidBill(): void
    {
        $auditLog = new BillAuditLog();
        $agentBill = $this->createAgentBill();
        $auditLog->setAgentBill($agentBill);
        $this->assertSame($agentBill, $auditLog->getAgentBill());
    }

    public function testSetActionWithValidAction(): void
    {
        $auditLog = new BillAuditLog();
        $auditLog->setAction('状态变更');
        $this->assertSame('状态变更', $auditLog->getAction());
    }

    public function testSetFromStatusWithValidStatus(): void
    {
        $auditLog = new BillAuditLog();
        $auditLog->setFromStatus(BillStatusEnum::PENDING);
        $this->assertSame(BillStatusEnum::PENDING, $auditLog->getFromStatus());
    }

    public function testSetFromStatusWithNull(): void
    {
        $auditLog = new BillAuditLog();
        $auditLog->setFromStatus(null);
        $this->assertNull($auditLog->getFromStatus());
    }

    public function testSetToStatusWithValidStatus(): void
    {
        $auditLog = new BillAuditLog();
        $auditLog->setToStatus(BillStatusEnum::CONFIRMED);
        $this->assertSame(BillStatusEnum::CONFIRMED, $auditLog->getToStatus());
    }

    public function testSetToStatusWithNull(): void
    {
        $auditLog = new BillAuditLog();
        $auditLog->setToStatus(null);
        $this->assertNull($auditLog->getToStatus());
    }

    public function testSetRemarksWithValidRemarks(): void
    {
        $auditLog = new BillAuditLog();
        $auditLog->setRemarks('测试备注');
        $this->assertSame('测试备注', $auditLog->getRemarks());
    }

    public function testSetRemarksWithNull(): void
    {
        $auditLog = new BillAuditLog();
        $auditLog->setRemarks(null);
        $this->assertNull($auditLog->getRemarks());
    }

    public function testSetChangeDetailsWithValidArray(): void
    {
        $details = ['old' => 'value1', 'new' => 'value2'];
        $auditLog = new BillAuditLog();
        $auditLog->setChangeDetails($details);
        $this->assertSame($details, $auditLog->getChangeDetails());
    }

    public function testSetChangeDetailsWithNull(): void
    {
        $auditLog = new BillAuditLog();
        $auditLog->setChangeDetails(null);
        $this->assertNull($auditLog->getChangeDetails());
    }

    public function testSetOperatorNameWithValidName(): void
    {
        $auditLog = new BillAuditLog();
        $auditLog->setOperatorName('操作员');
        $this->assertSame('操作员', $auditLog->getOperatorName());
    }

    public function testSetOperatorNameWithNull(): void
    {
        $auditLog = new BillAuditLog();
        $auditLog->setOperatorName(null);
        $this->assertNull($auditLog->getOperatorName());
    }

    public function testSetIpAddressWithValidIp(): void
    {
        $auditLog = new BillAuditLog();
        $auditLog->setIpAddress('192.168.1.1');
        $this->assertSame('192.168.1.1', $auditLog->getIpAddress());
    }

    public function testSetIpAddressWithNull(): void
    {
        $auditLog = new BillAuditLog();
        $auditLog->setIpAddress(null);
        $this->assertNull($auditLog->getIpAddress());
    }

    public function testCreateStatusChangeLogCreatesCorrectLog(): void
    {
        $agentBill = $this->createAgentBill();
        $log = BillAuditLog::createStatusChangeLog(
            $agentBill,
            BillStatusEnum::PENDING,
            BillStatusEnum::CONFIRMED,
            '测试备注',
            '操作员',
            '192.168.1.1'
        );

        $this->assertSame($agentBill, $log->getAgentBill());
        $this->assertSame('状态变更', $log->getAction());
        $this->assertSame(BillStatusEnum::PENDING, $log->getFromStatus());
        $this->assertSame(BillStatusEnum::CONFIRMED, $log->getToStatus());
        $this->assertSame('测试备注', $log->getRemarks());
        $this->assertSame('操作员', $log->getOperatorName());
        $this->assertSame('192.168.1.1', $log->getIpAddress());
    }

    public function testCreateStatusChangeLogWithNullFromStatus(): void
    {
        $agentBill = $this->createAgentBill();
        $log = BillAuditLog::createStatusChangeLog(
            $agentBill,
            null,
            BillStatusEnum::CONFIRMED
        );

        $this->assertNull($log->getFromStatus());
        $this->assertSame(BillStatusEnum::CONFIRMED, $log->getToStatus());
    }

    public function testCreateStatusChangeLogWithMinimalParameters(): void
    {
        $agentBill = $this->createAgentBill();
        $log = BillAuditLog::createStatusChangeLog(
            $agentBill,
            BillStatusEnum::PENDING,
            BillStatusEnum::CONFIRMED
        );

        $this->assertSame($agentBill, $log->getAgentBill());
        $this->assertNull($log->getRemarks());
        $this->assertNull($log->getOperatorName());
        $this->assertNull($log->getIpAddress());
    }

    public function testCreateRecalculateLogCreatesCorrectLog(): void
    {
        $agentBill = $this->createAgentBill();
        $oldData = ['amount' => '100.00'];
        $newData = ['amount' => '200.00'];

        $log = BillAuditLog::createRecalculateLog(
            $agentBill,
            $oldData,
            $newData,
            '重新计算备注',
            '操作员',
            '192.168.1.1'
        );

        $this->assertSame($agentBill, $log->getAgentBill());
        $this->assertSame('重新计算', $log->getAction());
        $this->assertSame(['old' => $oldData, 'new' => $newData], $log->getChangeDetails());
        $this->assertSame('重新计算备注', $log->getRemarks());
        $this->assertSame('操作员', $log->getOperatorName());
        $this->assertSame('192.168.1.1', $log->getIpAddress());
    }

    public function testCreateRecalculateLogWithEmptyData(): void
    {
        $agentBill = $this->createAgentBill();
        $log = BillAuditLog::createRecalculateLog(
            $agentBill,
            [],
            []
        );

        $this->assertSame(['old' => [], 'new' => []], $log->getChangeDetails());
    }

    public function testCreateAuditLogCreatesCorrectLog(): void
    {
        $agentBill = $this->createAgentBill();
        $log = BillAuditLog::createAuditLog(
            $agentBill,
            '审核通过',
            '审核备注',
            '审核员',
            '192.168.1.1'
        );

        $this->assertSame($agentBill, $log->getAgentBill());
        $this->assertSame('审核通过', $log->getAction());
        $this->assertSame('审核备注', $log->getRemarks());
        $this->assertSame('审核员', $log->getOperatorName());
        $this->assertSame('192.168.1.1', $log->getIpAddress());
    }

    public function testCreateAuditLogWithMinimalParameters(): void
    {
        $agentBill = $this->createAgentBill();
        $log = BillAuditLog::createAuditLog($agentBill, '审核操作');

        $this->assertSame($agentBill, $log->getAgentBill());
        $this->assertSame('审核操作', $log->getAction());
        $this->assertNull($log->getRemarks());
        $this->assertNull($log->getOperatorName());
        $this->assertNull($log->getIpAddress());
    }

    public function testSetCreateTimeSetsTime(): void
    {
        $time = new \DateTimeImmutable();
        $auditLog = new BillAuditLog();
        $auditLog->setCreateTime($time);

        $this->assertSame($time, $auditLog->getCreateTime());
    }

    public static function propertiesProvider(): iterable
    {
        $agent = new Agent();
        $agent->setCode('TEST001');
        $agent->setCompanyName('测试代理公司');
        $agent->setCreatedBy('test-user');

        $bill = new AgentBill();
        $bill->setAgent($agent);
        $bill->setBillMonth('2024-01');

        yield 'status_change_log' => ['agentBill', $bill];
        yield 'minimal_log' => ['action', '创建'];
        yield 'recalculate_log' => ['remarks', '价格调整'];
    }

    protected function createEntity(): object
    {
        return new BillAuditLog();
    }

    private function createAgentBill(): AgentBill
    {
        $agent = new Agent();
        $agent->setCode('TEST001');

        $bill = new AgentBill();
        $bill->setAgent($agent);
        $bill->setBillMonth('2024-01');

        return $bill;
    }
}
