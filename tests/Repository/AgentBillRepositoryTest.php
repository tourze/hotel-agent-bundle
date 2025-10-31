<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Enum\AgentLevelEnum;
use Tourze\HotelAgentBundle\Enum\AgentStatusEnum;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;
use Tourze\HotelAgentBundle\Enum\SettlementTypeEnum;
use Tourze\HotelAgentBundle\Repository\AgentBillRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(AgentBillRepository::class)]
#[RunTestsInSeparateProcesses]
final class AgentBillRepositoryTest extends AbstractRepositoryTestCase
{
    private AgentBillRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(AgentBillRepository::class);
    }

    private function createTestAgent(string $code = 'TEST'): Agent
    {
        /** @var int $counter */
        static $counter = 0;
        ++$counter;

        $agent = new Agent();
        $agent->setCode($code . str_pad((string) $counter, 3, '0', STR_PAD_LEFT));
        $agent->setCompanyName('Test Company ' . $counter);
        $agent->setContactPerson('Test Contact ' . $counter);
        $agent->setPhone('1380013' . str_pad((string) $counter, 4, '0', STR_PAD_LEFT));
        $agent->setEmail('test' . $counter . '@example.com');
        $agent->setCommissionRate('10.00');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::C);

        return $agent;
    }

    private function createTestAgentBill(Agent $agent, string $billMonth = '2024-01'): AgentBill
    {
        $agentBill = new AgentBill();
        $agentBill->setAgent($agent);
        $agentBill->setBillMonth($billMonth);
        $agentBill->setOrderCount(5);
        $agentBill->setTotalAmount('1000.00');
        $agentBill->setCommissionRate('10.00');
        $agentBill->setCommissionAmount('100.00');
        $agentBill->setSettlementType(SettlementTypeEnum::MONTHLY);
        $agentBill->setStatus(BillStatusEnum::PENDING);

        return $agentBill;
    }

    public function testFindByAgentAndMonth(): void
    {
        $agent = $this->createTestAgent();
        self::getEntityManager()->persist($agent);

        $bill = $this->createTestAgentBill($agent, '2024-01');
        self::getEntityManager()->persist($bill);

        self::getEntityManager()->flush();

        $result = $this->repository->findByAgentAndMonth($agent, '2024-01');
        $this->assertInstanceOf(AgentBill::class, $result);
        $this->assertSame($bill->getId(), $result->getId());

        $notFound = $this->repository->findByAgentAndMonth($agent, '2024-02');
        $this->assertNull($notFound);
    }

    public function testFindByAgent(): void
    {
        $agent = $this->createTestAgent();
        $agent->setLevel(AgentLevelEnum::B);
        self::getEntityManager()->persist($agent);

        $bill1 = $this->createTestAgentBill($agent, '2024-01');
        $bill1->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($bill1);

        $bill2 = $this->createTestAgentBill($agent, '2024-02');
        $bill2->setTotalAmount('2000.00');
        $bill2->setStatus(BillStatusEnum::CONFIRMED);
        self::getEntityManager()->persist($bill2);

        self::getEntityManager()->flush();

        $results = $this->repository->findByAgent($agent);
        $this->assertCount(2, $results);
        $this->assertSame('2024-02', $results[0]->getBillMonth());
        $this->assertSame('2024-01', $results[1]->getBillMonth());
    }

    public function testFindByStatus(): void
    {
        $agent = $this->createTestAgent();
        $agent->setLevel(AgentLevelEnum::A);
        self::getEntityManager()->persist($agent);

        $bill1 = $this->createTestAgentBill($agent, '2024-01');
        $bill1->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($bill1);

        $bill2 = $this->createTestAgentBill($agent, '2024-02');
        $bill2->setTotalAmount('2000.00');
        $bill2->setStatus(BillStatusEnum::CONFIRMED);
        self::getEntityManager()->persist($bill2);

        self::getEntityManager()->flush();

        $pendingBills = $this->repository->findByStatus(BillStatusEnum::PENDING);
        $this->assertGreaterThanOrEqual(1, count($pendingBills));

        $confirmedBills = $this->repository->findByStatus(BillStatusEnum::CONFIRMED);
        $this->assertGreaterThanOrEqual(1, count($confirmedBills));
    }

    public function testFindByMonth(): void
    {
        $agent = $this->createTestAgent();
        self::getEntityManager()->persist($agent);

        $bill = $this->createTestAgentBill($agent, '2024-03');
        $bill->setTotalAmount('1500.00');
        $bill->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($bill);

        self::getEntityManager()->flush();

        $results = $this->repository->findByMonth('2024-03');
        $this->assertGreaterThanOrEqual(1, count($results));

        $found = false;
        foreach ($results as $result) {
            if ($result->getId() === $bill->getId()) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testFindByPeriod(): void
    {
        $agent = $this->createTestAgent();
        $agent->setLevel(AgentLevelEnum::B);
        self::getEntityManager()->persist($agent);

        $bill1 = $this->createTestAgentBill($agent, '2024-04');
        $bill1->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($bill1);

        $bill2 = $this->createTestAgentBill($agent, '2024-05');
        $bill2->setTotalAmount('2000.00');
        $bill2->setStatus(BillStatusEnum::CONFIRMED);
        self::getEntityManager()->persist($bill2);

        $bill3 = $this->createTestAgentBill($agent, '2024-06');
        $bill3->setTotalAmount('3000.00');
        $bill3->setStatus(BillStatusEnum::PAID);
        self::getEntityManager()->persist($bill3);

        self::getEntityManager()->flush();

        $results = $this->repository->findByPeriod('2024-04', '2024-05');
        $this->assertGreaterThanOrEqual(2, count($results));
    }

    public function testFindPendingBills(): void
    {
        $agent = $this->createTestAgent();
        $agent->setLevel(AgentLevelEnum::A);
        self::getEntityManager()->persist($agent);

        $bill = $this->createTestAgentBill($agent, '2024-07');
        $bill->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($bill);

        self::getEntityManager()->flush();

        $results = $this->repository->findPendingBills();
        $this->assertGreaterThanOrEqual(1, count($results));

        $found = false;
        foreach ($results as $result) {
            if ($result->getId() === $bill->getId()) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testFindConfirmedUnpaidBills(): void
    {
        $agent = $this->createTestAgent();
        self::getEntityManager()->persist($agent);

        $bill = $this->createTestAgentBill($agent, '2024-08');
        $bill->setTotalAmount('1500.00');
        $bill->setStatus(BillStatusEnum::CONFIRMED);
        self::getEntityManager()->persist($bill);

        self::getEntityManager()->flush();

        $results = $this->repository->findConfirmedUnpaidBills();
        $this->assertGreaterThanOrEqual(1, count($results));

        $found = false;
        foreach ($results as $result) {
            if ($result->getId() === $bill->getId()) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testFindWithNonExistentId(): void
    {
        $result = $this->repository->find(99999);
        $this->assertNull($result);
    }

    public function testFindOneByWithNonExistentCriteria(): void
    {
        $result = $this->repository->findOneBy(['billMonth' => 'NONEXISTENT']);
        $this->assertNull($result);

        $resultByStatus = $this->repository->findOneBy(['billMonth' => '9999-99']);
        $this->assertNull($resultByStatus);
    }

    public function testFindByWithOrderByClause(): void
    {
        $agent = $this->createTestAgent('ORDER');
        self::getEntityManager()->persist($agent);

        $bill1 = $this->createTestAgentBill($agent, '2024-05');
        $bill1->setTotalAmount('1000.00');
        $bill1->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($bill1);

        $bill2 = $this->createTestAgentBill($agent, '2024-06');
        $bill2->setTotalAmount('2000.00');
        $bill2->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($bill2);

        self::getEntityManager()->flush();

        $resultsAsc = $this->repository->findBy(['status' => BillStatusEnum::PENDING], ['billMonth' => 'ASC']);
        $this->assertGreaterThanOrEqual(2, count($resultsAsc));

        $resultsDesc = $this->repository->findBy(['status' => BillStatusEnum::PENDING], ['billMonth' => 'DESC']);
        $this->assertGreaterThanOrEqual(2, count($resultsDesc));
    }

    public function testFindByWithLimitAndOffset(): void
    {
        $agent = $this->createTestAgent('LIMIT');
        self::getEntityManager()->persist($agent);

        for ($i = 1; $i <= 5; ++$i) {
            $bill = $this->createTestAgentBill($agent, '2024-' . sprintf('%02d', $i + 6));
            $bill->setTotalAmount(sprintf('%.2f', 1000.00 * $i));
            $bill->setStatus(BillStatusEnum::CONFIRMED);
            self::getEntityManager()->persist($bill);
        }
        self::getEntityManager()->flush();

        $results = $this->repository->findBy(['status' => BillStatusEnum::CONFIRMED], ['billMonth' => 'ASC'], 2, 1);
        $this->assertLessThanOrEqual(2, count($results));
    }

    public function testSaveMethodShouldPersistEntity(): void
    {
        $agent = $this->createTestAgent('SAVE');
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $bill = $this->createTestAgentBill($agent, '2024-12');
        $bill->setTotalAmount('5000.00');
        $bill->setStatus(BillStatusEnum::PENDING);

        $this->repository->save($bill);

        $savedBill = $this->repository->findByAgentAndMonth($agent, '2024-12');
        $this->assertInstanceOf(AgentBill::class, $savedBill);
        $this->assertSame('2024-12', $savedBill->getBillMonth());
        $this->assertSame('5000.00', $savedBill->getTotalAmount());
    }

    public function testSaveMethodWithoutFlush(): void
    {
        $agent = $this->createTestAgent('SAVE_NO_FLUSH');
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $bill = $this->createTestAgentBill($agent, '2024-13');
        $bill->setTotalAmount('6000.00');
        $bill->setStatus(BillStatusEnum::CONFIRMED);

        $this->repository->save($bill, false);
        self::getEntityManager()->flush();

        $savedBill = $this->repository->findByAgentAndMonth($agent, '2024-13');
        $this->assertInstanceOf(AgentBill::class, $savedBill);
        $this->assertSame('2024-13', $savedBill->getBillMonth());
    }

    public function testRemoveMethodShouldDeleteEntity(): void
    {
        $agent = $this->createTestAgent('REMOVE');
        self::getEntityManager()->persist($agent);

        $bill = $this->createTestAgentBill($agent, '2024-14');
        $bill->setTotalAmount('7000.00');
        $bill->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($bill);
        self::getEntityManager()->flush();

        $savedBill = $this->repository->findByAgentAndMonth($agent, '2024-14');
        $this->assertInstanceOf(AgentBill::class, $savedBill);

        $this->repository->remove($savedBill);

        $deletedBill = $this->repository->findByAgentAndMonth($agent, '2024-14');
        $this->assertNull($deletedBill);
    }

    public function testRemoveMethodWithoutFlush(): void
    {
        $agent = $this->createTestAgent('REMOVE_NO_FLUSH');
        self::getEntityManager()->persist($agent);

        $bill = $this->createTestAgentBill($agent, '2024-15');
        $bill->setTotalAmount('8000.00');
        $bill->setStatus(BillStatusEnum::CONFIRMED);
        self::getEntityManager()->persist($bill);
        self::getEntityManager()->flush();

        $savedBill = $this->repository->findByAgentAndMonth($agent, '2024-15');
        $this->assertInstanceOf(AgentBill::class, $savedBill);

        $this->repository->remove($savedBill, false);
        self::getEntityManager()->flush();

        $deletedBill = $this->repository->findByAgentAndMonth($agent, '2024-15');
        $this->assertNull($deletedBill);
    }

    public function testCountWithCriteria(): void
    {
        $initialPendingCount = $this->repository->count(['status' => BillStatusEnum::PENDING]);
        $initialConfirmedCount = $this->repository->count(['status' => BillStatusEnum::CONFIRMED]);

        $agent = $this->createTestAgent('COUNT');
        self::getEntityManager()->persist($agent);

        $pendingBill = $this->createTestAgentBill($agent, '2024-16');
        $pendingBill->setTotalAmount('9000.00');
        $pendingBill->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($pendingBill);

        $confirmedBill = $this->createTestAgentBill($agent, '2024-17');
        $confirmedBill->setTotalAmount('10000.00');
        $confirmedBill->setStatus(BillStatusEnum::CONFIRMED);
        self::getEntityManager()->persist($confirmedBill);

        self::getEntityManager()->flush();

        $pendingCount = $this->repository->count(['status' => BillStatusEnum::PENDING]);
        $confirmedCount = $this->repository->count(['status' => BillStatusEnum::CONFIRMED]);

        $this->assertSame($initialPendingCount + 1, $pendingCount);
        $this->assertSame($initialConfirmedCount + 1, $confirmedCount);
    }

    public function testCountAssociationQuery(): void
    {
        $agent1 = $this->createTestAgent('ASSOC_COUNT_1');
        self::getEntityManager()->persist($agent1);

        $agent2 = $this->createTestAgent('ASSOC_COUNT_2');
        self::getEntityManager()->persist($agent2);

        $bill1 = $this->createTestAgentBill($agent1, '2024-01');
        self::getEntityManager()->persist($bill1);

        $bill2 = $this->createTestAgentBill($agent2, '2024-01');
        self::getEntityManager()->persist($bill2);

        self::getEntityManager()->flush();

        $agent1Count = $this->repository->count(['agent' => $agent1]);
        $this->assertSame(1, $agent1Count);

        $agent2Count = $this->repository->count(['agent' => $agent2]);
        $this->assertSame(1, $agent2Count);
    }

    public function testAssociationQuery(): void
    {
        $agent1 = $this->createTestAgent('ASSOC_1');
        self::getEntityManager()->persist($agent1);

        $agent2 = $this->createTestAgent('ASSOC_2');
        self::getEntityManager()->persist($agent2);

        $bill1 = $this->createTestAgentBill($agent1, '2024-01');
        self::getEntityManager()->persist($bill1);

        $bill2 = $this->createTestAgentBill($agent2, '2024-02');
        self::getEntityManager()->persist($bill2);

        self::getEntityManager()->flush();

        $agent1Bills = $this->repository->findBy(['agent' => $agent1]);
        $this->assertCount(1, $agent1Bills);
        $this->assertSame($bill1->getId(), $agent1Bills[0]->getId());

        $agent2Bills = $this->repository->findBy(['agent' => $agent2]);
        $this->assertCount(1, $agent2Bills);
        $this->assertSame($bill2->getId(), $agent2Bills[0]->getId());
    }

    public function testNullFieldQuery(): void
    {
        $agent = $this->createTestAgent('NULL_FIELD');
        self::getEntityManager()->persist($agent);

        $bill = $this->createTestAgentBill($agent);
        $bill->setConfirmTime(null);
        $bill->setPayTime(null);
        self::getEntityManager()->persist($bill);
        self::getEntityManager()->flush();

        $results = $this->repository->findBy(['confirmTime' => null]);
        $this->assertGreaterThanOrEqual(1, count($results));

        $foundNullConfirmTime = false;
        foreach ($results as $result) {
            if (null === $result->getConfirmTime()) {
                $foundNullConfirmTime = true;
                break;
            }
        }
        $this->assertTrue($foundNullConfirmTime);
    }

    public function testCountNullFieldQuery(): void
    {
        $agent = $this->createTestAgent('COUNT_NULL');
        self::getEntityManager()->persist($agent);

        $bill = $this->createTestAgentBill($agent);
        $bill->setConfirmTime(null);
        self::getEntityManager()->persist($bill);
        self::getEntityManager()->flush();

        $count = $this->repository->count(['confirmTime' => null]);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    /**
     * @return ServiceEntityRepository<AgentBill>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): object
    {
        $agentBill = new AgentBill();
        $agentBill->setBillMonth('2024-' . sprintf('%02d', rand(1, 12)));
        $agentBill->setTotalAmount(sprintf('%.2f', rand(1000, 9999) / 100));
        $agentBill->setCommissionAmount(sprintf('%.2f', rand(50, 500) / 100));
        $agentBill->setCommissionRate(sprintf('%.2f', rand(5, 20) / 100));
        $agentBill->setOrderCount(rand(1, 50));
        $agentBill->setSettlementType(SettlementTypeEnum::MONTHLY);
        $agentBill->setStatus(BillStatusEnum::PENDING);

        return $agentBill;
    }
}
