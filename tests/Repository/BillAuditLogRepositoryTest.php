<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Entity\BillAuditLog;
use Tourze\HotelAgentBundle\Enum\AgentLevelEnum;
use Tourze\HotelAgentBundle\Enum\AgentStatusEnum;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;
use Tourze\HotelAgentBundle\Enum\SettlementTypeEnum;
use Tourze\HotelAgentBundle\Repository\BillAuditLogRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(BillAuditLogRepository::class)]
#[RunTestsInSeparateProcesses]
final class BillAuditLogRepositoryTest extends AbstractRepositoryTestCase
{
    private BillAuditLogRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(BillAuditLogRepository::class);
    }

    public function testFindByAgentBill(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST001');
        $agent->setCompanyName('Test Company');
        self::getEntityManager()->persist($agent);

        $agentBill = new AgentBill();
        $agentBill->setAgent($agent);
        $agentBill->setBillMonth('2024-01');
        $agentBill->setTotalAmount('1000.00');
        $agentBill->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($agentBill);

        $auditLog1 = new BillAuditLog();
        $auditLog1->setAgentBill($agentBill);
        $auditLog1->setAction('created');
        $auditLog1->setOperatorName('admin');
        $auditLog1->setChangeDetails(null);
        $auditLog1->setCreateTime(new \DateTimeImmutable('-2 hours'));
        self::getEntityManager()->persist($auditLog1);

        $auditLog2 = new BillAuditLog();
        $auditLog2->setAgentBill($agentBill);
        $auditLog2->setAction('reviewed');
        $auditLog2->setOperatorName('manager');
        $auditLog2->setChangeDetails(null);
        $auditLog2->setCreateTime(new \DateTimeImmutable('-1 hour'));
        self::getEntityManager()->persist($auditLog2);

        self::getEntityManager()->flush();

        $results = $this->repository->findByAgentBill($agentBill);
        $this->assertCount(2, $results);
        $this->assertSame('reviewed', $results[0]->getAction());
        $this->assertSame('created', $results[1]->getAction());
    }

    public function testFindByDateRange(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST002');
        $agent->setCompanyName('Test Company 2');
        self::getEntityManager()->persist($agent);

        $agentBill = new AgentBill();
        $agentBill->setAgent($agent);
        $agentBill->setBillMonth('2024-02');
        $agentBill->setTotalAmount('2000.00');
        $agentBill->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($agentBill);

        $startDate = new \DateTimeImmutable('-3 days');
        $endDate = new \DateTimeImmutable('-1 day');
        $inRangeDate = new \DateTimeImmutable('-2 days');
        $outOfRangeDate = new \DateTimeImmutable('+1 day');

        $inRangeLog = new BillAuditLog();
        $inRangeLog->setAgentBill($agentBill);
        $inRangeLog->setAction('updated');
        $inRangeLog->setOperatorName('admin');
        $inRangeLog->setChangeDetails(null);
        $inRangeLog->setCreateTime($inRangeDate);
        self::getEntityManager()->persist($inRangeLog);

        $outOfRangeLog = new BillAuditLog();
        $outOfRangeLog->setAgentBill($agentBill);
        $outOfRangeLog->setAction('deleted');
        $outOfRangeLog->setOperatorName('admin');
        $outOfRangeLog->setChangeDetails(null);
        $outOfRangeLog->setCreateTime($outOfRangeDate);
        self::getEntityManager()->persist($outOfRangeLog);

        self::getEntityManager()->flush();

        $results = $this->repository->findByDateRange($startDate, $endDate);

        $foundInRangeLog = false;
        $foundOutOfRangeLog = false;
        foreach ($results as $result) {
            if ($result->getId() === $inRangeLog->getId()) {
                $foundInRangeLog = true;
            }
            if ($result->getId() === $outOfRangeLog->getId()) {
                $foundOutOfRangeLog = true;
            }
        }
        $this->assertTrue($foundInRangeLog);
        $this->assertFalse($foundOutOfRangeLog);
    }

    public function testFindByAction(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST003');
        $agent->setCompanyName('Test Company 3');
        self::getEntityManager()->persist($agent);

        $agentBill = new AgentBill();
        $agentBill->setAgent($agent);
        $agentBill->setBillMonth('2024-03');
        $agentBill->setTotalAmount('3000.00');
        $agentBill->setStatus(BillStatusEnum::CONFIRMED);
        self::getEntityManager()->persist($agentBill);

        $approvedLog1 = new BillAuditLog();
        $approvedLog1->setAgentBill($agentBill);
        $approvedLog1->setAction('approved');
        $approvedLog1->setOperatorName('manager1');
        $approvedLog1->setChangeDetails(null);
        $approvedLog1->setCreateTime(new \DateTimeImmutable('-1 hour'));
        self::getEntityManager()->persist($approvedLog1);

        $approvedLog2 = new BillAuditLog();
        $approvedLog2->setAgentBill($agentBill);
        $approvedLog2->setAction('approved');
        $approvedLog2->setOperatorName('manager2');
        $approvedLog2->setChangeDetails(null);
        $approvedLog2->setCreateTime(new \DateTimeImmutable('-30 minutes'));
        self::getEntityManager()->persist($approvedLog2);

        $rejectedLog = new BillAuditLog();
        $rejectedLog->setAgentBill($agentBill);
        $rejectedLog->setAction('rejected');
        $rejectedLog->setOperatorName('manager3');
        $rejectedLog->setChangeDetails(null);
        $rejectedLog->setCreateTime(new \DateTimeImmutable('-15 minutes'));
        self::getEntityManager()->persist($rejectedLog);

        self::getEntityManager()->flush();

        $approvedResults = $this->repository->findByAction('approved');
        $this->assertGreaterThanOrEqual(2, count($approvedResults));

        $rejectedResults = $this->repository->findByAction('rejected');
        $this->assertGreaterThanOrEqual(1, count($rejectedResults));

        $foundApprovedLog1 = false;
        $foundApprovedLog2 = false;
        foreach ($approvedResults as $result) {
            if ($result->getId() === $approvedLog1->getId()) {
                $foundApprovedLog1 = true;
            }
            if ($result->getId() === $approvedLog2->getId()) {
                $foundApprovedLog2 = true;
            }
        }
        $this->assertTrue($foundApprovedLog1);
        $this->assertTrue($foundApprovedLog2);
    }

    public function testGetAuditStatistics(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST004');
        $agent->setCompanyName('Test Company 4');
        self::getEntityManager()->persist($agent);

        $agentBill = new AgentBill();
        $agentBill->setAgent($agent);
        $agentBill->setBillMonth('2024-04');
        $agentBill->setTotalAmount('4000.00');
        $agentBill->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($agentBill);

        $today = new \DateTimeImmutable();
        $yesterday = new \DateTimeImmutable('-1 day');

        $log1 = new BillAuditLog();
        $log1->setAgentBill($agentBill);
        $log1->setAction('created');
        $log1->setOperatorName('admin');
        $log1->setChangeDetails(null);
        $log1->setCreateTime($today);
        self::getEntityManager()->persist($log1);

        $log2 = new BillAuditLog();
        $log2->setAgentBill($agentBill);
        $log2->setAction('created');
        $log2->setOperatorName('admin');
        $log2->setChangeDetails(null);
        $log2->setCreateTime($today);
        self::getEntityManager()->persist($log2);

        $log3 = new BillAuditLog();
        $log3->setAgentBill($agentBill);
        $log3->setAction('reviewed');
        $log3->setOperatorName('manager');
        $log3->setChangeDetails(null);
        $log3->setCreateTime($yesterday);
        self::getEntityManager()->persist($log3);

        self::getEntityManager()->flush();

        $startDate = new \DateTimeImmutable('-2 days');
        $endDate = new \DateTimeImmutable('+1 day');
        $results = $this->repository->getAuditStatistics($startDate, $endDate);

        $this->assertArrayHasKey('actions_by_type', $results);
        $this->assertArrayHasKey('total_actions', $results);
        $this->assertGreaterThan(0, $results['total_actions']);

        $actionsByType = $results['actions_by_type'];
        $this->assertNotEmpty($actionsByType);

        $foundCreatedAction = false;
        $foundReviewedAction = false;

        foreach ($actionsByType as $result) {
            $this->assertArrayHasKey('action', $result);
            $this->assertArrayHasKey('count', $result);
            if ('created' === $result['action']) {
                $foundCreatedAction = true;
                $this->assertGreaterThanOrEqual(2, $result['count']);
            }
            if ('reviewed' === $result['action']) {
                $foundReviewedAction = true;
                $this->assertGreaterThanOrEqual(1, $result['count']);
            }
        }
        $this->assertTrue($foundCreatedAction);
        $this->assertTrue($foundReviewedAction);
    }

    public function testFindByAgentBillWithEmptyResult(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST005');
        $agent->setCompanyName('Test Company 5');
        self::getEntityManager()->persist($agent);

        $agentBill = new AgentBill();
        $agentBill->setAgent($agent);
        $agentBill->setBillMonth('2024-05');
        $agentBill->setTotalAmount('5000.00');
        $agentBill->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($agentBill);

        self::getEntityManager()->flush();

        $results = $this->repository->findByAgentBill($agentBill);
        $this->assertCount(0, $results);
    }

    public function testFindByActionWithEmptyResult(): void
    {
        $results = $this->repository->findByAction('nonexistent_action');
        $this->assertCount(0, $results);
    }

    public function testFindWithNonExistentId(): void
    {
        $result = $this->repository->find(99999);
        $this->assertNull($result);
    }

    public function testFindOneByWithNonExistentCriteria(): void
    {
        $result = $this->repository->findOneBy(['action' => 'NONEXISTENT']);
        $this->assertNull($result);

        $resultByOperator = $this->repository->findOneBy(['operatorName' => 'NONEXISTENT_OPERATOR']);
        $this->assertNull($resultByOperator);
    }

    public function testFindByWithOrderByClause(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_ORDER');
        $agent->setCompanyName('Order Test Company');
        self::getEntityManager()->persist($agent);

        $agentBill = new AgentBill();
        $agentBill->setAgent($agent);
        $agentBill->setBillMonth('2024-05');
        $agentBill->setTotalAmount('5000.00');
        $agentBill->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($agentBill);

        $auditLog1 = new BillAuditLog();
        $auditLog1->setAgentBill($agentBill);
        $auditLog1->setAction('created');
        $auditLog1->setOperatorName('admin');
        $auditLog1->setChangeDetails(null);
        $auditLog1->setCreateTime(new \DateTimeImmutable('-1 hour'));
        self::getEntityManager()->persist($auditLog1);

        $auditLog2 = new BillAuditLog();
        $auditLog2->setAgentBill($agentBill);
        $auditLog2->setAction('updated');
        $auditLog2->setOperatorName('admin');
        $auditLog2->setChangeDetails(null);
        $auditLog2->setCreateTime(new \DateTimeImmutable('-30 minutes'));
        self::getEntityManager()->persist($auditLog2);

        self::getEntityManager()->flush();

        $resultsAsc = $this->repository->findBy(['agentBill' => $agentBill], ['createTime' => 'ASC']);
        $this->assertGreaterThanOrEqual(2, count($resultsAsc));

        $resultsDesc = $this->repository->findBy(['agentBill' => $agentBill], ['createTime' => 'DESC']);
        $this->assertGreaterThanOrEqual(2, count($resultsDesc));
    }

    public function testFindByWithLimitAndOffset(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_LIMIT');
        $agent->setCompanyName('Limit Test Company');
        self::getEntityManager()->persist($agent);

        $agentBill = new AgentBill();
        $agentBill->setAgent($agent);
        $agentBill->setBillMonth('2024-06');
        $agentBill->setTotalAmount('6000.00');
        $agentBill->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($agentBill);

        for ($i = 1; $i <= 5; ++$i) {
            $auditLog = new BillAuditLog();
            $auditLog->setAgentBill($agentBill);
            $auditLog->setAction('action_' . $i);
            $auditLog->setOperatorName('admin');
            $auditLog->setChangeDetails(null);
            $auditLog->setCreateTime(new \DateTimeImmutable('-' . $i . ' minutes'));
            self::getEntityManager()->persist($auditLog);
        }
        self::getEntityManager()->flush();

        $results = $this->repository->findBy(['agentBill' => $agentBill], ['createTime' => 'DESC'], 2, 1);
        $this->assertLessThanOrEqual(2, count($results));
    }

    public function testSaveMethodShouldPersistEntity(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_SAVE');
        $agent->setCompanyName('Save Test Company');
        self::getEntityManager()->persist($agent);

        $agentBill = new AgentBill();
        $agentBill->setAgent($agent);
        $agentBill->setBillMonth('2024-07');
        $agentBill->setTotalAmount('7000.00');
        $agentBill->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($agentBill);
        self::getEntityManager()->flush();

        $auditLog = new BillAuditLog();
        $auditLog->setAgentBill($agentBill);
        $auditLog->setAction('saved');
        $auditLog->setOperatorName('admin');
        $auditLog->setChangeDetails(null);
        $auditLog->setCreateTime(new \DateTimeImmutable());

        $this->repository->save($auditLog);

        $savedLog = $this->repository->findOneBy(['action' => 'saved']);
        $this->assertInstanceOf(BillAuditLog::class, $savedLog);
        $this->assertSame('saved', $savedLog->getAction());
        $this->assertNull($savedLog->getChangeDetails());
    }

    public function testSaveMethodWithoutFlush(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_SAVE_NO_FLUSH');
        $agent->setCompanyName('Save No Flush Company');
        self::getEntityManager()->persist($agent);

        $agentBill = new AgentBill();
        $agentBill->setAgent($agent);
        $agentBill->setBillMonth('2024-08');
        $agentBill->setTotalAmount('8000.00');
        $agentBill->setStatus(BillStatusEnum::CONFIRMED);
        self::getEntityManager()->persist($agentBill);
        self::getEntityManager()->flush();

        $auditLog = new BillAuditLog();
        $auditLog->setAgentBill($agentBill);
        $auditLog->setAction('saved_no_flush');
        $auditLog->setOperatorName('admin');
        $auditLog->setChangeDetails(null);
        $auditLog->setCreateTime(new \DateTimeImmutable());

        $this->repository->save($auditLog, false);
        self::getEntityManager()->flush();

        $savedLog = $this->repository->findOneBy(['action' => 'saved_no_flush']);
        $this->assertInstanceOf(BillAuditLog::class, $savedLog);
        $this->assertSame('saved_no_flush', $savedLog->getAction());
    }

    public function testRemoveMethodShouldDeleteEntity(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_REMOVE');
        $agent->setCompanyName('Remove Test Company');
        self::getEntityManager()->persist($agent);

        $agentBill = new AgentBill();
        $agentBill->setAgent($agent);
        $agentBill->setBillMonth('2024-09');
        $agentBill->setTotalAmount('9000.00');
        $agentBill->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($agentBill);

        $auditLog = new BillAuditLog();
        $auditLog->setAgentBill($agentBill);
        $auditLog->setAction('to_remove');
        $auditLog->setOperatorName('admin');
        $auditLog->setChangeDetails(null);
        $auditLog->setCreateTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($auditLog);
        self::getEntityManager()->flush();

        $savedLog = $this->repository->findOneBy(['action' => 'to_remove']);
        $this->assertInstanceOf(BillAuditLog::class, $savedLog);

        $this->repository->remove($savedLog);

        $deletedLog = $this->repository->findOneBy(['action' => 'to_remove']);
        $this->assertNull($deletedLog);
    }

    public function testRemoveMethodWithoutFlush(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_REMOVE_NO_FLUSH');
        $agent->setCompanyName('Remove No Flush Company');
        self::getEntityManager()->persist($agent);

        $agentBill = new AgentBill();
        $agentBill->setAgent($agent);
        $agentBill->setBillMonth('2024-10');
        $agentBill->setTotalAmount('10000.00');
        $agentBill->setStatus(BillStatusEnum::CONFIRMED);
        self::getEntityManager()->persist($agentBill);

        $auditLog = new BillAuditLog();
        $auditLog->setAgentBill($agentBill);
        $auditLog->setAction('to_remove_no_flush');
        $auditLog->setOperatorName('admin');
        $auditLog->setChangeDetails(null);
        $auditLog->setCreateTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($auditLog);
        self::getEntityManager()->flush();

        $savedLog = $this->repository->findOneBy(['action' => 'to_remove_no_flush']);
        $this->assertInstanceOf(BillAuditLog::class, $savedLog);

        $this->repository->remove($savedLog, false);
        self::getEntityManager()->flush();

        $deletedLog = $this->repository->findOneBy(['action' => 'to_remove_no_flush']);
        $this->assertNull($deletedLog);
    }

    public function testCountWithCriteria(): void
    {
        $initialCount = $this->repository->count(['action' => 'counted']);

        $agent = new Agent();
        $agent->setCode('TEST_COUNT');
        $agent->setCompanyName('Count Test Company');
        self::getEntityManager()->persist($agent);

        $agentBill = new AgentBill();
        $agentBill->setAgent($agent);
        $agentBill->setBillMonth('2024-11');
        $agentBill->setTotalAmount('11000.00');
        $agentBill->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($agentBill);

        $auditLog1 = new BillAuditLog();
        $auditLog1->setAgentBill($agentBill);
        $auditLog1->setAction('counted');
        $auditLog1->setOperatorName('admin1');
        $auditLog1->setChangeDetails(null);
        $auditLog1->setCreateTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($auditLog1);

        $auditLog2 = new BillAuditLog();
        $auditLog2->setAgentBill($agentBill);
        $auditLog2->setAction('counted');
        $auditLog2->setOperatorName('admin2');
        $auditLog2->setChangeDetails(null);
        $auditLog2->setCreateTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($auditLog2);

        self::getEntityManager()->flush();

        $finalCount = $this->repository->count(['action' => 'counted']);
        $this->assertSame($initialCount + 2, $finalCount);
    }

    public function testCountAssociationQuery(): void
    {
        $agent1 = new Agent();
        $agent1->setCode('ASSOC_COUNT_1');
        $agent1->setCompanyName('Association Count Company 1');
        self::getEntityManager()->persist($agent1);

        $agentBill1 = new AgentBill();
        $agentBill1->setAgent($agent1);
        $agentBill1->setBillMonth('2024-01');
        $agentBill1->setTotalAmount('1000.00');
        $agentBill1->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($agentBill1);

        $agent2 = new Agent();
        $agent2->setCode('ASSOC_COUNT_2');
        $agent2->setCompanyName('Association Count Company 2');
        self::getEntityManager()->persist($agent2);

        $agentBill2 = new AgentBill();
        $agentBill2->setAgent($agent2);
        $agentBill2->setBillMonth('2024-02');
        $agentBill2->setTotalAmount('2000.00');
        $agentBill2->setStatus(BillStatusEnum::CONFIRMED);
        self::getEntityManager()->persist($agentBill2);

        $auditLog1 = new BillAuditLog();
        $auditLog1->setAgentBill($agentBill1);
        $auditLog1->setAction('association_test');
        $auditLog1->setOperatorName('admin1');
        $auditLog1->setChangeDetails(null);
        $auditLog1->setCreateTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($auditLog1);

        $auditLog2 = new BillAuditLog();
        $auditLog2->setAgentBill($agentBill2);
        $auditLog2->setAction('association_test');
        $auditLog2->setOperatorName('admin2');
        $auditLog2->setChangeDetails(null);
        $auditLog2->setCreateTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($auditLog2);

        self::getEntityManager()->flush();

        $bill1Count = $this->repository->count(['agentBill' => $agentBill1]);
        $this->assertSame(1, $bill1Count);

        $bill2Count = $this->repository->count(['agentBill' => $agentBill2]);
        $this->assertSame(1, $bill2Count);
    }

    public function testAssociationQuery(): void
    {
        $agent1 = new Agent();
        $agent1->setCode('ASSOC_QUERY_1');
        $agent1->setCompanyName('Association Query Company 1');
        self::getEntityManager()->persist($agent1);

        $agentBill1 = new AgentBill();
        $agentBill1->setAgent($agent1);
        $agentBill1->setBillMonth('2024-01');
        $agentBill1->setTotalAmount('1000.00');
        $agentBill1->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($agentBill1);

        $agent2 = new Agent();
        $agent2->setCode('ASSOC_QUERY_2');
        $agent2->setCompanyName('Association Query Company 2');
        self::getEntityManager()->persist($agent2);

        $agentBill2 = new AgentBill();
        $agentBill2->setAgent($agent2);
        $agentBill2->setBillMonth('2024-02');
        $agentBill2->setTotalAmount('2000.00');
        $agentBill2->setStatus(BillStatusEnum::CONFIRMED);
        self::getEntityManager()->persist($agentBill2);

        $auditLog1 = new BillAuditLog();
        $auditLog1->setAgentBill($agentBill1);
        $auditLog1->setAction('association_query');
        $auditLog1->setOperatorName('admin1');
        $auditLog1->setChangeDetails(null);
        $auditLog1->setCreateTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($auditLog1);

        $auditLog2 = new BillAuditLog();
        $auditLog2->setAgentBill($agentBill2);
        $auditLog2->setAction('association_query');
        $auditLog2->setOperatorName('admin2');
        $auditLog2->setChangeDetails(null);
        $auditLog2->setCreateTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($auditLog2);

        self::getEntityManager()->flush();

        $bill1Logs = $this->repository->findBy(['agentBill' => $agentBill1]);
        $this->assertCount(1, $bill1Logs);
        $this->assertSame($auditLog1->getId(), $bill1Logs[0]->getId());

        $bill2Logs = $this->repository->findBy(['agentBill' => $agentBill2]);
        $this->assertCount(1, $bill2Logs);
        $this->assertSame($auditLog2->getId(), $bill2Logs[0]->getId());
    }

    public function testNullFieldQuery(): void
    {
        $agent = new Agent();
        $agent->setCode('NULL_FIELD');
        $agent->setCompanyName('NULL Field Company');
        self::getEntityManager()->persist($agent);

        $agentBill = new AgentBill();
        $agentBill->setAgent($agent);
        $agentBill->setBillMonth('2024-01');
        $agentBill->setTotalAmount('1000.00');
        $agentBill->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($agentBill);

        $auditLog = new BillAuditLog();
        $auditLog->setAgentBill($agentBill);
        $auditLog->setAction('null_test');
        $auditLog->setOperatorName('admin');
        $auditLog->setChangeDetails(null);
        $auditLog->setCreateTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($auditLog);
        self::getEntityManager()->flush();

        $results = $this->repository->findBy(['changeDetails' => null]);
        $this->assertGreaterThanOrEqual(1, count($results));

        $foundNullDetails = false;
        foreach ($results as $result) {
            if (null === $result->getChangeDetails()) {
                $foundNullDetails = true;
                break;
            }
        }
        $this->assertTrue($foundNullDetails);
    }

    public function testCountNullFieldQuery(): void
    {
        $agent = new Agent();
        $agent->setCode('COUNT_NULL');
        $agent->setCompanyName('Count NULL Company');
        self::getEntityManager()->persist($agent);

        $agentBill = new AgentBill();
        $agentBill->setAgent($agent);
        $agentBill->setBillMonth('2024-01');
        $agentBill->setTotalAmount('1000.00');
        $agentBill->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($agentBill);

        $auditLog = new BillAuditLog();
        $auditLog->setAgentBill($agentBill);
        $auditLog->setAction('count_null_test');
        $auditLog->setOperatorName(null);
        $auditLog->setChangeDetails(null);
        $auditLog->setRemarks(null);
        $auditLog->setIpAddress(null);
        $auditLog->setFromStatus(null);
        $auditLog->setToStatus(null);
        $auditLog->setCreateTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($auditLog);
        self::getEntityManager()->flush();

        $count = $this->repository->count(['changeDetails' => null]);
        $this->assertGreaterThanOrEqual(1, $count);

        $remarksCount = $this->repository->count(['remarks' => null]);
        $this->assertGreaterThanOrEqual(1, $remarksCount);

        $operatorNameCount = $this->repository->count(['operatorName' => null]);
        $this->assertGreaterThanOrEqual(1, $operatorNameCount);

        $ipAddressCount = $this->repository->count(['ipAddress' => null]);
        $this->assertGreaterThanOrEqual(1, $ipAddressCount);

        $fromStatusCount = $this->repository->count(['fromStatus' => null]);
        $this->assertGreaterThanOrEqual(1, $fromStatusCount);

        $toStatusCount = $this->repository->count(['toStatus' => null]);
        $this->assertGreaterThanOrEqual(1, $toStatusCount);
    }

    public function testFindOneByWithOrderBy(): void
    {
        $agent = new Agent();
        $agent->setCode('ORDER_ONE_BY');
        $agent->setCompanyName('Order One By Company');
        self::getEntityManager()->persist($agent);

        $agentBill = new AgentBill();
        $agentBill->setAgent($agent);
        $agentBill->setBillMonth('2024-03');
        $agentBill->setTotalAmount('3000.00');
        $agentBill->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($agentBill);

        $auditLog1 = new BillAuditLog();
        $auditLog1->setAgentBill($agentBill);
        $auditLog1->setAction('order_one_test');
        $auditLog1->setOperatorName('admin_first');
        $auditLog1->setChangeDetails(null);
        $auditLog1->setCreateTime(new \DateTimeImmutable('-1 hour'));
        self::getEntityManager()->persist($auditLog1);

        $auditLog2 = new BillAuditLog();
        $auditLog2->setAgentBill($agentBill);
        $auditLog2->setAction('order_one_test');
        $auditLog2->setOperatorName('admin_second');
        $auditLog2->setChangeDetails(null);
        $auditLog2->setCreateTime(new \DateTimeImmutable('-30 minutes'));
        self::getEntityManager()->persist($auditLog2);

        self::getEntityManager()->flush();

        $resultAsc = $this->repository->findOneBy(['action' => 'order_one_test'], ['createTime' => 'ASC']);
        $this->assertInstanceOf(BillAuditLog::class, $resultAsc);

        $resultDesc = $this->repository->findOneBy(['action' => 'order_one_test'], ['createTime' => 'DESC']);
        $this->assertInstanceOf(BillAuditLog::class, $resultDesc);
    }

    /**
     * @return ServiceEntityRepository<BillAuditLog>
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

        // 创建必需的 AgentBill 实体
        $agentBill = new AgentBill();
        $agentBill->setAgent($agent);
        $agentBill->setBillMonth('2024-' . sprintf('%02d', rand(1, 12)));
        $agentBill->setTotalAmount(sprintf('%.2f', rand(1000, 9999) / 100));
        $agentBill->setCommissionAmount(sprintf('%.2f', rand(50, 500) / 100));
        $agentBill->setCommissionRate(sprintf('%.2f', rand(5, 20) / 100));
        $agentBill->setOrderCount(rand(1, 50));
        $agentBill->setSettlementType(SettlementTypeEnum::MONTHLY);
        $agentBill->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($agentBill);

        self::getEntityManager()->flush();

        // 创建 BillAuditLog 实体
        $auditLog = new BillAuditLog();
        $auditLog->setAgentBill($agentBill);
        $auditLog->setAction('test_action_' . uniqid());
        $auditLog->setOperatorName('test_operator_' . uniqid());
        $auditLog->setChangeDetails(null);
        $auditLog->setCreateTime(new \DateTimeImmutable());

        return $auditLog;
    }
}
