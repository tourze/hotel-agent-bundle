<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Enum\AgentLevelEnum;
use Tourze\HotelAgentBundle\Enum\AgentStatusEnum;
use Tourze\HotelAgentBundle\Enum\AuditStatusEnum;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;
use Tourze\HotelAgentBundle\Enum\OrderSourceEnum;
use Tourze\HotelAgentBundle\Enum\OrderStatusEnum;
use Tourze\HotelAgentBundle\Exception\AgentBillException;
use Tourze\HotelAgentBundle\Service\AgentBillService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(AgentBillService::class)]
#[RunTestsInSeparateProcesses]
final class AgentBillServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 无需额外设置
    }

    public function testServiceInstance(): void
    {
        $this->assertInstanceOf(AgentBillService::class, self::getService(AgentBillService::class));
    }

    public function testGenerateMonthlyBillsWithEmptyDatabase(): void
    {
        $billMonth = '2024-01';
        $result = self::getService(AgentBillService::class)->generateMonthlyBills($billMonth);
        // 在空数据库（没有有效订单）时，返回空数组
        $this->assertSame([], $result);
    }

    public function testGetAgentBillsReturnsArray(): void
    {
        $result = self::getService(AgentBillService::class)->getAgentBills();
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('limit', $result);
        // 验证返回的数据结构和类型
        $this->assertGreaterThanOrEqual(0, $result['total']);
        $this->assertGreaterThanOrEqual(1, $result['page']);
    }

    public function testGetBillStatisticsReturnsArray(): void
    {
        $statistics = self::getService(AgentBillService::class)->getBillStatistics('2024-01');
        // getBillStatistics 返回的是聚合查询结果的数组
        // 空数据库返回空数组，非空情况下每个元素应包含 status, bill_count, total_amount, total_commission
        $this->assertCount(0, array_filter($statistics, static fn ($item): bool => ! isset($item['status'])));
    }

    public function testGetDetailedBillReportReturnsArray(): void
    {
        $startDate = new \DateTime('2024-01-01');
        $endDate = new \DateTime('2024-01-31');
        $report = self::getService(AgentBillService::class)->getDetailedBillReport($startDate, $endDate);
        // 验证报告包含所有必需的键
        $this->assertArrayHasKey('total_bills', $report);
        $this->assertArrayHasKey('total_amount', $report);
        $this->assertArrayHasKey('total_commission', $report);
        $this->assertArrayHasKey('status_summary', $report);
        $this->assertArrayHasKey('agent_summary', $report);
        $this->assertArrayHasKey('monthly_summary', $report);
        // 验证基本数据约束
        $this->assertGreaterThanOrEqual(0, $report['total_bills']);
    }

    public function testConfirmBill(): void
    {
        $service = self::getService(AgentBillService::class);
        $em = self::getEntityManager();

        // 创建代理和账单
        $agent = $this->createAgent('TEST_CONFIRM');
        $bill = new AgentBill();
        $bill->setAgent($agent);
        $bill->setBillMonth('2024-01');
        $bill->setStatus(BillStatusEnum::PENDING);
        $bill->setOrderCount(1);
        $bill->setTotalAmount('100.00');
        $bill->setCommissionAmount('10.00');
        $bill->setCommissionRate('0.10');
        $em->persist($bill);
        $em->flush();

        // 测试确认待处理账单
        $result = $service->confirmBill($bill);
        $this->assertTrue($result);
        $this->assertSame(BillStatusEnum::CONFIRMED, $bill->getStatus());
        $this->assertNotNull($bill->getConfirmTime());

        // 测试确认非待处理账单应返回 false
        $result = $service->confirmBill($bill);
        $this->assertFalse($result);
    }

    public function testMarkBillAsPaid(): void
    {
        $service = self::getService(AgentBillService::class);
        $em = self::getEntityManager();

        // 创建代理和已确认的账单
        $agent = $this->createAgent('TEST_PAID');
        $bill = new AgentBill();
        $bill->setAgent($agent);
        $bill->setBillMonth('2024-01');
        $bill->setStatus(BillStatusEnum::CONFIRMED);
        $bill->setOrderCount(1);
        $bill->setTotalAmount('100.00');
        $bill->setCommissionAmount('10.00');
        $bill->setCommissionRate('0.10');
        $bill->setConfirmTime(new \DateTimeImmutable());
        $em->persist($bill);
        $em->flush();

        // 测试标记已确认账单为已支付
        $result = $service->markBillAsPaid($bill, 'PAY_REF_001');
        $this->assertTrue($result);
        $this->assertSame(BillStatusEnum::PAID, $bill->getStatus());
        $this->assertSame('PAY_REF_001', $bill->getPaymentReference());
        $this->assertNotNull($bill->getPayTime());

        // 测试标记非已确认账单应返回 false
        $pendingBill = new AgentBill();
        $pendingBill->setAgent($agent);
        $pendingBill->setBillMonth('2024-02');
        $pendingBill->setStatus(BillStatusEnum::PENDING);
        $pendingBill->setOrderCount(1);
        $pendingBill->setTotalAmount('100.00');
        $pendingBill->setCommissionAmount('10.00');
        $pendingBill->setCommissionRate('0.10');
        $em->persist($pendingBill);
        $em->flush();

        $result = $service->markBillAsPaid($pendingBill);
        $this->assertFalse($result);
    }

    public function testGenerateAgentBill(): void
    {
        $service = self::getService(AgentBillService::class);
        $em = self::getEntityManager();

        // 创建代理
        $agent = $this->createAgent('TEST_GENERATE');

        // 创建已确认的订单
        $order = new Order();
        $order->setOrderNo('ORD_GENERATE_001');
        $order->setAgent($agent);
        $order->setStatus(OrderStatusEnum::CONFIRMED);
        $order->setAuditStatus(AuditStatusEnum::APPROVED);
        $order->setSource(OrderSourceEnum::MANUAL_INPUT);
        $order->setTotalAmount('100.00');
        $order->setCreateTime(new \DateTimeImmutable('2024-01-15'));
        $em->persist($order);
        $em->flush();

        // 生成账单
        $startDate = new \DateTime('2024-01-01');
        $endDate = new \DateTime('2024-01-31');
        $bill = $service->generateAgentBill($agent, '2024-01', $startDate, $endDate);

        $this->assertInstanceOf(AgentBill::class, $bill);
        $agentFromBill = $bill->getAgent();
        $this->assertNotNull($agentFromBill);
        $this->assertSame($agent->getId(), $agentFromBill->getId());
        $this->assertSame('2024-01', $bill->getBillMonth());
        $this->assertSame(1, $bill->getOrderCount());
        $this->assertSame('100.00', $bill->getTotalAmount());
        $this->assertSame(BillStatusEnum::PENDING, $bill->getStatus());

        // 测试无订单时返回 null
        $agent2 = $this->createAgent('TEST_GENERATE_2');
        $result = $service->generateAgentBill($agent2, '2024-02', new \DateTime('2024-02-01'), new \DateTime('2024-02-29'));
        $this->assertNull($result);
    }

    public function testRecalculateBill(): void
    {
        $service = self::getService(AgentBillService::class);
        $em = self::getEntityManager();

        // 创建代理
        $agent = $this->createAgent('TEST_RECALC');

        // 创建订单和账单
        $order = new Order();
        $order->setOrderNo('ORD_RECALC_001');
        $order->setAgent($agent);
        $order->setStatus(OrderStatusEnum::CONFIRMED);
        $order->setAuditStatus(AuditStatusEnum::APPROVED);
        $order->setSource(OrderSourceEnum::MANUAL_INPUT);
        $order->setTotalAmount('100.00');
        $order->setCreateTime(new \DateTimeImmutable('2024-01-15'));
        $em->persist($order);

        $bill = new AgentBill();
        $bill->setAgent($agent);
        $bill->setBillMonth('2024-01');
        $bill->setStatus(BillStatusEnum::PENDING);
        $bill->setOrderCount(1);
        $bill->setTotalAmount('100.00');
        $bill->setCommissionAmount('10.00');
        $bill->setCommissionRate('0.10');
        $em->persist($bill);
        $em->flush();

        // 添加新订单
        $order2 = new Order();
        $order2->setOrderNo('ORD_RECALC_002');
        $order2->setAgent($agent);
        $order2->setStatus(OrderStatusEnum::CONFIRMED);
        $order2->setAuditStatus(AuditStatusEnum::APPROVED);
        $order2->setSource(OrderSourceEnum::MANUAL_INPUT);
        $order2->setTotalAmount('200.00');
        $order2->setCreateTime(new \DateTimeImmutable('2024-01-20'));
        $em->persist($order2);
        $em->flush();

        // 重新计算账单
        $updatedBill = $service->recalculateBill($bill);
        $this->assertSame($bill->getId(), $updatedBill->getId());
        $this->assertSame(2, $updatedBill->getOrderCount());
        $this->assertSame('300.00', $updatedBill->getTotalAmount());

        // 测试已支付账单不能重新计算
        $paidBill = new AgentBill();
        $paidBill->setAgent($agent);
        $paidBill->setBillMonth('2024-02');
        $paidBill->setStatus(BillStatusEnum::PAID);
        $paidBill->setOrderCount(1);
        $paidBill->setTotalAmount('100.00');
        $paidBill->setCommissionAmount('10.00');
        $paidBill->setCommissionRate('0.10');
        $paidBill->setConfirmTime(new \DateTimeImmutable());
        $paidBill->setPayTime(new \DateTimeImmutable());
        $em->persist($paidBill);
        $em->flush();

        $this->expectException(AgentBillException::class);
        $this->expectExceptionMessage('已支付的账单不能重新计算');
        $service->recalculateBill($paidBill);
    }

    private function createAgent(string $code): Agent
    {
        $agent = new Agent();
        $agent->setCode($code);
        $agent->setCompanyName('Test Company ' . $code);
        $agent->setContactPerson('Contact ' . $code);
        $agent->setPhone('138' . sprintf('%08d', rand(10000000, 99999999)));
        $agent->setCommissionRate('0.10');
        $agent->setLevel(AgentLevelEnum::A);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        return $agent;
    }
}
