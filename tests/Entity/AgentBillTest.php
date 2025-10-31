<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;
use Tourze\HotelAgentBundle\Enum\SettlementTypeEnum;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(AgentBill::class)]
final class AgentBillTest extends AbstractEntityTestCase
{
    public function testToStringReturnsBillMonthAndAgentCode(): void
    {
        $agent = new Agent();
        $agent->setCompanyName('测试公司');
        $agent->setCode('AGT001');

        $bill = new AgentBill();
        $bill->setAgent($agent);
        $bill->setBillMonth('2025-01');

        $result = (string) $bill;

        $this->assertSame('账单 2025-01 (AGT001)', $result);
    }

    public function testSetAgentSetsAgent(): void
    {
        $bill = new AgentBill();
        $agent = new Agent();
        $agent->setCompanyName('测试公司');
        $agent->setCode('AGT001');

        $bill->setAgent($agent);

        $this->assertSame($agent, $bill->getAgent());
    }

    public function testSetBillMonthWithValidMonth(): void
    {
        $bill = new AgentBill();
        $bill->setBillMonth('2025-01');

        $this->assertSame('2025-01', $bill->getBillMonth());
    }

    public function testSetOrderCountWithValidCount(): void
    {
        $bill = new AgentBill();
        $bill->setOrderCount(15);

        $this->assertSame(15, $bill->getOrderCount());
    }

    public function testSetOrderCountWithZero(): void
    {
        $bill = new AgentBill();
        $bill->setOrderCount(0);

        $this->assertSame(0, $bill->getOrderCount());
    }

    public function testSetTotalAmountWithValidAmount(): void
    {
        $bill = new AgentBill();
        $bill->setTotalAmount('1500.50');

        $this->assertSame('1500.50', $bill->getTotalAmount());
    }

    public function testSetCommissionAmountWithValidAmount(): void
    {
        $bill = new AgentBill();
        $bill->setCommissionAmount('150.05');

        $this->assertSame('150.05', $bill->getCommissionAmount());
    }

    public function testSetCommissionRateWithValidRate(): void
    {
        $bill = new AgentBill();
        $bill->setCommissionRate('0.10');

        $this->assertSame('0.10', $bill->getCommissionRate());
    }

    public function testSetSettlementTypeWithValidType(): void
    {
        $bill = new AgentBill();
        $bill->setSettlementType(SettlementTypeEnum::HALF_MONTHLY);

        $this->assertSame(SettlementTypeEnum::HALF_MONTHLY, $bill->getSettlementType());
    }

    public function testSetStatusWithValidStatus(): void
    {
        $bill = new AgentBill();
        $bill->setStatus(BillStatusEnum::CONFIRMED);

        $this->assertSame(BillStatusEnum::CONFIRMED, $bill->getStatus());
    }

    public function testSetConfirmTimeWithValidTime(): void
    {
        $time = new \DateTimeImmutable();
        $bill = new AgentBill();
        $bill->setConfirmTime($time);

        $this->assertSame($time, $bill->getConfirmTime());
    }

    public function testSetConfirmTimeWithNull(): void
    {
        $bill = new AgentBill();
        $bill->setConfirmTime(null);

        $this->assertNull($bill->getConfirmTime());
    }

    public function testSetPayTimeWithValidTime(): void
    {
        $time = new \DateTimeImmutable();
        $bill = new AgentBill();
        $bill->setPayTime($time);

        $this->assertSame($time, $bill->getPayTime());
    }

    public function testSetPaymentReferenceWithValidReference(): void
    {
        $bill = new AgentBill();
        $bill->setPaymentReference('PAY20250101001');

        $this->assertSame('PAY20250101001', $bill->getPaymentReference());
    }

    public function testSetPaymentReferenceWithNull(): void
    {
        $bill = new AgentBill();
        $bill->setPaymentReference(null);

        $this->assertNull($bill->getPaymentReference());
    }

    public function testSetRemarksWithValidRemarks(): void
    {
        $remarks = '测试备注信息';
        $bill = new AgentBill();
        $bill->setRemarks($remarks);

        $this->assertSame($remarks, $bill->getRemarks());
    }

    public function testSetRemarksWithNull(): void
    {
        $bill = new AgentBill();
        $bill->setRemarks(null);

        $this->assertNull($bill->getRemarks());
    }

    public function testConfirmUpdatesStatusAndTimeWhenPending(): void
    {
        $bill = new AgentBill();
        $bill->setStatus(BillStatusEnum::PENDING);

        $result = $bill->confirm();

        $this->assertSame($bill, $result);
        $this->assertSame(BillStatusEnum::CONFIRMED, $bill->getStatus());
        $this->assertInstanceOf(\DateTimeInterface::class, $bill->getConfirmTime());
    }

