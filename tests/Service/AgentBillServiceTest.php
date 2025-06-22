<?php

namespace Tourze\HotelAgentBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Entity\OrderItem;
use Tourze\HotelAgentBundle\Enum\AgentLevelEnum;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;
use Tourze\HotelAgentBundle\Enum\OrderStatusEnum;
use Tourze\HotelAgentBundle\Repository\AgentBillRepository;
use Tourze\HotelAgentBundle\Repository\AgentRepository;
use Tourze\HotelAgentBundle\Repository\OrderRepository;
use Tourze\HotelAgentBundle\Service\AgentBillService;
use Tourze\HotelAgentBundle\Service\BillAuditService;

class AgentBillServiceTest extends TestCase
{
    private EntityManagerInterface|MockObject $entityManager;
    private AgentBillRepository|MockObject $agentBillRepository;
    private OrderRepository|MockObject $orderRepository;
    private AgentRepository|MockObject $agentRepository;
    private LoggerInterface|MockObject $logger;
    private BillAuditService|MockObject $billAuditService;
    private AgentBillService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->agentBillRepository = $this->createMock(AgentBillRepository::class);
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->agentRepository = $this->createMock(AgentRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->billAuditService = $this->createMock(BillAuditService::class);

        $this->service = new AgentBillService(
            $this->entityManager,
            $this->agentBillRepository,
            $this->orderRepository,
            $this->agentRepository,
            $this->logger,
            $this->billAuditService
        );
    }

    public function test_generateMonthlyBills_with_no_agents(): void
    {
        $billMonth = '2025-01';

        $this->setupAgentRepository([]);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->service->generateMonthlyBills($billMonth);

        $this->assertSame([], $result);
    }

    public function test_generateMonthlyBills_skips_existing_bills_without_force(): void
    {
        $billMonth = '2025-01';
        $agent = $this->createMockAgent();
        $existingBill = new AgentBill();

        $this->setupAgentRepository([$agent]);

        $this->agentBillRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['agent' => $agent, 'billMonth' => $billMonth])
            ->willReturn($existingBill);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('代理账单已存在', $this->anything());

        $result = $this->service->generateMonthlyBills($billMonth, false);

