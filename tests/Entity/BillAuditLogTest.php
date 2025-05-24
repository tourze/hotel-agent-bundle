<?php

namespace Tourze\HotelAgentBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Entity\BillAuditLog;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;

class BillAuditLogTest extends TestCase
{
    private BillAuditLog $auditLog;
    private AgentBill $agentBill;

    protected function setUp(): void
    {
        $this->auditLog = new BillAuditLog();
        $this->agentBill = $this->createAgentBill();
    }

    public function test_toString_returns_action_and_time(): void
    {
        $time = new \DateTime('2024-01-01 10:00:00');
        $this->auditLog->setAction('测试操作')
            ->setCreateTime($time);

        $this->assertSame('审核日志 测试操作 (2024-01-01 10:00:00)', $this->auditLog->__toString());
    }

    public function test_toString_with_null_time(): void
    {
        $this->auditLog->setAction('测试操作');

        $this->assertSame('审核日志 测试操作 ()', $this->auditLog->__toString());
    }

    public function test_setAgentBill_with_valid_bill(): void
    {
        $result = $this->auditLog->setAgentBill($this->agentBill);

        $this->assertSame($this->auditLog, $result);
        $this->assertSame($this->agentBill, $this->auditLog->getAgentBill());
    }

    public function test_setAction_with_valid_action(): void
    {
        $result = $this->auditLog->setAction('状态变更');

        $this->assertSame($this->auditLog, $result);
        $this->assertSame('状态变更', $this->auditLog->getAction());
    }

    public function test_setFromStatus_with_valid_status(): void
    {
        $result = $this->auditLog->setFromStatus(BillStatusEnum::PENDING);

        $this->assertSame($this->auditLog, $result);
        $this->assertSame(BillStatusEnum::PENDING, $this->auditLog->getFromStatus());
    }

    public function test_setFromStatus_with_null(): void
    {
        $result = $this->auditLog->setFromStatus(null);

        $this->assertSame($this->auditLog, $result);
        $this->assertNull($this->auditLog->getFromStatus());
    }

    public function test_setToStatus_with_valid_status(): void
    {
        $result = $this->auditLog->setToStatus(BillStatusEnum::CONFIRMED);

        $this->assertSame($this->auditLog, $result);
        $this->assertSame(BillStatusEnum::CONFIRMED, $this->auditLog->getToStatus());
    }

    public function test_setToStatus_with_null(): void
    {
        $result = $this->auditLog->setToStatus(null);

        $this->assertSame($this->auditLog, $result);
        $this->assertNull($this->auditLog->getToStatus());
    }

    public function test_setRemarks_with_valid_remarks(): void
    {
        $result = $this->auditLog->setRemarks('测试备注');

        $this->assertSame($this->auditLog, $result);
        $this->assertSame('测试备注', $this->auditLog->getRemarks());
    }

    public function test_setRemarks_with_null(): void
    {
        $result = $this->auditLog->setRemarks(null);

        $this->assertSame($this->auditLog, $result);
        $this->assertNull($this->auditLog->getRemarks());
    }

    public function test_setChangeDetails_with_valid_array(): void
    {
        $details = ['old' => 'value1', 'new' => 'value2'];
        $result = $this->auditLog->setChangeDetails($details);

        $this->assertSame($this->auditLog, $result);
        $this->assertSame($details, $this->auditLog->getChangeDetails());
    }

    public function test_setChangeDetails_with_null(): void
    {
        $result = $this->auditLog->setChangeDetails(null);

        $this->assertSame($this->auditLog, $result);
        $this->assertNull($this->auditLog->getChangeDetails());
    }

    public function test_setOperatorName_with_valid_name(): void
    {
        $result = $this->auditLog->setOperatorName('操作员');

        $this->assertSame($this->auditLog, $result);
        $this->assertSame('操作员', $this->auditLog->getOperatorName());
    }

    public function test_setOperatorName_with_null(): void
    {
        $result = $this->auditLog->setOperatorName(null);

        $this->assertSame($this->auditLog, $result);
        $this->assertNull($this->auditLog->getOperatorName());
    }

    public function test_setIpAddress_with_valid_ip(): void
    {
        $result = $this->auditLog->setIpAddress('192.168.1.1');

        $this->assertSame($this->auditLog, $result);
        $this->assertSame('192.168.1.1', $this->auditLog->getIpAddress());
    }

    public function test_setIpAddress_with_null(): void
    {
        $result = $this->auditLog->setIpAddress(null);

        $this->assertSame($this->auditLog, $result);
        $this->assertNull($this->auditLog->getIpAddress());
    }