    public function testConfirmDoesNotUpdateWhenNotPending(): void
    {
        $bill = new AgentBill();
        $bill->setStatus(BillStatusEnum::CONFIRMED);
        $originalTime = $bill->getConfirmTime();

        $result = $bill->confirm();

        $this->assertSame($bill, $result);
        $this->assertSame(BillStatusEnum::CONFIRMED, $bill->getStatus());
        $this->assertSame($originalTime, $bill->getConfirmTime());
    }

    public function testMarkAsPaidUpdatesStatusAndTimeWhenConfirmed(): void
    {
        $bill = new AgentBill();
        $bill->setStatus(BillStatusEnum::CONFIRMED);
        $reference = 'PAY20250101001';

        $result = $bill->markAsPaid($reference);

        $this->assertSame($bill, $result);
        $this->assertSame(BillStatusEnum::PAID, $bill->getStatus());
        $this->assertInstanceOf(\DateTimeInterface::class, $bill->getPayTime());
        $this->assertSame($reference, $bill->getPaymentReference());
    }

    public function testMarkAsPaidWithoutReference(): void
    {
        $bill = new AgentBill();
        $bill->setStatus(BillStatusEnum::CONFIRMED);

        $result = $bill->markAsPaid();

        $this->assertSame($bill, $result);
        $this->assertSame(BillStatusEnum::PAID, $bill->getStatus());
        $this->assertInstanceOf(\DateTimeInterface::class, $bill->getPayTime());
        $this->assertNull($bill->getPaymentReference());
    }

    public function testMarkAsPaidDoesNotUpdateWhenNotConfirmed(): void
    {
        $bill = new AgentBill();
        $bill->setStatus(BillStatusEnum::PENDING);

        $result = $bill->markAsPaid('PAY001');

        $this->assertSame($bill, $result);
        $this->assertSame(BillStatusEnum::PENDING, $bill->getStatus());
        $this->assertNull($bill->getPayTime());
        $this->assertNull($bill->getPaymentReference());
    }

    public function testCalculateCommissionCalculatesCorrectly(): void
    {
        $bill = new AgentBill();
        $bill->setTotalAmount('1000.00');
        $bill->setCommissionRate('0.10');

        $result = $bill->calculateCommission();

        $this->assertSame($bill, $result);
        $this->assertSame('100.00', $bill->getCommissionAmount());
    }

    public function testCalculateCommissionWithDecimalAmounts(): void
    {
        $bill = new AgentBill();
        $bill->setTotalAmount('1234.56');
        $bill->setCommissionRate('0.08');

        $result = $bill->calculateCommission();

        $this->assertSame($bill, $result);
        $this->assertSame('98.76', $bill->getCommissionAmount());
    }

    public function testCalculateCommissionWithZeroRate(): void
    {
        $bill = new AgentBill();
        $bill->setTotalAmount('1000.00');
        $bill->setCommissionRate('0.00');

        $result = $bill->calculateCommission();

        $this->assertSame($bill, $result);
        $this->assertSame('0.00', $bill->getCommissionAmount());
    }

    public function testCalculateCommissionWithZeroAmount(): void
    {
        $bill = new AgentBill();
        $bill->setTotalAmount('0.00');
        $bill->setCommissionRate('0.10');

        $result = $bill->calculateCommission();

        $this->assertSame($bill, $result);
        $this->assertSame('0.00', $bill->getCommissionAmount());
    }

    public function testSetCreateTimeSetsTime(): void
    {
        $time = new \DateTimeImmutable();
        $bill = new AgentBill();
        $bill->setCreateTime($time);

        $this->assertSame($time, $bill->getCreateTime());
    }

    public function testSetUpdateTimeSetsTime(): void
    {
        $time = new \DateTimeImmutable();
        $bill = new AgentBill();
        $bill->setUpdateTime($time);

        $this->assertSame($time, $bill->getUpdateTime());
    }

    public static function propertiesProvider(): iterable
    {
        yield 'billMonth' => ['billMonth', '2025-01'];
        yield 'orderCount' => ['orderCount', 15];
        yield 'totalAmount' => ['totalAmount', '1500.50'];
        yield 'commissionAmount' => ['commissionAmount', '150.05'];
        yield 'commissionRate' => ['commissionRate', '0.10'];
        yield 'settlementType' => ['settlementType', SettlementTypeEnum::MONTHLY];
        yield 'status' => ['status', BillStatusEnum::PENDING];
        yield 'confirmTime' => ['confirmTime', new \DateTimeImmutable()];
        yield 'payTime' => ['payTime', new \DateTimeImmutable()];
        yield 'paymentReference' => ['paymentReference', 'PAY20250101001'];
        yield 'remarks' => ['remarks', '测试备注信息'];
    }

    protected function createEntity(): object
    {
        return new AgentBill();
    }
}