        $this->assertSame([], $result);
    }

    public function test_generateMonthlyBills_removes_existing_bills_with_force(): void
    {
        $billMonth = '2025-01';
        $agent = $this->createMockAgent();
        $existingBill = new AgentBill();

        $this->setupAgentRepository([$agent]);
        $this->setupOrderRepository($agent, []);

        $this->agentBillRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['agent' => $agent, 'billMonth' => $billMonth])
            ->willReturn($existingBill);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($existingBill);

        $this->billAuditService->expects($this->once())
            ->method('logAuditAction')
            ->with($existingBill, '强制重新生成', '删除旧账单并重新生成');

        $result = $this->service->generateMonthlyBills($billMonth, true);

        $this->assertSame([], $result);
    }

    public function test_generateAgentBill_with_no_orders_returns_null(): void
    {
        $agent = $this->createMockAgent();
        $billMonth = '2025-01';
        $startDate = new \DateTime('2025-01-01 00:00:00');
        $endDate = new \DateTime('2025-01-31 23:59:59');

        $this->setupOrderRepository($agent, []);

        $result = $this->service->generateAgentBill($agent, $billMonth, $startDate, $endDate);

        $this->assertNull($result);
    }

    public function test_generateAgentBill_with_orders_creates_bill(): void
    {
        $agent = $this->createMockAgent();
        $billMonth = '2025-01';
        $startDate = new \DateTime('2025-01-01 00:00:00');
        $endDate = new \DateTime('2025-01-31 23:59:59');

        $orders = [$this->createMockOrder($agent, '1000.00')];
        $this->setupOrderRepository($agent, $orders);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(AgentBill::class));

        $result = $this->service->generateAgentBill($agent, $billMonth, $startDate, $endDate);

        $this->assertInstanceOf(AgentBill::class, $result);
        $this->assertSame($agent, $result->getAgent());
        $this->assertSame($billMonth, $result->getBillMonth());
        $this->assertSame(1, $result->getOrderCount());
        $this->assertSame(BillStatusEnum::PENDING, $result->getStatus());
    }

    public function test_confirmBill_with_pending_status_succeeds(): void
    {
        $bill = new AgentBill();
        $bill->setStatus(BillStatusEnum::PENDING);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->billAuditService->expects($this->once())
            ->method('logStatusChange')
            ->with($bill, BillStatusEnum::PENDING, BillStatusEnum::CONFIRMED, null);

        $result = $this->service->confirmBill($bill);

        $this->assertTrue($result);
        $this->assertSame(BillStatusEnum::CONFIRMED, $bill->getStatus());
        $this->assertInstanceOf(\DateTimeInterface::class, $bill->getConfirmTime());
    }

    public function test_confirmBill_with_non_pending_status_fails(): void
    {
        $bill = new AgentBill();
        $bill->setStatus(BillStatusEnum::CONFIRMED);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('账单状态不正确，无法确认', $this->anything());

        $result = $this->service->confirmBill($bill);

        $this->assertFalse($result);
        $this->assertSame(BillStatusEnum::CONFIRMED, $bill->getStatus());
    }

    public function test_recalculateBill_throws_exception_for_paid_bill(): void
    {
        $bill = new AgentBill();
        $bill->setStatus(BillStatusEnum::PAID);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('已支付的账单不能重新计算');

        $this->service->recalculateBill($bill);
    }

    public function test_recalculateBill_updates_bill_data(): void
    {
        $agent = $this->createMockAgent();
        $bill = new AgentBill();
        $bill->setAgent($agent)
            ->setBillMonth('2025-01')
            ->setStatus(BillStatusEnum::PENDING)
            ->setOrderCount(5)
            ->setTotalAmount('500.00')
            ->setCommissionAmount('25.00');

        $orders = [$this->createMockOrder($agent, '1000.00')];
        $this->setupOrderRepository($agent, $orders);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->billAuditService->expects($this->once())
            ->method('logRecalculation')
            ->with($bill, $this->anything(), $this->anything(), null);

        $result = $this->service->recalculateBill($bill);

        $this->assertSame($bill, $result);
        $this->assertSame(1, $bill->getOrderCount());
    }

    public function test_markBillAsPaid_with_confirmed_status_succeeds(): void
    {
        $bill = new AgentBill();
        $bill->setStatus(BillStatusEnum::CONFIRMED);
        $reference = 'PAY20250101001';

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->billAuditService->expects($this->once())
            ->method('logStatusChange')
            ->with($bill, BillStatusEnum::CONFIRMED, BillStatusEnum::PAID, null);

        $result = $this->service->markBillAsPaid($bill, $reference);

        $this->assertTrue($result);
        $this->assertSame(BillStatusEnum::PAID, $bill->getStatus());
        $this->assertSame($reference, $bill->getPaymentReference());
        $this->assertInstanceOf(\DateTimeInterface::class, $bill->getPayTime());
    }

    public function test_markBillAsPaid_with_non_confirmed_status_fails(): void
    {
        $bill = new AgentBill();
        $bill->setStatus(BillStatusEnum::PENDING);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('只有已确认的账单才能标记为已支付', $this->anything());

        $result = $this->service->markBillAsPaid($bill, 'PAY001');

        $this->assertFalse($result);
        $this->assertSame(BillStatusEnum::PENDING, $bill->getStatus());
    }

    public function test_getAgentBills_with_filters(): void
    {
        $agent = $this->createMockAgent();
        $status = BillStatusEnum::CONFIRMED;
        $billMonth = '2025-01';

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->agentBillRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('ab')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->exactly(3))
            ->method('andWhere')
            ->willReturnSelf();
        $queryBuilder->expects($this->exactly(3))
            ->method('setParameter')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('addSelect')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setFirstResult')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->willReturnSelf();

        $query = $this->createMock(Query::class);
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $result = $this->service->getAgentBills($agent, $status, $billMonth, 2, 10);

        $this->assertSame([], $result);
    }

    public function test_getBillStatistics_returns_grouped_data(): void
    {
        $billMonth = '2025-01';
        $expectedData = [
            ['status' => 'pending', 'bill_count' => 5, 'total_amount' => '5000.00', 'total_commission' => '250.00'],
            ['status' => 'confirmed', 'bill_count' => 3, 'total_amount' => '3000.00', 'total_commission' => '150.00'],
        ];

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->agentBillRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('ab')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('select')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('groupBy')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedData);

        $result = $this->service->getBillStatistics($billMonth);

        $this->assertSame($expectedData, $result);
    }

    public function test_getDetailedBillReport_calculates_totals(): void
    {
        $startDate = new \DateTime('2025-01-01');
        $endDate = new \DateTime('2025-01-31');

        $agent = $this->createMockAgent();
        $bill1 = new AgentBill();
        $bill1->setAgent($agent)
            ->setBillMonth('2025-01')
            ->setStatus(BillStatusEnum::CONFIRMED)
            ->setTotalAmount('1000.00')
            ->setCommissionAmount('100.00');

        $bill2 = new AgentBill();
        $bill2->setAgent($agent)
            ->setBillMonth('2025-01')
            ->setStatus(BillStatusEnum::PAID)
            ->setTotalAmount('2000.00')
            ->setCommissionAmount('200.00');

        $bills = [$bill1, $bill2];

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->agentBillRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('ab')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->willReturnSelf();
        $queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('addSelect')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($bills);

        $result = $this->service->getDetailedBillReport($startDate, $endDate);

        $this->assertSame(2, $result['total_bills']);
        $this->assertSame('3000.00', $result['total_amount']);
        $this->assertSame('300.00', $result['total_commission']);
        $this->assertArrayHasKey('status_summary', $result);
        $this->assertArrayHasKey('agent_summary', $result);
        $this->assertArrayHasKey('monthly_summary', $result);
    }

    private function createMockAgent(): Agent
    {
        $agent = new Agent();
        $agent->setCode('AGT001')
            ->setCompanyName('测试公司')
            ->setLevel(AgentLevelEnum::A)
            ->setCommissionRate('0.10');
        return $agent;
    }

    private function createMockOrder(Agent $agent, string $totalAmount): Order
    {
        $order = new Order();
        $order->setAgent($agent)
            ->setOrderNo('ORD001')
            ->setTotalAmount($totalAmount)
            ->setStatus(OrderStatusEnum::CONFIRMED);

        $orderItem = new OrderItem();
        $orderItem->setAmount('500.00')
            ->setCostPrice('400.00');
        $order->addOrderItem($orderItem);

        return $order;
    }

    private function setupAgentRepository(array $agents): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->agentRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($agents);
    }

    private function setupOrderRepository(Agent $agent, array $orders): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->orderRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->exactly(3))
            ->method('andWhere')
            ->willReturnSelf();
        $queryBuilder->expects($this->exactly(4))
            ->method('setParameter')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($orders);
    }
}
