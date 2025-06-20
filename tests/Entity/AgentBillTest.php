<?php

namespace Tourze\HotelAgentBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;
use Tourze\HotelAgentBundle\Enum\SettlementTypeEnum;

class AgentBillTest extends TestCase
{
    private AgentBill $bill;
    private Agent $agent;

    protected function setUp(): void
    {
        $this->bill = new AgentBill();
        $this->agent = new Agent();
        $this->agent->setCompanyName('测试公司')->setCode('AGT001');
    }

    public function test_toString_returns_bill_month_and_agent_code(): void
    {
        $this->bill->setAgent($this->agent)->setBillMonth('2025-01');

        $result = (string)$this->bill;

        $this->assertSame('账单 2025-01 (AGT001)', $result);
    }

    public function test_setAgent_sets_agent(): void
    {
        $this->bill->setAgent($this->agent);

        $this->assertSame($this->agent, $this->bill->getAgent());
    }

    public function test_setBillMonth_with_valid_month(): void
    {
        $this->bill->setBillMonth('2025-01');

        $this->assertSame('2025-01', $this->bill->getBillMonth());
    }

    public function test_setOrderCount_with_valid_count(): void
    {
        $this->bill->setOrderCount(15);

        $this->assertSame(15, $this->bill->getOrderCount());
    }

    public function test_setOrderCount_with_zero(): void
    {
        $this->bill->setOrderCount(0);

        $this->assertSame(0, $this->bill->getOrderCount());
    }

    public function test_setTotalAmount_with_valid_amount(): void
    {
        $this->bill->setTotalAmount('1500.50');

        $this->assertSame('1500.50', $this->bill->getTotalAmount());
    }

    public function test_setCommissionAmount_with_valid_amount(): void
    {
        $this->bill->setCommissionAmount('150.05');

        $this->assertSame('150.05', $this->bill->getCommissionAmount());
    }

    public function test_setCommissionRate_with_valid_rate(): void
    {
        $this->bill->setCommissionRate('0.10');

        $this->assertSame('0.10', $this->bill->getCommissionRate());
    }

    public function test_setSettlementType_with_valid_type(): void
    {
        $this->bill->setSettlementType(SettlementTypeEnum::HALF_MONTHLY);

        $this->assertSame(SettlementTypeEnum::HALF_MONTHLY, $this->bill->getSettlementType());
    }

    public function test_setStatus_with_valid_status(): void
    {
        $this->bill->setStatus(BillStatusEnum::CONFIRMED);

        $this->assertSame(BillStatusEnum::CONFIRMED, $this->bill->getStatus());
    }

    public function test_setConfirmTime_with_valid_time(): void
    {
        $time = new \DateTime();
        $this->bill->setConfirmTime($time);

        $this->assertSame($time, $this->bill->getConfirmTime());
    }

    public function test_setConfirmTime_with_null(): void
    {
        $this->bill->setConfirmTime(null);

        $this->assertNull($this->bill->getConfirmTime());
    }

    public function test_setPayTime_with_valid_time(): void
    {
        $time = new \DateTime();
        $this->bill->setPayTime($time);

        $this->assertSame($time, $this->bill->getPayTime());
    }

    public function test_setPaymentReference_with_valid_reference(): void
    {
        $this->bill->setPaymentReference('PAY20250101001');

        $this->assertSame('PAY20250101001', $this->bill->getPaymentReference());
    }

    public function test_setPaymentReference_with_null(): void
    {
        $this->bill->setPaymentReference(null);

        $this->assertNull($this->bill->getPaymentReference());
    }

    public function test_setRemarks_with_valid_remarks(): void
    {
        $remarks = '测试备注信息';
        $this->bill->setRemarks($remarks);

        $this->assertSame($remarks, $this->bill->getRemarks());
    }

    public function test_setRemarks_with_null(): void
    {
        $this->bill->setRemarks(null);

        $this->assertNull($this->bill->getRemarks());
    }

