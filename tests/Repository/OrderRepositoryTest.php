<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Enum\AgentLevelEnum;
use Tourze\HotelAgentBundle\Enum\AgentStatusEnum;
use Tourze\HotelAgentBundle\Enum\AuditStatusEnum;
use Tourze\HotelAgentBundle\Enum\OrderSourceEnum;
use Tourze\HotelAgentBundle\Enum\OrderStatusEnum;
use Tourze\HotelAgentBundle\Repository\OrderRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(OrderRepository::class)]
#[RunTestsInSeparateProcesses]
final class OrderRepositoryTest extends AbstractRepositoryTestCase
{
    private OrderRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(OrderRepository::class);
    }

    public function testFindByOrderNo(): void
    {
        $order = new Order();
        $order->setOrderNo('ORD001');
        $agent = new Agent();
        $agent->setCode('TEST001');
        $agent->setCompanyName('Test Company');
        $agent->setContactPerson('Test Contact');
        $agent->setPhone('13800138000');
        $agent->setLevel(AgentLevelEnum::A);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);
        $order->setAgent($agent);
        $order->setStatus(OrderStatusEnum::PENDING);
        $order->setAuditStatus(AuditStatusEnum::PENDING);
        $order->setSource(OrderSourceEnum::MANUAL_INPUT);
        self::getEntityManager()->persist($order);
        self::getEntityManager()->flush();

        $result = $this->repository->findByOrderNo('ORD001');
        $this->assertInstanceOf(Order::class, $result);
        $this->assertSame($order->getId(), $result->getId());

        $notFound = $this->repository->findByOrderNo('NONEXISTENT');
        $this->assertNull($notFound);
    }

    public function testFindByAgentId(): void
    {
        $agent = new Agent();
        $agent->setCode('AGENT001');
        $agent->setCompanyName('Test Company');
        $agent->setContactPerson('Test Contact');
        $agent->setPhone('13800138000');
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $order1 = new Order();
        $order1->setOrderNo('ORD002');
        $agent1 = new Agent();
        $agent1->setCode('TEST002');
        $agent1->setCompanyName('Test Company 2');
        $agent1->setContactPerson('Test Contact');
        $agent1->setPhone('13800138000');
        $agent1->setLevel(AgentLevelEnum::A);
        $agent1->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent1);
        $order1->setAgent($agent1);
        $order1->setAgent($agent);
        $order1->setStatus(OrderStatusEnum::PENDING);
        $order1->setAuditStatus(AuditStatusEnum::PENDING);
        $order1->setSource(OrderSourceEnum::MANUAL_INPUT);
        self::getEntityManager()->persist($order1);

        $order2 = new Order();
        $order2->setOrderNo('ORD003');
        $agent2 = new Agent();
        $agent2->setCode('TEST003');
        $agent2->setCompanyName('Test Company 3');
        $agent2->setContactPerson('Test Contact');
        $agent2->setPhone('13800138000');
        $agent2->setLevel(AgentLevelEnum::A);
        $agent2->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent2);
        $order2->setAgent($agent2);
        $order2->setAgent($agent);
        $order2->setStatus(OrderStatusEnum::CONFIRMED);
        $order2->setAuditStatus(AuditStatusEnum::APPROVED);
        $order2->setSource(OrderSourceEnum::MANUAL_INPUT);
        self::getEntityManager()->persist($order2);

        self::getEntityManager()->flush();

        $agentId = $agent->getId();
        $this->assertNotNull($agentId);
        $results = $this->repository->findByAgentId($agentId);
        $this->assertCount(2, $results);
        $this->assertInstanceOf(Order::class, $results[0]);
        $this->assertInstanceOf(Order::class, $results[1]);
    }

    public function testFindByStatus(): void
    {
        $order1 = new Order();
        $order1->setOrderNo('ORD004');
        $agent3 = new Agent();
        $agent3->setCode('TEST004');
        $agent3->setCompanyName('Test Company 4');
        $agent3->setContactPerson('Test Contact');
        $agent3->setPhone('13800138000');
        $agent3->setLevel(AgentLevelEnum::A);
        $agent3->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent3);
        $order1->setAgent($agent3);
        $order1->setStatus(OrderStatusEnum::PENDING);
        $order1->setAuditStatus(AuditStatusEnum::PENDING);
        $order1->setSource(OrderSourceEnum::MANUAL_INPUT);
        self::getEntityManager()->persist($order1);

        $order2 = new Order();
        $order2->setOrderNo('ORD005');
        $agent4 = new Agent();
        $agent4->setCode('TEST005');
        $agent4->setCompanyName('Test Company 5');
        $agent4->setContactPerson('Test Contact');
        $agent4->setPhone('13800138000');
        $agent4->setLevel(AgentLevelEnum::A);
        $agent4->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent4);
        $order2->setAgent($agent4);
        $order2->setStatus(OrderStatusEnum::CONFIRMED);
        $order2->setAuditStatus(AuditStatusEnum::APPROVED);
        $order2->setSource(OrderSourceEnum::MANUAL_INPUT);
        self::getEntityManager()->persist($order2);

        self::getEntityManager()->flush();

        $pendingOrders = $this->repository->findByStatus(OrderStatusEnum::PENDING);
        $this->assertGreaterThanOrEqual(1, count($pendingOrders));

        $confirmedOrders = $this->repository->findByStatus(OrderStatusEnum::CONFIRMED);
        $this->assertGreaterThanOrEqual(1, count($confirmedOrders));
    }

    public function testFindByAuditStatus(): void
    {
        $agent5 = new Agent();
        $agent5->setCode('TEST006');
        $agent5->setCompanyName('Test Company 6');
        $agent5->setContactPerson('Guest 5');
        $agent5->setPhone('13800138005');
        $agent5->setLevel(AgentLevelEnum::A);
        $agent5->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent5);

        $order1 = new Order();
        $order1->setOrderNo('ORD006');
        $order1->setAgent($agent5);
        $order1->setStatus(OrderStatusEnum::PENDING);
        $order1->setAuditStatus(AuditStatusEnum::PENDING);
        $order1->setSource(OrderSourceEnum::MANUAL_INPUT);
        self::getEntityManager()->persist($order1);

        $agent6 = new Agent();
        $agent6->setCode('TEST007');
        $agent6->setCompanyName('Test Company 7');
        $agent6->setContactPerson('Guest 6');
        $agent6->setPhone('13800138006');
        $agent6->setLevel(AgentLevelEnum::A);
        $agent6->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent6);

        $order2 = new Order();
        $order2->setOrderNo('ORD007');
        $order2->setAgent($agent6);
        $order2->setStatus(OrderStatusEnum::CONFIRMED);
        $order2->setAuditStatus(AuditStatusEnum::APPROVED);
        $order2->setSource(OrderSourceEnum::MANUAL_INPUT);
        self::getEntityManager()->persist($order2);

        self::getEntityManager()->flush();

        $pendingAuditOrders = $this->repository->findByAuditStatus(AuditStatusEnum::PENDING);
        $this->assertGreaterThanOrEqual(1, count($pendingAuditOrders));

        $approvedOrders = $this->repository->findByAuditStatus(AuditStatusEnum::APPROVED);
        $this->assertGreaterThanOrEqual(1, count($approvedOrders));
    }

    public function testFindBySource(): void
    {
        $agent7 = new Agent();
        $agent7->setCode('TEST008');
        $agent7->setCompanyName('Test Company 8');
        $agent7->setContactPerson('Guest 7');
        $agent7->setPhone('13800138007');
        $agent7->setLevel(AgentLevelEnum::B);
        $agent7->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent7);

        $order1 = new Order();
        $order1->setOrderNo('ORD008');
        $order1->setAgent($agent7);
        $order1->setStatus(OrderStatusEnum::PENDING);
        $order1->setAuditStatus(AuditStatusEnum::PENDING);
        $order1->setSource(OrderSourceEnum::MANUAL_INPUT);
        self::getEntityManager()->persist($order1);

        $agent8 = new Agent();
        $agent8->setCode('TEST009');
        $agent8->setCompanyName('Test Company 9');
        $agent8->setContactPerson('Guest 8');
        $agent8->setPhone('13800138008');
        $agent8->setLevel(AgentLevelEnum::B);
        $agent8->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent8);

        $order2 = new Order();
        $order2->setOrderNo('ORD009');
        $order2->setAgent($agent8);
        $order2->setStatus(OrderStatusEnum::PENDING);
        $order2->setAuditStatus(AuditStatusEnum::PENDING);
        $order2->setSource(OrderSourceEnum::EXCEL_IMPORT);
        self::getEntityManager()->persist($order2);

        self::getEntityManager()->flush();

        $agentOrders = $this->repository->findBySource(OrderSourceEnum::MANUAL_INPUT);
        $this->assertGreaterThanOrEqual(1, count($agentOrders));

        $apiOrders = $this->repository->findBySource(OrderSourceEnum::EXCEL_IMPORT);
        $this->assertGreaterThanOrEqual(1, count($apiOrders));
    }

    public function testFindComplexOrders(): void
    {
        $agent9 = new Agent();
        $agent9->setCode('TEST010');
        $agent9->setCompanyName('Test Company 10');
        $agent9->setContactPerson('Guest 9');
        $agent9->setPhone('13800138009');
        $agent9->setLevel(AgentLevelEnum::C);
        $agent9->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent9);

        $order1 = new Order();
        $order1->setOrderNo('ORD010');
        $order1->setAgent($agent9);
        $order1->setStatus(OrderStatusEnum::PENDING);
        $order1->setAuditStatus(AuditStatusEnum::PENDING);
        $order1->setSource(OrderSourceEnum::MANUAL_INPUT);
        $order1->setIsComplex(true);
        self::getEntityManager()->persist($order1);

        $agent10 = new Agent();
        $agent10->setCode('TEST011');
        $agent10->setCompanyName('Test Company 11');
        $agent10->setContactPerson('Guest 10');
        $agent10->setPhone('13800138010');
        $agent10->setLevel(AgentLevelEnum::C);
        $agent10->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent10);

        $order2 = new Order();
        $order2->setOrderNo('ORD011');
        $order2->setAgent($agent10);
        $order2->setStatus(OrderStatusEnum::PENDING);
        $order2->setAuditStatus(AuditStatusEnum::PENDING);
        $order2->setSource(OrderSourceEnum::MANUAL_INPUT);
        $order2->setIsComplex(false);
        self::getEntityManager()->persist($order2);

        self::getEntityManager()->flush();

        $complexOrders = $this->repository->findComplexOrders();
        $this->assertGreaterThanOrEqual(1, count($complexOrders));

        $foundComplexOrder = false;
        $foundSimpleOrder = false;
        foreach ($complexOrders as $order) {
            if ($order->getId() === $order1->getId()) {
                $foundComplexOrder = true;
            }
            if ($order->getId() === $order2->getId()) {
                $foundSimpleOrder = true;
            }
        }
        $this->assertTrue($foundComplexOrder);
        $this->assertFalse($foundSimpleOrder);
    }

    public function testFindByDateRange(): void
    {
        $startDate = new \DateTimeImmutable('2023-01-01 00:00:00');
        $endDate = new \DateTimeImmutable('2023-01-02 23:59:59');
        $inRangeDate = new \DateTimeImmutable('2023-01-01 12:00:00');
        $outOfRangeDate = new \DateTimeImmutable('2023-01-03 12:00:00');

        $agent11 = new Agent();
        $agent11->setCode('TEST012');
        $agent11->setCompanyName('Test Company 12');
        $agent11->setContactPerson('Guest 11');
        $agent11->setPhone('13800138011');
        $agent11->setLevel(AgentLevelEnum::A);
        $agent11->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent11);

        $order1 = new Order();
        $order1->setOrderNo('ORD012');
        $order1->setAgent($agent11);
        $order1->setStatus(OrderStatusEnum::PENDING);
        $order1->setAuditStatus(AuditStatusEnum::PENDING);
        $order1->setSource(OrderSourceEnum::MANUAL_INPUT);
        $order1->setCreateTime($inRangeDate);
        self::getEntityManager()->persist($order1);

        $agent12 = new Agent();
        $agent12->setCode('TEST013');
        $agent12->setCompanyName('Test Company 13');
        $agent12->setContactPerson('Guest 12');
        $agent12->setPhone('13800138012');
        $agent12->setLevel(AgentLevelEnum::B);
        $agent12->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent12);

        $order2 = new Order();
        $order2->setOrderNo('ORD013');
        $order2->setAgent($agent12);
        $order2->setStatus(OrderStatusEnum::PENDING);
        $order2->setAuditStatus(AuditStatusEnum::PENDING);
        $order2->setSource(OrderSourceEnum::MANUAL_INPUT);
        $order2->setCreateTime($outOfRangeDate);
        self::getEntityManager()->persist($order2);

        self::getEntityManager()->flush();

        $results = $this->repository->findByDateRange($startDate, $endDate);

        $foundInRangeOrder = false;
        $foundOutOfRangeOrder = false;
        foreach ($results as $order) {
            if ($order->getId() === $order1->getId()) {
                $foundInRangeOrder = true;
            }
            if ($order->getId() === $order2->getId()) {
                $foundOutOfRangeOrder = true;
            }
        }

        $this->assertGreaterThanOrEqual(1, count($results), 'Should find at least one order in date range');
        $this->assertTrue($foundInRangeOrder, 'Should find the in-range order');
        $this->assertFalse($foundOutOfRangeOrder, 'Should not find the out-of-range order');
    }

    public function testFindOrdersRequiringAudit(): void
    {
        $agent13 = new Agent();
        $agent13->setCode('TEST014');
        $agent13->setCompanyName('Test Company 14');
        $agent13->setContactPerson('Guest 13');
        $agent13->setPhone('13800138013');
        $agent13->setLevel(AgentLevelEnum::C);
        $agent13->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent13);

        $order = new Order();
        $order->setOrderNo('ORD014');
        $order->setAgent($agent13);
        $order->setStatus(OrderStatusEnum::PENDING);
        $order->setAuditStatus(AuditStatusEnum::RISK_REVIEW);
        $order->setSource(OrderSourceEnum::MANUAL_INPUT);
        self::getEntityManager()->persist($order);

        self::getEntityManager()->flush();

        $results = $this->repository->findOrdersRequiringAudit();
        $this->assertGreaterThanOrEqual(1, count($results));

        $found = false;
        foreach ($results as $result) {
            if ($result->getId() === $order->getId()) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testFindRecentOrdersByAgentId(): void
    {
        $agent = new Agent();
        $agent->setCode('AGENT002');
        $agent->setCompanyName('Test Company 2');
        $agent->setContactPerson('Test Contact');
        $agent->setPhone('13800138000');
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        for ($i = 1; $i <= 15; ++$i) {
            $order = new Order();
            $order->setOrderNo('ORD_RECENT_' . $i);
            $order->setAgent($agent);
            $order->setStatus(OrderStatusEnum::PENDING);
            $order->setAuditStatus(AuditStatusEnum::PENDING);
            $order->setSource(OrderSourceEnum::MANUAL_INPUT);
            self::getEntityManager()->persist($order);
        }

        self::getEntityManager()->flush();

        $agentId = $agent->getId();
        $this->assertNotNull($agentId);
        $results = $this->repository->findRecentOrdersByAgentId($agentId, 5);
        $this->assertCount(5, $results);
        $this->assertInstanceOf(Order::class, $results[0]);
    }

    public function testFindExpiredPendingOrders(): void
    {
        $expireDate = new \DateTimeImmutable('-1 hour');
        $recentDate = new \DateTimeImmutable('-30 minutes');
        $oldDate = new \DateTimeImmutable('-2 hours');

        $expiredAgent = new Agent();
        $expiredAgent->setCode('TEST015');
        $expiredAgent->setCompanyName('Expired Company');
        $expiredAgent->setContactPerson('Expired Guest');
        $expiredAgent->setPhone('13800138015');
        $expiredAgent->setLevel(AgentLevelEnum::A);
        $expiredAgent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($expiredAgent);

        $expiredOrder = new Order();
        $expiredOrder->setOrderNo('ORD015');
        $expiredOrder->setAgent($expiredAgent);
        $expiredOrder->setStatus(OrderStatusEnum::PENDING);
        $expiredOrder->setAuditStatus(AuditStatusEnum::PENDING);
        $expiredOrder->setSource(OrderSourceEnum::MANUAL_INPUT);
        $expiredOrder->setCreateTime($oldDate);
        self::getEntityManager()->persist($expiredOrder);

        $recentAgent = new Agent();
        $recentAgent->setCode('TEST016');
        $recentAgent->setCompanyName('Recent Company');
        $recentAgent->setContactPerson('Recent Guest');
        $recentAgent->setPhone('13800138016');
        $recentAgent->setLevel(AgentLevelEnum::B);
        $recentAgent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($recentAgent);

        $recentOrder = new Order();
        $recentOrder->setOrderNo('ORD016');
        $recentOrder->setAgent($recentAgent);
        $recentOrder->setStatus(OrderStatusEnum::PENDING);
        $recentOrder->setAuditStatus(AuditStatusEnum::PENDING);
        $recentOrder->setSource(OrderSourceEnum::MANUAL_INPUT);
        $recentOrder->setCreateTime($recentDate);
        self::getEntityManager()->persist($recentOrder);

        self::getEntityManager()->flush();

        $results = $this->repository->findExpiredPendingOrders($expireDate);

        $foundExpiredOrder = false;
        $foundRecentOrder = false;
        foreach ($results as $order) {
            if ($order->getId() === $expiredOrder->getId()) {
                $foundExpiredOrder = true;
            }
            if ($order->getId() === $recentOrder->getId()) {
                $foundRecentOrder = true;
            }
        }
        $this->assertTrue($foundExpiredOrder);
        $this->assertFalse($foundRecentOrder);
    }

    public function testFindByAgentIdWithEmptyResult(): void
    {
        $results = $this->repository->findByAgentId(99999);
        $this->assertSame([], $results);
    }

    public function testFindRecentOrdersByAgentIdWithEmptyResult(): void
    {
        $results = $this->repository->findRecentOrdersByAgentId(99999, 10);
        $this->assertSame([], $results);
    }

    public function testFindWithNonExistentId(): void
    {
        $result = $this->repository->find(99999);
        $this->assertNull($result);
    }

    public function testFindOneByWithNonExistentCriteria(): void
    {
        $result = $this->repository->findOneBy(['orderNo' => 'NONEXISTENT']);
        $this->assertNull($result);

        $statusResult = $this->repository->findOneBy(['status' => OrderStatusEnum::CLOSED]);
        $this->assertNull($statusResult);
    }

    public function testSaveMethodShouldPersistEntity(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_SAVE');
        $agent->setCompanyName('Test Company Save');
        $agent->setContactPerson('Test Contact');
        $agent->setPhone('13800138000');
        $agent->setLevel(AgentLevelEnum::A);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $order = new Order();
        $order->setOrderNo('ORD_SAVE');
        $order->setAgent($agent);
        $order->setStatus(OrderStatusEnum::CONFIRMED);
        $order->setAuditStatus(AuditStatusEnum::APPROVED);
        $order->setSource(OrderSourceEnum::EXCEL_IMPORT);

        $this->repository->save($order);

        $savedOrder = $this->repository->findByOrderNo('ORD_SAVE');
        $this->assertInstanceOf(Order::class, $savedOrder);
        $this->assertSame($agent->getId(), $savedOrder->getAgent()?->getId());
        $this->assertSame(OrderStatusEnum::CONFIRMED, $savedOrder->getStatus());
    }

    public function testSaveMethodWithoutFlush(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_SAVE_NO_FLUSH');
        $agent->setCompanyName('Test Company Save No Flush');
        $agent->setContactPerson('Test Contact');
        $agent->setPhone('13800138000');
        $agent->setLevel(AgentLevelEnum::B);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $order = new Order();
        $order->setOrderNo('ORD_SAVE_NO_FLUSH');
        $order->setAgent($agent);
        $order->setStatus(OrderStatusEnum::PENDING);
        $order->setAuditStatus(AuditStatusEnum::PENDING);
        $order->setSource(OrderSourceEnum::MANUAL_INPUT);

        $this->repository->save($order, false);
        self::getEntityManager()->flush();

        $savedOrder = $this->repository->findByOrderNo('ORD_SAVE_NO_FLUSH');
        $this->assertInstanceOf(Order::class, $savedOrder);
        $this->assertSame(OrderStatusEnum::PENDING, $savedOrder->getStatus());
    }

    public function testRemoveMethodShouldDeleteEntity(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_REMOVE');
        $agent->setCompanyName('Test Company Remove');
        $agent->setContactPerson('Test Contact');
        $agent->setPhone('13800138000');
        $agent->setLevel(AgentLevelEnum::C);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);

        $order = new Order();
        $order->setOrderNo('ORD_REMOVE');
        $order->setAgent($agent);
        $order->setStatus(OrderStatusEnum::PENDING);
        $order->setAuditStatus(AuditStatusEnum::PENDING);
        $order->setSource(OrderSourceEnum::MANUAL_INPUT);
        self::getEntityManager()->persist($order);
        self::getEntityManager()->flush();

        $savedOrder = $this->repository->findByOrderNo('ORD_REMOVE');
        $this->assertInstanceOf(Order::class, $savedOrder);

        $this->repository->remove($savedOrder);

        $deletedOrder = $this->repository->findByOrderNo('ORD_REMOVE');
        $this->assertNull($deletedOrder);
    }

    public function testRemoveMethodWithoutFlush(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_REMOVE_NO_FLUSH');
        $agent->setCompanyName('Test Company Remove No Flush');
        $agent->setContactPerson('Test Contact');
        $agent->setPhone('13800138000');
        $agent->setLevel(AgentLevelEnum::A);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);

        $order = new Order();
        $order->setOrderNo('ORD_REMOVE_NO_FLUSH');
        $order->setAgent($agent);
        $order->setStatus(OrderStatusEnum::CONFIRMED);
        $order->setAuditStatus(AuditStatusEnum::APPROVED);
        $order->setSource(OrderSourceEnum::EXCEL_IMPORT);
        self::getEntityManager()->persist($order);
        self::getEntityManager()->flush();

        $savedOrder = $this->repository->findByOrderNo('ORD_REMOVE_NO_FLUSH');
        $this->assertInstanceOf(Order::class, $savedOrder);

        $this->repository->remove($savedOrder, false);
        self::getEntityManager()->flush();

        $deletedOrder = $this->repository->findByOrderNo('ORD_REMOVE_NO_FLUSH');
        $this->assertNull($deletedOrder);
    }

    public function testCountWithCriteria(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_COUNT');
        $agent->setCompanyName('Test Company Count');
        $agent->setContactPerson('Test Contact');
        $agent->setPhone('13800138000');
        $agent->setLevel(AgentLevelEnum::B);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $initialCount = $this->repository->count(['agent' => $agent]);

        $order1 = new Order();
        $order1->setOrderNo('ORD_COUNT_1');
        $order1->setAgent($agent);
        $order1->setStatus(OrderStatusEnum::CONFIRMED);
        $order1->setAuditStatus(AuditStatusEnum::APPROVED);
        $order1->setSource(OrderSourceEnum::MANUAL_INPUT);
        self::getEntityManager()->persist($order1);

        $order2 = new Order();
        $order2->setOrderNo('ORD_COUNT_2');
        $order2->setAgent($agent);
        $order2->setStatus(OrderStatusEnum::CONFIRMED);
        $order2->setAuditStatus(AuditStatusEnum::APPROVED);
        $order2->setSource(OrderSourceEnum::EXCEL_IMPORT);
        self::getEntityManager()->persist($order2);

        self::getEntityManager()->flush();

        $finalCount = $this->repository->count(['agent' => $agent]);
        $this->assertSame($initialCount + 2, $finalCount);

        $confirmedCount = $this->repository->count(['status' => OrderStatusEnum::CONFIRMED]);
        $this->assertGreaterThanOrEqual(2, $confirmedCount);
    }

    public function testFindByNullableFields(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_NULL_FIELDS');
        $agent->setCompanyName('Test Company Null Fields');
        $agent->setContactPerson('Test Contact');
        $agent->setPhone('13800138000');
        $agent->setLevel(AgentLevelEnum::C);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);

        $order = new Order();
        $order->setOrderNo('ORD_NULL_FIELDS');
        $order->setAgent($agent);
        $order->setStatus(OrderStatusEnum::PENDING);
        $order->setAuditStatus(AuditStatusEnum::PENDING);
        $order->setSource(OrderSourceEnum::MANUAL_INPUT);
        $order->setRemark(null);
        $order->setCancelReason(null);
        $order->setAuditRemark(null);
        $order->setImportFile(null);
        self::getEntityManager()->persist($order);
        self::getEntityManager()->flush();

        $results = $this->repository->findBy(['agent' => $agent]);
        $this->assertGreaterThanOrEqual(1, count($results));

        $resultWithNullFields = false;
        foreach ($results as $result) {
            if (null === $result->getRemark() && null === $result->getCancelReason()) {
                $resultWithNullFields = true;
                break;
            }
        }
        $this->assertTrue($resultWithNullFields);
    }

    public function testFindOneByAssociationAgentShouldReturnMatchingEntity(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_FIND_ONE_BY_AGENT');
        $agent->setCompanyName('Test Company Find One By Agent');
        $agent->setContactPerson('Test Contact');
        $agent->setPhone('13800138000');
        $agent->setLevel(AgentLevelEnum::A);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $order = new Order();
        $order->setOrderNo('ORD_FIND_ONE_BY_AGENT');
        $order->setAgent($agent);
        $order->setStatus(OrderStatusEnum::PENDING);
        $order->setAuditStatus(AuditStatusEnum::PENDING);
        $order->setSource(OrderSourceEnum::MANUAL_INPUT);
        self::getEntityManager()->persist($order);
        self::getEntityManager()->flush();

        $result = $this->repository->findOneBy(['agent' => $agent]);
        $this->assertInstanceOf(Order::class, $result);
        $this->assertSame($agent->getId(), $result->getAgent()?->getId());
        $this->assertSame('ORD_FIND_ONE_BY_AGENT', $result->getOrderNo());
    }

    public function testCountByAssociationAgentShouldReturnCorrectNumber(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_COUNT_AGENT');
        $agent->setCompanyName('Test Company Count Agent');
        $agent->setContactPerson('Test Contact');
        $agent->setPhone('13800138000');
        $agent->setLevel(AgentLevelEnum::B);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $initialCount = $this->repository->count(['agent' => $agent]);

        for ($i = 1; $i <= 4; ++$i) {
            $order = new Order();
            $order->setOrderNo('ORD_COUNT_AGENT_' . $i);
            $order->setAgent($agent);
            $order->setStatus(OrderStatusEnum::PENDING);
            $order->setAuditStatus(AuditStatusEnum::PENDING);
            $order->setSource(OrderSourceEnum::MANUAL_INPUT);
            self::getEntityManager()->persist($order);
        }
        self::getEntityManager()->flush();

        $count = $this->repository->count(['agent' => $agent]);
        $this->assertSame($initialCount + 4, $count);
    }

    /**
     * @return ServiceEntityRepository<Order>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): object
    {
        // 创建必需的 Agent 实体
        $agent = new Agent();
        $agent->setCode('AGENT' . uniqid());
        $agent->setCompanyName('Test Company - ' . uniqid());
        $agent->setContactPerson('Test Contact - ' . uniqid());
        $agent->setPhone('138' . sprintf('%08d', rand(10000000, 99999999)));
        $agent->setEmail('test' . uniqid() . '@example.com');
        $agent->setCommissionRate('10.00');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::C);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        // 创建 Order 实体
        $order = new Order();
        $order->setOrderNo('ORD' . uniqid());
        $order->setAgent($agent);
        $order->setStatus(OrderStatusEnum::PENDING);
        $order->setAuditStatus(AuditStatusEnum::PENDING);
        $order->setSource(OrderSourceEnum::MANUAL_INPUT);

        return $order;
    }
}