    public function test_createStatusChangeLog_creates_correct_log(): void
    {
        $log = BillAuditLog::createStatusChangeLog(
            $this->agentBill,
            BillStatusEnum::PENDING,
            BillStatusEnum::CONFIRMED,
            '测试备注',
            '操作员',
            '192.168.1.1'
        );

        $this->assertInstanceOf(BillAuditLog::class, $log);
        $this->assertSame($this->agentBill, $log->getAgentBill());
        $this->assertSame('状态变更', $log->getAction());
        $this->assertSame(BillStatusEnum::PENDING, $log->getFromStatus());
        $this->assertSame(BillStatusEnum::CONFIRMED, $log->getToStatus());
        $this->assertSame('测试备注', $log->getRemarks());
        $this->assertSame('操作员', $log->getOperatorName());
        $this->assertSame('192.168.1.1', $log->getIpAddress());
    }

    public function test_createStatusChangeLog_with_null_from_status(): void
    {
        $log = BillAuditLog::createStatusChangeLog(
            $this->agentBill,
            null,
            BillStatusEnum::CONFIRMED
        );

        $this->assertNull($log->getFromStatus());
        $this->assertSame(BillStatusEnum::CONFIRMED, $log->getToStatus());
    }

    public function test_createStatusChangeLog_with_minimal_parameters(): void
    {
        $log = BillAuditLog::createStatusChangeLog(
            $this->agentBill,
            BillStatusEnum::PENDING,
            BillStatusEnum::CONFIRMED
        );

        $this->assertSame($this->agentBill, $log->getAgentBill());
        $this->assertNull($log->getRemarks());
        $this->assertNull($log->getOperatorName());
        $this->assertNull($log->getIpAddress());
    }

    public function test_createRecalculateLog_creates_correct_log(): void
    {
        $oldData = ['amount' => '100.00'];
        $newData = ['amount' => '200.00'];

        $log = BillAuditLog::createRecalculateLog(
            $this->agentBill,
            $oldData,
            $newData,
            '重新计算备注',
            '操作员',
            '192.168.1.1'
        );

        $this->assertInstanceOf(BillAuditLog::class, $log);
        $this->assertSame($this->agentBill, $log->getAgentBill());
        $this->assertSame('重新计算', $log->getAction());
        $this->assertSame(['old' => $oldData, 'new' => $newData], $log->getChangeDetails());
        $this->assertSame('重新计算备注', $log->getRemarks());
        $this->assertSame('操作员', $log->getOperatorName());
        $this->assertSame('192.168.1.1', $log->getIpAddress());
    }

    public function test_createRecalculateLog_with_empty_data(): void
    {
        $log = BillAuditLog::createRecalculateLog(
            $this->agentBill,
            [],
            []
        );

        $this->assertSame(['old' => [], 'new' => []], $log->getChangeDetails());
    }

    public function test_createAuditLog_creates_correct_log(): void
    {
        $log = BillAuditLog::createAuditLog(
            $this->agentBill,
            '审核通过',
            '审核备注',
            '审核员',
            '192.168.1.1'
        );

        $this->assertInstanceOf(BillAuditLog::class, $log);
        $this->assertSame($this->agentBill, $log->getAgentBill());
        $this->assertSame('审核通过', $log->getAction());
        $this->assertSame('审核备注', $log->getRemarks());
        $this->assertSame('审核员', $log->getOperatorName());
        $this->assertSame('192.168.1.1', $log->getIpAddress());
    }

    public function test_createAuditLog_with_minimal_parameters(): void
    {
        $log = BillAuditLog::createAuditLog($this->agentBill, '审核操作');

        $this->assertSame($this->agentBill, $log->getAgentBill());
        $this->assertSame('审核操作', $log->getAction());
        $this->assertNull($log->getRemarks());
        $this->assertNull($log->getOperatorName());
        $this->assertNull($log->getIpAddress());
    }

    public function test_setCreateTime_sets_time(): void
    {
        $time = new \DateTime();

        $this->auditLog->setCreateTime($time);

        $this->assertSame($time, $this->auditLog->getCreateTime());
    }

    public function test_default_values(): void
    {
        $this->assertNull($this->auditLog->getId());
        $this->assertSame('', $this->auditLog->getAction());
        $this->assertNull($this->auditLog->getFromStatus());
        $this->assertNull($this->auditLog->getToStatus());
        $this->assertNull($this->auditLog->getRemarks());
        $this->assertNull($this->auditLog->getChangeDetails());
        $this->assertNull($this->auditLog->getOperatorName());
        $this->assertNull($this->auditLog->getIpAddress());
        $this->assertNull($this->auditLog->getCreateTime());
        $this->assertNull($this->auditLog->getCreatedBy());
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