    public function test_confirm_updates_status_and_time_when_pending(): void
    {
        $this->bill->setStatus(BillStatusEnum::PENDING);

        $result = $this->bill->confirm();

        $this->assertSame($this->bill, $result);
        $this->assertSame(BillStatusEnum::CONFIRMED, $this->bill->getStatus());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->bill->getConfirmTime());
    }

    public function test_confirm_does_not_update_when_not_pending(): void
    {
        $this->bill->setStatus(BillStatusEnum::CONFIRMED);
        $originalTime = $this->bill->getConfirmTime();

        $result = $this->bill->confirm();

        $this->assertSame($this->bill, $result);
        $this->assertSame(BillStatusEnum::CONFIRMED, $this->bill->getStatus());
        $this->assertSame($originalTime, $this->bill->getConfirmTime());
    }

    public function test_markAsPaid_updates_status_and_time_when_confirmed(): void
    {
        $this->bill->setStatus(BillStatusEnum::CONFIRMED);
        $reference = 'PAY20250101001';

        $result = $this->bill->markAsPaid($reference);

        $this->assertSame($this->bill, $result);
        $this->assertSame(BillStatusEnum::PAID, $this->bill->getStatus());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->bill->getPayTime());
        $this->assertSame($reference, $this->bill->getPaymentReference());
    }

    public function test_markAsPaid_without_reference(): void
    {
        $this->bill->setStatus(BillStatusEnum::CONFIRMED);

        $result = $this->bill->markAsPaid();

        $this->assertSame($this->bill, $result);
        $this->assertSame(BillStatusEnum::PAID, $this->bill->getStatus());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->bill->getPayTime());
        $this->assertNull($this->bill->getPaymentReference());
    }

    public function test_markAsPaid_does_not_update_when_not_confirmed(): void
    {
        $this->bill->setStatus(BillStatusEnum::PENDING);

        $result = $this->bill->markAsPaid('PAY001');

        $this->assertSame($this->bill, $result);
        $this->assertSame(BillStatusEnum::PENDING, $this->bill->getStatus());
        $this->assertNull($this->bill->getPayTime());
        $this->assertNull($this->bill->getPaymentReference());
    }

    public function test_calculateCommission_calculates_correctly(): void
    {
        $this->bill->setTotalAmount('1000.00')
            ->setCommissionRate('0.10');

        $result = $this->bill->calculateCommission();

        $this->assertSame($this->bill, $result);
        $this->assertSame('100.00', $this->bill->getCommissionAmount());
    }

    public function test_calculateCommission_with_decimal_amounts(): void
    {
        $this->bill->setTotalAmount('1234.56')
            ->setCommissionRate('0.08');

        $result = $this->bill->calculateCommission();

        $this->assertSame($this->bill, $result);
        $this->assertSame('98.76', $this->bill->getCommissionAmount());
    }

    public function test_calculateCommission_with_zero_rate(): void
    {
        $this->bill->setTotalAmount('1000.00')
            ->setCommissionRate('0.00');

        $result = $this->bill->calculateCommission();

        $this->assertSame($this->bill, $result);
        $this->assertSame('0.00', $this->bill->getCommissionAmount());
    }

    public function test_calculateCommission_with_zero_amount(): void
    {
        $this->bill->setTotalAmount('0.00')
            ->setCommissionRate('0.10');

        $result = $this->bill->calculateCommission();

        $this->assertSame($this->bill, $result);
        $this->assertSame('0.00', $this->bill->getCommissionAmount());
    }

    public function test_setCreateTime_sets_time(): void
    {
        $time = new \DateTimeImmutable();
        $this->bill->setCreateTime($time);

        $this->assertSame($time, $this->bill->getCreateTime());
    }

    public function test_setUpdateTime_sets_time(): void
    {
        $time = new \DateTime();
        $this->bill->setUpdateTime($time);

        $this->assertSame($time, $this->bill->getUpdateTime());
    }

    public function test_default_values(): void
    {
        $bill = new AgentBill();

        $this->assertSame('', $bill->getBillMonth());
        $this->assertSame(0, $bill->getOrderCount());
        $this->assertSame('0.00', $bill->getTotalAmount());
        $this->assertSame('0.00', $bill->getCommissionAmount());
        $this->assertSame('0.00', $bill->getCommissionRate());
        $this->assertSame(SettlementTypeEnum::MONTHLY, $bill->getSettlementType());
        $this->assertSame(BillStatusEnum::PENDING, $bill->getStatus());
        $this->assertNull($bill->getConfirmTime());
        $this->assertNull($bill->getPayTime());
        $this->assertNull($bill->getPaymentReference());
        $this->assertNull($bill->getRemarks());
        $this->assertNull($bill->getCreateTime());
        $this->assertNull($bill->getUpdateTime());
        $this->assertNull($bill->getCreatedBy());
    }
}
