<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Enum\AgentLevelEnum;
use Tourze\HotelAgentBundle\Enum\AgentStatusEnum;
use Tourze\HotelAgentBundle\Repository\AgentRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(AgentRepository::class)]
#[RunTestsInSeparateProcesses]
final class AgentRepositoryTest extends AbstractRepositoryTestCase
{
    private AgentRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(AgentRepository::class);
    }

    public function testFindByCode(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST001');
        $agent->setCompanyName('Test Company');
        $agent->setContactPerson('Test Contact');
        $agent->setPhone('13800138000');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::A);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $result = $this->repository->findByCode('TEST001');
        $this->assertInstanceOf(Agent::class, $result);
        $this->assertSame($agent->getId(), $result->getId());

        $notFound = $this->repository->findByCode('NONEXISTENT');
        $this->assertNull($notFound);
    }

    public function testFindByCompanyName(): void
    {
        $agent1 = new Agent();
        $agent1->setCode('TEST002');
        $agent1->setCompanyName('Acme Corporation');
        $agent1->setContactPerson('Contact 1');
        $agent1->setPhone('13800138001');
        $agent1->setStatus(AgentStatusEnum::ACTIVE);
        $agent1->setLevel(AgentLevelEnum::A);
        self::getEntityManager()->persist($agent1);

        $agent2 = new Agent();
        $agent2->setCode('TEST003');
        $agent2->setCompanyName('Acme Solutions');
        $agent2->setContactPerson('Contact 2');
        $agent2->setPhone('13800138002');
        $agent2->setStatus(AgentStatusEnum::ACTIVE);
        $agent2->setLevel(AgentLevelEnum::B);
        self::getEntityManager()->persist($agent2);

        $agent3 = new Agent();
        $agent3->setCode('TEST004');
        $agent3->setCompanyName('Different Company');
        $agent3->setContactPerson('Contact 3');
        $agent3->setPhone('13800138003');
        $agent3->setStatus(AgentStatusEnum::ACTIVE);
        $agent3->setLevel(AgentLevelEnum::C);
        self::getEntityManager()->persist($agent3);

        self::getEntityManager()->flush();

        $results = $this->repository->findByCompanyName('Acme');
        $this->assertGreaterThanOrEqual(2, count($results));

        $foundAgent1 = false;
        $foundAgent2 = false;
        $foundAgent3 = false;
        foreach ($results as $result) {
            if ($result->getId() === $agent1->getId()) {
                $foundAgent1 = true;
            }
            if ($result->getId() === $agent2->getId()) {
                $foundAgent2 = true;
            }
            if ($result->getId() === $agent3->getId()) {
                $foundAgent3 = true;
            }
        }
        $this->assertTrue($foundAgent1);
        $this->assertTrue($foundAgent2);
        $this->assertFalse($foundAgent3);
    }

    public function testFindByPhone(): void
    {
        $uniquePhone = '19999' . uniqid();
        $agent = new Agent();
        $agent->setCode('TEST005');
        $agent->setCompanyName('Test Company');
        $agent->setContactPerson('Phone Contact');
        $agent->setPhone($uniquePhone);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::A);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $result = $this->repository->findByPhone($uniquePhone);
        $this->assertInstanceOf(Agent::class, $result);
        $this->assertSame($agent->getId(), $result->getId());

        $notFound = $this->repository->findByPhone('99999999999');
        $this->assertNull($notFound);
    }

    public function testFindByStatus(): void
    {
        $agent1 = new Agent();
        $agent1->setCode('TEST006');
        $agent1->setCompanyName('Active Company 1');
        $agent1->setContactPerson('Active Contact 1');
        $agent1->setPhone('13800138006');
        $agent1->setStatus(AgentStatusEnum::ACTIVE);
        $agent1->setLevel(AgentLevelEnum::A);
        self::getEntityManager()->persist($agent1);

        $agent2 = new Agent();
        $agent2->setCode('TEST007');
        $agent2->setCompanyName('Active Company 2');
        $agent2->setContactPerson('Active Contact 2');
        $agent2->setPhone('13800138007');
        $agent2->setStatus(AgentStatusEnum::ACTIVE);
        $agent2->setLevel(AgentLevelEnum::B);
        self::getEntityManager()->persist($agent2);

        $agent3 = new Agent();
        $agent3->setCode('TEST008');
        $agent3->setCompanyName('Inactive Company');
        $agent3->setContactPerson('Inactive Contact');
        $agent3->setPhone('13800138008');
        $agent3->setStatus(AgentStatusEnum::DISABLED);
        $agent3->setLevel(AgentLevelEnum::C);
        self::getEntityManager()->persist($agent3);

        self::getEntityManager()->flush();

        $activeAgents = $this->repository->findByStatus(AgentStatusEnum::ACTIVE);
        $this->assertGreaterThanOrEqual(2, count($activeAgents));

        $inactiveAgents = $this->repository->findByStatus(AgentStatusEnum::DISABLED);
        $this->assertGreaterThanOrEqual(1, count($inactiveAgents));
    }

    public function testFindByLevel(): void
    {
        $agent1 = new Agent();
        $agent1->setCode('TEST009');
        $agent1->setCompanyName('Bronze Company 1');
        $agent1->setContactPerson('Bronze Contact 1');
        $agent1->setPhone('13800138009');
        $agent1->setStatus(AgentStatusEnum::ACTIVE);
        $agent1->setLevel(AgentLevelEnum::A);
        self::getEntityManager()->persist($agent1);

        $agent2 = new Agent();
        $agent2->setCode('TEST010');
        $agent2->setCompanyName('Bronze Company 2');
        $agent2->setContactPerson('Bronze Contact 2');
        $agent2->setPhone('13800138010');
        $agent2->setStatus(AgentStatusEnum::ACTIVE);
        $agent2->setLevel(AgentLevelEnum::A);
        self::getEntityManager()->persist($agent2);

        $agent3 = new Agent();
        $agent3->setCode('TEST011');
        $agent3->setCompanyName('Silver Company');
        $agent3->setContactPerson('Silver Contact');
        $agent3->setPhone('13800138011');
        $agent3->setStatus(AgentStatusEnum::ACTIVE);
        $agent3->setLevel(AgentLevelEnum::B);
        self::getEntityManager()->persist($agent3);

        self::getEntityManager()->flush();

        $bronzeAgents = $this->repository->findByLevel(AgentLevelEnum::A);
        $this->assertGreaterThanOrEqual(2, count($bronzeAgents));

        $silverAgents = $this->repository->findByLevel(AgentLevelEnum::B);
        $this->assertGreaterThanOrEqual(1, count($silverAgents));
    }

    public function testFindExpiredAgents(): void
    {
        $pastDate = new \DateTimeImmutable('-10 days');
        $futureDate = new \DateTimeImmutable('+10 days');

        $expiredAgent = new Agent();
        $expiredAgent->setCode('TEST012');
        $expiredAgent->setCompanyName('Expired Company');
        $expiredAgent->setContactPerson('Expired Contact');
        $expiredAgent->setPhone('13800138012');
        $expiredAgent->setStatus(AgentStatusEnum::ACTIVE);
        $expiredAgent->setLevel(AgentLevelEnum::A);
        $expiredAgent->setExpiryDate($pastDate);
        self::getEntityManager()->persist($expiredAgent);

        $activeAgent = new Agent();
        $activeAgent->setCode('TEST013');
        $activeAgent->setCompanyName('Active Company');
        $activeAgent->setContactPerson('Active Contact');
        $activeAgent->setPhone('13800138013');
        $activeAgent->setStatus(AgentStatusEnum::ACTIVE);
        $activeAgent->setLevel(AgentLevelEnum::B);
        $activeAgent->setExpiryDate($futureDate);
        self::getEntityManager()->persist($activeAgent);

        $alreadyExpiredAgent = new Agent();
        $alreadyExpiredAgent->setCode('TEST014');
        $alreadyExpiredAgent->setCompanyName('Already Expired Company');
        $alreadyExpiredAgent->setContactPerson('Already Expired Contact');
        $alreadyExpiredAgent->setPhone('13800138014');
        $alreadyExpiredAgent->setStatus(AgentStatusEnum::EXPIRED);
        $alreadyExpiredAgent->setLevel(AgentLevelEnum::C);
        $alreadyExpiredAgent->setExpiryDate($pastDate);
        self::getEntityManager()->persist($alreadyExpiredAgent);

        self::getEntityManager()->flush();

        $results = $this->repository->findExpiredAgents();
        $this->assertGreaterThanOrEqual(1, count($results));

        $foundExpiredAgent = false;
        $foundActiveAgent = false;
        $foundAlreadyExpiredAgent = false;
        foreach ($results as $result) {
            if ($result->getId() === $expiredAgent->getId()) {
                $foundExpiredAgent = true;
            }
            if ($result->getId() === $activeAgent->getId()) {
                $foundActiveAgent = true;
            }
            if ($result->getId() === $alreadyExpiredAgent->getId()) {
                $foundAlreadyExpiredAgent = true;
            }
        }
        $this->assertTrue($foundExpiredAgent);
        $this->assertFalse($foundActiveAgent);
        $this->assertFalse($foundAlreadyExpiredAgent);
    }

    public function testFindAgentsExpiringInDays(): void
    {
        $in5Days = new \DateTimeImmutable('+5 days');
        $in15Days = new \DateTimeImmutable('+15 days');
        $pastDate = new \DateTimeImmutable('-5 days');

        $expiringSoonAgent = new Agent();
        $expiringSoonAgent->setCode('TEST015');
        $expiringSoonAgent->setCompanyName('Expiring Soon Company');
        $expiringSoonAgent->setContactPerson('Expiring Soon Contact');
        $expiringSoonAgent->setPhone('13800138015');
        $expiringSoonAgent->setStatus(AgentStatusEnum::ACTIVE);
        $expiringSoonAgent->setLevel(AgentLevelEnum::A);
        $expiringSoonAgent->setExpiryDate($in5Days);
        self::getEntityManager()->persist($expiringSoonAgent);

        $expiringLaterAgent = new Agent();
        $expiringLaterAgent->setCode('TEST016');
        $expiringLaterAgent->setCompanyName('Expiring Later Company');
        $expiringLaterAgent->setContactPerson('Expiring Later Contact');
        $expiringLaterAgent->setPhone('13800138016');
        $expiringLaterAgent->setStatus(AgentStatusEnum::ACTIVE);
        $expiringLaterAgent->setLevel(AgentLevelEnum::B);
        $expiringLaterAgent->setExpiryDate($in15Days);
        self::getEntityManager()->persist($expiringLaterAgent);

        $alreadyExpiredAgent = new Agent();
        $alreadyExpiredAgent->setCode('TEST017');
        $alreadyExpiredAgent->setCompanyName('Already Expired Company');
        $alreadyExpiredAgent->setContactPerson('Already Expired Contact');
        $alreadyExpiredAgent->setPhone('13800138017');
        $alreadyExpiredAgent->setStatus(AgentStatusEnum::ACTIVE);
        $alreadyExpiredAgent->setLevel(AgentLevelEnum::C);
        $alreadyExpiredAgent->setExpiryDate($pastDate);
        self::getEntityManager()->persist($alreadyExpiredAgent);

        self::getEntityManager()->flush();

        $results = $this->repository->findAgentsExpiringInDays(10);

        $foundExpiringSoonAgent = false;
        $foundExpiringLaterAgent = false;
        $foundAlreadyExpiredAgent = false;
        foreach ($results as $result) {
            if ($result->getId() === $expiringSoonAgent->getId()) {
                $foundExpiringSoonAgent = true;
            }
            if ($result->getId() === $expiringLaterAgent->getId()) {
                $foundExpiringLaterAgent = true;
            }
            if ($result->getId() === $alreadyExpiredAgent->getId()) {
                $foundAlreadyExpiredAgent = true;
            }
        }
        $this->assertTrue($foundExpiringSoonAgent);
        $this->assertFalse($foundExpiringLaterAgent);
        $this->assertFalse($foundAlreadyExpiredAgent);
    }

    public function testFindWithNonExistentId(): void
    {
        $result = $this->repository->find(99999);
        $this->assertNull($result);
    }

    public function testFindOneByWithNonExistentCriteria(): void
    {
        $result = $this->repository->findOneBy(['code' => 'NONEXISTENT']);
        $this->assertNull($result);

        $resultByCompany = $this->repository->findOneBy(['companyName' => 'Nonexistent Company']);
        $this->assertNull($resultByCompany);
    }

    public function testFindByWithOrderByClause(): void
    {
        $agent1 = new Agent();
        $agent1->setCode('TEST_ORDER_A');
        $agent1->setCompanyName('Order Test A');
        $agent1->setContactPerson('Contact A');
        $agent1->setPhone('13800138022');
        $agent1->setStatus(AgentStatusEnum::ACTIVE);
        $agent1->setLevel(AgentLevelEnum::A);
        self::getEntityManager()->persist($agent1);

        $agent2 = new Agent();
        $agent2->setCode('TEST_ORDER_B');
        $agent2->setCompanyName('Order Test B');
        $agent2->setContactPerson('Contact B');
        $agent2->setPhone('13800138023');
        $agent2->setStatus(AgentStatusEnum::ACTIVE);
        $agent2->setLevel(AgentLevelEnum::A);
        self::getEntityManager()->persist($agent2);

        self::getEntityManager()->flush();

        $resultsAsc = $this->repository->findBy(['level' => AgentLevelEnum::A], ['code' => 'ASC']);
        $this->assertGreaterThanOrEqual(2, count($resultsAsc));

        $resultsDesc = $this->repository->findBy(['level' => AgentLevelEnum::A], ['code' => 'DESC']);
        $this->assertGreaterThanOrEqual(2, count($resultsDesc));

        $foundA = false;
        $foundB = false;
        foreach ($resultsAsc as $result) {
            if ('TEST_ORDER_A' === $result->getCode()) {
                $foundA = true;
            }
            if ('TEST_ORDER_B' === $result->getCode()) {
                $foundB = true;
            }
        }
        $this->assertTrue($foundA);
        $this->assertTrue($foundB);
    }

    public function testFindByWithLimitAndOffset(): void
    {
        for ($i = 1; $i <= 5; ++$i) {
            $agent = new Agent();
            $agent->setCode('TEST_LIMIT_' . $i);
            $agent->setCompanyName('Limit Test ' . $i);
            $agent->setContactPerson('Contact ' . $i);
            $agent->setPhone('1380013802' . $i);
            $agent->setStatus(AgentStatusEnum::ACTIVE);
            $agent->setLevel(AgentLevelEnum::B);
            self::getEntityManager()->persist($agent);
        }
        self::getEntityManager()->flush();

        $results = $this->repository->findBy(['level' => AgentLevelEnum::B], ['code' => 'ASC'], 2, 1);
        $this->assertLessThanOrEqual(2, count($results));
    }

    public function testSaveMethodShouldPersistEntity(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_SAVE');
        $agent->setCompanyName('Save Test Company');
        $agent->setContactPerson('Save Contact');
        $agent->setPhone('13800138026');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::C);

        $this->repository->save($agent);

        $savedAgent = $this->repository->findByCode('TEST_SAVE');
        $this->assertInstanceOf(Agent::class, $savedAgent);
        $this->assertSame('TEST_SAVE', $savedAgent->getCode());
        $this->assertSame('Save Test Company', $savedAgent->getCompanyName());
    }

    public function testSaveMethodWithoutFlush(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_SAVE_NO_FLUSH');
        $agent->setCompanyName('Save No Flush Company');
        $agent->setContactPerson('Save Contact');
        $agent->setPhone('13800138027');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::A);

        $this->repository->save($agent, false);
        self::getEntityManager()->flush();

        $savedAgent = $this->repository->findByCode('TEST_SAVE_NO_FLUSH');
        $this->assertInstanceOf(Agent::class, $savedAgent);
        $this->assertSame('TEST_SAVE_NO_FLUSH', $savedAgent->getCode());
    }

    public function testRemoveMethodShouldDeleteEntity(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_REMOVE');
        $agent->setCompanyName('Remove Test Company');
        $agent->setContactPerson('Remove Contact');
        $agent->setPhone('13800138028');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::B);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $savedAgent = $this->repository->findByCode('TEST_REMOVE');
        $this->assertInstanceOf(Agent::class, $savedAgent);

        $this->repository->remove($savedAgent);

        $deletedAgent = $this->repository->findByCode('TEST_REMOVE');
        $this->assertNull($deletedAgent);
    }

    public function testRemoveMethodWithoutFlush(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_REMOVE_NO_FLUSH');
        $agent->setCompanyName('Remove No Flush Company');
        $agent->setContactPerson('Remove Contact');
        $agent->setPhone('13800138029');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::C);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $savedAgent = $this->repository->findByCode('TEST_REMOVE_NO_FLUSH');
        $this->assertInstanceOf(Agent::class, $savedAgent);

        $this->repository->remove($savedAgent, false);
        self::getEntityManager()->flush();

        $deletedAgent = $this->repository->findByCode('TEST_REMOVE_NO_FLUSH');
        $this->assertNull($deletedAgent);
    }

    public function testFindByNullableFields(): void
    {
        $agentWithEmail = new Agent();
        $agentWithEmail->setCode('TEST_EMAIL');
        $agentWithEmail->setCompanyName('Email Test Company');
        $agentWithEmail->setContactPerson('Email Contact');
        $agentWithEmail->setPhone('13800138030');
        $agentWithEmail->setEmail('test@example.com');
        $agentWithEmail->setStatus(AgentStatusEnum::ACTIVE);
        $agentWithEmail->setLevel(AgentLevelEnum::A);
        self::getEntityManager()->persist($agentWithEmail);

        $agentWithoutEmail = new Agent();
        $agentWithoutEmail->setCode('TEST_NO_EMAIL');
        $agentWithoutEmail->setCompanyName('No Email Test Company');
        $agentWithoutEmail->setContactPerson('No Email Contact');
        $agentWithoutEmail->setPhone('13800138031');
        $agentWithoutEmail->setStatus(AgentStatusEnum::ACTIVE);
        $agentWithoutEmail->setLevel(AgentLevelEnum::B);
        self::getEntityManager()->persist($agentWithoutEmail);

        self::getEntityManager()->flush();

        $agentsWithEmail = $this->repository->findBy(['email' => 'test@example.com']);
        $this->assertGreaterThanOrEqual(1, count($agentsWithEmail));

        $agentsWithNullEmail = $this->repository->findBy(['email' => null]);
        $this->assertGreaterThanOrEqual(1, count($agentsWithNullEmail));
    }

    public function testCountWithCriteria(): void
    {
        $initialActiveCount = $this->repository->count(['status' => AgentStatusEnum::ACTIVE]);
        $initialInactiveCount = $this->repository->count(['status' => AgentStatusEnum::DISABLED]);

        $activeAgent = new Agent();
        $activeAgent->setCode('TEST_COUNT_ACTIVE');
        $activeAgent->setCompanyName('Count Active Company');
        $activeAgent->setContactPerson('Active Contact');
        $activeAgent->setPhone('13800138032');
        $activeAgent->setStatus(AgentStatusEnum::ACTIVE);
        $activeAgent->setLevel(AgentLevelEnum::A);
        self::getEntityManager()->persist($activeAgent);

        $inactiveAgent = new Agent();
        $inactiveAgent->setCode('TEST_COUNT_INACTIVE');
        $inactiveAgent->setCompanyName('Count Inactive Company');
        $inactiveAgent->setContactPerson('Inactive Contact');
        $inactiveAgent->setPhone('13800138033');
        $inactiveAgent->setStatus(AgentStatusEnum::DISABLED);
        $inactiveAgent->setLevel(AgentLevelEnum::B);
        self::getEntityManager()->persist($inactiveAgent);

        self::getEntityManager()->flush();

        $activeCount = $this->repository->count(['status' => AgentStatusEnum::ACTIVE]);
        $inactiveCount = $this->repository->count(['status' => AgentStatusEnum::DISABLED]);

        $this->assertSame($initialActiveCount + 1, $activeCount);
        $this->assertSame($initialInactiveCount + 1, $inactiveCount);
    }

    public function testNullFieldQuery(): void
    {
        $agent = new Agent();
        $agent->setCode('NULL_FIELD');
        $agent->setCompanyName('NULL Field Company');
        $agent->setContactPerson('Contact Person');
        $agent->setPhone('13800138043');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::A);
        $agent->setEmail(null);
        $agent->setExpiryDate(null);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $results = $this->repository->findBy(['email' => null]);
        $this->assertGreaterThanOrEqual(1, count($results));

        $foundNullEmail = false;
        foreach ($results as $result) {
            if (null === $result->getEmail()) {
                $foundNullEmail = true;
                break;
            }
        }
        $this->assertTrue($foundNullEmail);

        $expiryResults = $this->repository->findBy(['expiryDate' => null]);
        $this->assertGreaterThanOrEqual(1, count($expiryResults));
    }

    public function testCountNullFieldQuery(): void
    {
        $agent = new Agent();
        $agent->setCode('COUNT_NULL');
        $agent->setCompanyName('Count NULL Company');
        $agent->setContactPerson('Contact Person');
        $agent->setPhone('13800138044');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::B);
        $agent->setEmail(null);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $count = $this->repository->count(['email' => null]);
        $this->assertGreaterThanOrEqual(1, $count);

        $expiryCount = $this->repository->count(['expiryDate' => null]);
        $this->assertGreaterThanOrEqual(1, $expiryCount);

        $userIdCount = $this->repository->count(['userId' => null]);
        $this->assertGreaterThanOrEqual(1, $userIdCount);

        $licenseUrlCount = $this->repository->count(['licenseUrl' => null]);
        $this->assertGreaterThanOrEqual(1, $licenseUrlCount);
    }

    public function testFindOneByWithOrderBy(): void
    {
        $agent1 = new Agent();
        $agent1->setCode('ORDER_ONE_A');
        $agent1->setCompanyName('Order One Company A');
        $agent1->setContactPerson('Contact A');
        $agent1->setPhone('13800138041');
        $agent1->setStatus(AgentStatusEnum::ACTIVE);
        $agent1->setLevel(AgentLevelEnum::C);
        self::getEntityManager()->persist($agent1);

        $agent2 = new Agent();
        $agent2->setCode('ORDER_ONE_B');
        $agent2->setCompanyName('Order One Company B');
        $agent2->setContactPerson('Contact B');
        $agent2->setPhone('13800138042');
        $agent2->setStatus(AgentStatusEnum::ACTIVE);
        $agent2->setLevel(AgentLevelEnum::C);
        self::getEntityManager()->persist($agent2);

        self::getEntityManager()->flush();

        $resultAsc = $this->repository->findOneBy(['level' => AgentLevelEnum::C], ['code' => 'ASC']);
        $this->assertInstanceOf(Agent::class, $resultAsc);

        $resultDesc = $this->repository->findOneBy(['level' => AgentLevelEnum::C], ['code' => 'DESC']);
        $this->assertInstanceOf(Agent::class, $resultDesc);
    }

    /**
     * @return ServiceEntityRepository<Agent>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): object
    {
        $agent = new Agent();
        $agent->setCode('AGENT' . uniqid());
        $agent->setCompanyName('Test Company - ' . uniqid());
        $agent->setContactPerson('Test Contact - ' . uniqid());
        $agent->setPhone('138' . sprintf('%08d', rand(10000000, 99999999)));
        $agent->setEmail('test' . uniqid() . '@example.com');
        $agent->setCommissionRate('10.00');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::C);

        return $agent;
    }
}
