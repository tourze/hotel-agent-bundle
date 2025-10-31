<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentHotelMapping;
use Tourze\HotelAgentBundle\Enum\AgentLevelEnum;
use Tourze\HotelAgentBundle\Enum\AgentStatusEnum;
use Tourze\HotelAgentBundle\Repository\AgentHotelMappingRepository;
use Tourze\HotelProfileBundle\Entity\Hotel;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(AgentHotelMappingRepository::class)]
#[RunTestsInSeparateProcesses]
final class AgentHotelMappingRepositoryTest extends AbstractRepositoryTestCase
{
    private AgentHotelMappingRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(AgentHotelMappingRepository::class);
    }

    private function createMockHotel(int $hotelId, string $name = 'Test Hotel'): Hotel
    {
        $hotel = new Hotel();
        $hotel->setName($name . ' ' . $hotelId);
        $hotel->setAddress('Test Address ' . $hotelId);
        $hotel->setContactPerson('Contact Person ' . $hotelId);
        $hotel->setPhone('1380013' . str_pad((string) $hotelId, 4, '0', STR_PAD_LEFT));
        self::getEntityManager()->persist($hotel);
        self::getEntityManager()->flush();

        return $hotel;
    }

    private function requireEntityId(Agent|Hotel|AgentHotelMapping $entity): int
    {
        $id = $entity->getId();
        self::assertNotNull($id, '实体在 flush 后应该拥有 ID');

        return $id;
    }

    public function testFindByAgentId(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST001');
        $agent->setCompanyName('Test Company');
        $agent->setContactPerson('Test Contact');
        $agent->setPhone('13800138001');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::C);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $hotel1 = $this->createMockHotel(101);
        $hotel2 = $this->createMockHotel(102);

        $mapping1 = new AgentHotelMapping();
        $mapping1->setAgent($agent);
        $mapping1->setHotel($hotel1);
        $mapping1->setRoomTypeIds([1, 2, 3]);
        self::getEntityManager()->persist($mapping1);

        $mapping2 = new AgentHotelMapping();
        $mapping2->setAgent($agent);
        $mapping2->setHotel($hotel2);
        $mapping2->setRoomTypeIds([4, 5, 6]);
        self::getEntityManager()->persist($mapping2);

        self::getEntityManager()->flush();

        $agentId = $agent->getId();
        $results = $this->repository->findByAgentId($agentId);
        $this->assertCount(2, $results);
        $this->assertInstanceOf(AgentHotelMapping::class, $results[0]);
        $this->assertInstanceOf(AgentHotelMapping::class, $results[1]);
    }

    public function testFindByHotelId(): void
    {
        $agent1 = new Agent();
        $agent1->setCode('TEST002');
        $agent1->setCompanyName('Test Company 1');
        $agent1->setContactPerson('Test Contact 1');
        $agent1->setPhone('13800138002');
        $agent1->setStatus(AgentStatusEnum::ACTIVE);
        $agent1->setLevel(AgentLevelEnum::B);
        self::getEntityManager()->persist($agent1);

        $agent2 = new Agent();
        $agent2->setCode('TEST003');
        $agent2->setCompanyName('Test Company 2');
        $agent2->setContactPerson('Test Contact 2');
        $agent2->setPhone('13800138003');
        $agent2->setStatus(AgentStatusEnum::ACTIVE);
        $agent2->setLevel(AgentLevelEnum::A);
        self::getEntityManager()->persist($agent2);

        self::getEntityManager()->flush();

        $hotel = $this->createMockHotel(201);

        $mapping1 = new AgentHotelMapping();
        $mapping1->setAgent($agent1);
        $mapping1->setHotel($hotel);
        $mapping1->setRoomTypeIds([1, 2]);
        self::getEntityManager()->persist($mapping1);

        $mapping2 = new AgentHotelMapping();
        $mapping2->setAgent($agent2);
        $mapping2->setHotel($hotel);
        $mapping2->setRoomTypeIds([3, 4]);
        self::getEntityManager()->persist($mapping2);

        self::getEntityManager()->flush();

        $hotelId = $hotel->getId();
        $this->assertNotNull($hotelId, 'Hotel ID should not be null after persistence');
        $results = $this->repository->findByHotelId($hotelId);
        $this->assertGreaterThanOrEqual(2, count($results));

        $foundMapping1 = false;
        $foundMapping2 = false;
        foreach ($results as $result) {
            if ($result->getId() === $mapping1->getId()) {
                $foundMapping1 = true;
            }
            if ($result->getId() === $mapping2->getId()) {
                $foundMapping2 = true;
            }
        }
        $this->assertTrue($foundMapping1);
        $this->assertTrue($foundMapping2);
    }

    public function testFindByAgentAndHotel(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST004');
        $agent->setCompanyName('Test Company 4');
        $agent->setContactPerson('Test Contact 4');
        $agent->setPhone('13800138004');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::C);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $hotel = $this->createMockHotel(301);

        $mapping = new AgentHotelMapping();
        $mapping->setAgent($agent);
        $mapping->setHotel($hotel);
        $mapping->setRoomTypeIds([1, 2, 3]);
        self::getEntityManager()->persist($mapping);

        self::getEntityManager()->flush();

        $agentId = $this->requireEntityId($agent);
        $hotelId = $this->requireEntityId($hotel);
        $result = $this->repository->findByAgentAndHotel($agentId, $hotelId);
        $this->assertInstanceOf(AgentHotelMapping::class, $result);
        $this->assertSame($mapping->getId(), $result->getId());

        $agentIdForNotFound = $this->requireEntityId($agent);
        $notFound = $this->repository->findByAgentAndHotel($agentIdForNotFound, 999);
        $this->assertNull($notFound);
    }

    public function testFindByRoomTypeId(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST005');
        $agent->setCompanyName('Test Company 5');
        $agent->setContactPerson('Test Contact 5');
        $agent->setPhone('13800138005');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::B);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $roomTypeId = 100;

        $hotel1 = $this->createMockHotel(401);
        $hotel2 = $this->createMockHotel(402);
        $hotel3 = $this->createMockHotel(403);

        $mapping1 = new AgentHotelMapping();
        $mapping1->setAgent($agent);
        $mapping1->setHotel($hotel1);
        $mapping1->setRoomTypeIds([$roomTypeId, 101, 102]);
        self::getEntityManager()->persist($mapping1);

        $mapping2 = new AgentHotelMapping();
        $mapping2->setAgent($agent);
        $mapping2->setHotel($hotel2);
        $mapping2->setRoomTypeIds([103, $roomTypeId, 104]);
        self::getEntityManager()->persist($mapping2);

        $mapping3 = new AgentHotelMapping();
        $mapping3->setAgent($agent);
        $mapping3->setHotel($hotel3);
        $mapping3->setRoomTypeIds([105, 106, 107]);
        self::getEntityManager()->persist($mapping3);

        self::getEntityManager()->flush();

        $results = $this->repository->findByRoomTypeId($roomTypeId);
        $this->assertGreaterThanOrEqual(2, count($results));

        $foundMapping1 = false;
        $foundMapping2 = false;
        $foundMapping3 = false;
        foreach ($results as $result) {
            if ($result->getId() === $mapping1->getId()) {
                $foundMapping1 = true;
            }
            if ($result->getId() === $mapping2->getId()) {
                $foundMapping2 = true;
            }
            if ($result->getId() === $mapping3->getId()) {
                $foundMapping3 = true;
            }
        }
        $this->assertTrue($foundMapping1);
        $this->assertTrue($foundMapping2);
        $this->assertFalse($foundMapping3);
    }

    public function testFindByAgentIdWithEmptyResult(): void
    {
        $results = $this->repository->findByAgentId(99999);
        $this->assertEmpty($results);
    }

    public function testFindByHotelIdWithEmptyResult(): void
    {
        $results = $this->repository->findByHotelId(99999);
        $this->assertEmpty($results);
    }

    public function testFindByRoomTypeIdWithEmptyResult(): void
    {
        $results = $this->repository->findByRoomTypeId(99999);
        $this->assertEmpty($results);
    }

    public function testFindWithNonExistentId(): void
    {
        $result = $this->repository->find(99999);
        $this->assertNull($result);
    }

    public function testFindOneByWithNonExistentCriteria(): void
    {
        $result = $this->repository->findOneBy(['hotel' => 99999]);
        $this->assertNull($result);

        $agentResult = $this->repository->findOneBy(['agent' => 99999]);
        $this->assertNull($agentResult);
    }

    public function testFindByWithOrderByClause(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_ORDER');
        $agent->setCompanyName('Order Test Company');
        $agent->setContactPerson('Contact Person');
        $agent->setPhone('13800138054');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::B);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $hotel1 = $this->createMockHotel(501);
        $hotel2 = $this->createMockHotel(502);

        $mapping1 = new AgentHotelMapping();
        $mapping1->setAgent($agent);
        $mapping1->setHotel($hotel1);
        $mapping1->setRoomTypeIds([1, 2]);
        self::getEntityManager()->persist($mapping1);

        $mapping2 = new AgentHotelMapping();
        $mapping2->setAgent($agent);
        $mapping2->setHotel($hotel2);
        $mapping2->setRoomTypeIds([3, 4]);
        self::getEntityManager()->persist($mapping2);

        self::getEntityManager()->flush();

        $resultsAsc = $this->repository->findBy(['agent' => $agent], ['hotel' => 'ASC']);
        $this->assertGreaterThanOrEqual(2, count($resultsAsc));

        $resultsDesc = $this->repository->findBy(['agent' => $agent], ['hotel' => 'DESC']);
        $this->assertGreaterThanOrEqual(2, count($resultsDesc));
    }

    public function testFindByWithLimitAndOffset(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_LIMIT');
        $agent->setCompanyName('Limit Test Company');
        $agent->setContactPerson('Contact Person');
        $agent->setPhone('13800138055');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::A);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        for ($i = 1; $i <= 5; ++$i) {
            $hotel = $this->createMockHotel(600 + $i);
            $mapping = new AgentHotelMapping();
            $mapping->setAgent($agent);
            $mapping->setHotel($hotel);
            $mapping->setRoomTypeIds([$i * 10, $i * 10 + 1]);
            self::getEntityManager()->persist($mapping);
        }
        self::getEntityManager()->flush();

        $results = $this->repository->findBy(['agent' => $agent], ['hotel' => 'ASC'], 2, 1);
        $this->assertLessThanOrEqual(2, count($results));
    }

    public function testSaveMethodShouldPersistEntity(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_SAVE');
        $agent->setCompanyName('Save Test Company');
        $agent->setContactPerson('Save Contact');
        $agent->setPhone('13800138056');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::C);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $hotel = $this->createMockHotel(701);

        $mapping = new AgentHotelMapping();
        $mapping->setAgent($agent);
        $mapping->setHotel($hotel);
        $mapping->setRoomTypeIds([21, 22, 23]);

        $this->repository->save($mapping);

        $agentId = $this->requireEntityId($agent);
        $hotelId = $this->requireEntityId($hotel);
        $savedMapping = $this->repository->findByAgentAndHotel($agentId, $hotelId);
        $this->assertInstanceOf(AgentHotelMapping::class, $savedMapping);
        $savedHotel = $savedMapping->getHotel();
        $this->assertNotNull($savedHotel, 'Hotel should not be null');
        $this->assertSame($hotel->getId(), $savedHotel->getId());
        $this->assertSame([21, 22, 23], $savedMapping->getRoomTypeIds());
    }

    public function testSaveMethodWithoutFlush(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_SAVE_NO_FLUSH');
        $agent->setCompanyName('Save No Flush Company');
        $agent->setContactPerson('Save Contact');
        $agent->setPhone('13800138057');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::B);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $hotel = $this->createMockHotel(801);

        $mapping = new AgentHotelMapping();
        $mapping->setAgent($agent);
        $mapping->setHotel($hotel);
        $mapping->setRoomTypeIds([31, 32, 33]);

        $this->repository->save($mapping, false);
        self::getEntityManager()->flush();

        $agentId = $this->requireEntityId($agent);
        $hotelId = $this->requireEntityId($hotel);
        $savedMapping = $this->repository->findByAgentAndHotel($agentId, $hotelId);
        $this->assertInstanceOf(AgentHotelMapping::class, $savedMapping);
        $savedHotel = $savedMapping->getHotel();
        $this->assertNotNull($savedHotel, 'Hotel should not be null');
        $this->assertSame($hotel->getId(), $savedHotel->getId());
    }

    public function testRemoveMethodShouldDeleteEntity(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_REMOVE');
        $agent->setCompanyName('Remove Test Company');
        $agent->setContactPerson('Remove Contact');
        $agent->setPhone('13800138058');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::A);
        self::getEntityManager()->persist($agent);

        $hotel = $this->createMockHotel(901);

        $mapping = new AgentHotelMapping();
        $mapping->setAgent($agent);
        $mapping->setHotel($hotel);
        $mapping->setRoomTypeIds([41, 42, 43]);
        self::getEntityManager()->persist($mapping);
        self::getEntityManager()->flush();

        $agentId = $this->requireEntityId($agent);
        $hotelId = $this->requireEntityId($hotel);
        $savedMapping = $this->repository->findByAgentAndHotel($agentId, $hotelId);
        $this->assertInstanceOf(AgentHotelMapping::class, $savedMapping);

        $this->repository->remove($savedMapping);

        $deletedMapping = $this->repository->findByAgentAndHotel($agentId, $hotelId);
        $this->assertNull($deletedMapping);
    }

    public function testRemoveMethodWithoutFlush(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_REMOVE_NO_FLUSH');
        $agent->setCompanyName('Remove No Flush Company');
        $agent->setContactPerson('Remove Contact');
        $agent->setPhone('13800138059');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::C);
        self::getEntityManager()->persist($agent);

        $hotel = $this->createMockHotel(1001);

        $mapping = new AgentHotelMapping();
        $mapping->setAgent($agent);
        $mapping->setHotel($hotel);
        $mapping->setRoomTypeIds([51, 52, 53]);
        self::getEntityManager()->persist($mapping);
        self::getEntityManager()->flush();

        $agentId = $this->requireEntityId($agent);
        $hotelId = $this->requireEntityId($hotel);
        $savedMapping = $this->repository->findByAgentAndHotel($agentId, $hotelId);
        $this->assertInstanceOf(AgentHotelMapping::class, $savedMapping);

        $this->repository->remove($savedMapping, false);
        self::getEntityManager()->flush();

        $deletedMapping = $this->repository->findByAgentAndHotel($agentId, $hotelId);
        $this->assertNull($deletedMapping);
    }

    public function testCountWithCriteria(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_COUNT');
        $agent->setCompanyName('Count Test Company');
        $agent->setContactPerson('Count Contact');
        $agent->setPhone('13800138060');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::B);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $initialCount = $this->repository->count(['agent' => $agent]);

        $hotel1 = $this->createMockHotel(1101);
        $hotel2 = $this->createMockHotel(1102);

        $mapping1 = new AgentHotelMapping();
        $mapping1->setAgent($agent);
        $mapping1->setHotel($hotel1);
        $mapping1->setRoomTypeIds([61, 62]);
        self::getEntityManager()->persist($mapping1);

        $mapping2 = new AgentHotelMapping();
        $mapping2->setAgent($agent);
        $mapping2->setHotel($hotel2);
        $mapping2->setRoomTypeIds([63, 64]);
        self::getEntityManager()->persist($mapping2);

        self::getEntityManager()->flush();

        $finalCount = $this->repository->count(['agent' => $agent]);
        $this->assertSame($initialCount + 2, $finalCount);
    }

    public function testFindByNullableFields(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_NULL');
        $agent->setCompanyName('NULL Test Company');
        $agent->setContactPerson('NULL Contact');
        $agent->setPhone('13800138061');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::C);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $hotel = $this->createMockHotel(1201);

        $mapping = new AgentHotelMapping();
        $mapping->setAgent($agent);
        $mapping->setHotel($hotel);
        $mapping->setRoomTypeIds([]);
        self::getEntityManager()->persist($mapping);
        self::getEntityManager()->flush();

        $results = $this->repository->findBy(['agent' => $agent]);
        $this->assertGreaterThanOrEqual(1, count($results));

        $resultWithEmptyArray = false;
        foreach ($results as $result) {
            if ([] === $result->getRoomTypeIds()) {
                $resultWithEmptyArray = true;
                break;
            }
        }
        $this->assertTrue($resultWithEmptyArray);
    }

    public function testCountAssociationQuery(): void
    {
        $agent1 = new Agent();
        $agent1->setCode('ASSOC_COUNT_1');
        $agent1->setCompanyName('Association Count Company 1');
        $agent1->setContactPerson('Contact Person 1');
        $agent1->setPhone('13800138078');
        $agent1->setStatus(AgentStatusEnum::ACTIVE);
        $agent1->setLevel(AgentLevelEnum::A);
        self::getEntityManager()->persist($agent1);

        $agent2 = new Agent();
        $agent2->setCode('ASSOC_COUNT_2');
        $agent2->setCompanyName('Association Count Company 2');
        $agent2->setContactPerson('Contact Person 2');
        $agent2->setPhone('13800138079');
        $agent2->setStatus(AgentStatusEnum::ACTIVE);
        $agent2->setLevel(AgentLevelEnum::C);
        self::getEntityManager()->persist($agent2);

        self::getEntityManager()->flush();

        $hotel1 = $this->createMockHotel(1201);
        $hotel2 = $this->createMockHotel(1202);

        $mapping1 = new AgentHotelMapping();
        $mapping1->setAgent($agent1);
        $mapping1->setHotel($hotel1);
        $mapping1->setRoomTypeIds([1, 2]);
        self::getEntityManager()->persist($mapping1);

        $mapping2 = new AgentHotelMapping();
        $mapping2->setAgent($agent2);
        $mapping2->setHotel($hotel2);
        $mapping2->setRoomTypeIds([3, 4]);
        self::getEntityManager()->persist($mapping2);

        self::getEntityManager()->flush();

        $agent1Count = $this->repository->count(['agent' => $agent1]);
        $this->assertSame(1, $agent1Count);

        $agent2Count = $this->repository->count(['agent' => $agent2]);
        $this->assertSame(1, $agent2Count);

        $hotel1Count = $this->repository->count(['hotel' => $hotel1]);
        $this->assertSame(1, $hotel1Count);

        $hotel2Count = $this->repository->count(['hotel' => $hotel2]);
        $this->assertSame(1, $hotel2Count);
    }

    public function testAssociationQuery(): void
    {
        $agent1 = new Agent();
        $agent1->setCode('ASSOC_QUERY_1');
        $agent1->setCompanyName('Association Query Company 1');
        $agent1->setContactPerson('Contact Person 1');
        $agent1->setPhone('13800138080');
        $agent1->setStatus(AgentStatusEnum::ACTIVE);
        $agent1->setLevel(AgentLevelEnum::B);
        self::getEntityManager()->persist($agent1);

        $agent2 = new Agent();
        $agent2->setCode('ASSOC_QUERY_2');
        $agent2->setCompanyName('Association Query Company 2');
        $agent2->setContactPerson('Contact Person 2');
        $agent2->setPhone('13800138081');
        $agent2->setStatus(AgentStatusEnum::ACTIVE);
        $agent2->setLevel(AgentLevelEnum::A);
        self::getEntityManager()->persist($agent2);

        self::getEntityManager()->flush();

        $hotel1 = $this->createMockHotel(1301);
        $hotel2 = $this->createMockHotel(1302);

        $mapping1 = new AgentHotelMapping();
        $mapping1->setAgent($agent1);
        $mapping1->setHotel($hotel1);
        $mapping1->setRoomTypeIds([1, 2]);
        self::getEntityManager()->persist($mapping1);

        $mapping2 = new AgentHotelMapping();
        $mapping2->setAgent($agent2);
        $mapping2->setHotel($hotel2);
        $mapping2->setRoomTypeIds([3, 4]);
        self::getEntityManager()->persist($mapping2);

        self::getEntityManager()->flush();

        $agent1Mappings = $this->repository->findBy(['agent' => $agent1]);
        $this->assertCount(1, $agent1Mappings);
        $this->assertSame($mapping1->getId(), $agent1Mappings[0]->getId());

        $agent2Mappings = $this->repository->findBy(['agent' => $agent2]);
        $this->assertCount(1, $agent2Mappings);
        $this->assertSame($mapping2->getId(), $agent2Mappings[0]->getId());

        $hotel1Mappings = $this->repository->findBy(['hotel' => $hotel1]);
        $this->assertCount(1, $hotel1Mappings);
        $this->assertSame($mapping1->getId(), $hotel1Mappings[0]->getId());

        $hotel2Mappings = $this->repository->findBy(['hotel' => $hotel2]);
        $this->assertCount(1, $hotel2Mappings);
        $this->assertSame($mapping2->getId(), $hotel2Mappings[0]->getId());
    }

    public function testNullFieldQuery(): void
    {
        $agent = new Agent();
        $agent->setCode('NULL_QUERY');
        $agent->setCompanyName('NULL Query Company');
        $agent->setContactPerson('Contact Person');
        $agent->setPhone('13800138082');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::C);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $hotel = $this->createMockHotel(1401);

        $mapping = new AgentHotelMapping();
        $mapping->setAgent($agent);
        $mapping->setHotel($hotel);
        $mapping->setRoomTypeIds([]);
        self::getEntityManager()->persist($mapping);
        self::getEntityManager()->flush();

        $results = $this->repository->findBy(['agent' => $agent]);
        $this->assertGreaterThanOrEqual(1, count($results));

        $foundEmptyRoomTypes = false;
        foreach ($results as $result) {
            if ([] === $result->getRoomTypeIds()) {
                $foundEmptyRoomTypes = true;
                break;
            }
        }
        $this->assertTrue($foundEmptyRoomTypes);
    }

    public function testCountNullFieldQuery(): void
    {
        $agent = new Agent();
        $agent->setCode('COUNT_NULL');
        $agent->setCompanyName('Count NULL Company');
        $agent->setContactPerson('Contact Person');
        $agent->setPhone('13800138083');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::B);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $hotel = $this->createMockHotel(1501);

        $mapping = new AgentHotelMapping();
        $mapping->setAgent($agent);
        $mapping->setHotel($hotel);
        $mapping->setRoomTypeIds([]);
        self::getEntityManager()->persist($mapping);
        self::getEntityManager()->flush();

        $count = $this->repository->count(['agent' => $agent]);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    /**
     * @return ServiceEntityRepository<AgentHotelMapping>
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

        // 创建必需的 Hotel 实体
        $hotel = new Hotel();
        $hotel->setName('Test Hotel - ' . uniqid());
        $hotel->setAddress('Test Address - ' . uniqid());
        $hotel->setContactPerson('Hotel Contact - ' . uniqid());
        $hotel->setPhone('0571' . sprintf('%08d', rand(10000000, 99999999)));
        self::getEntityManager()->persist($hotel);

        self::getEntityManager()->flush();

        // 创建 AgentHotelMapping 实体
        $mapping = new AgentHotelMapping();
        $mapping->setAgent($agent);
        $mapping->setHotel($hotel);
        $mapping->setRoomTypeIds([rand(1, 100), rand(101, 200), rand(201, 300)]);

        return $mapping;
    }
}
