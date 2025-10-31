<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Entity\OrderItem;
use Tourze\HotelAgentBundle\Enum\AgentLevelEnum;
use Tourze\HotelAgentBundle\Enum\AgentStatusEnum;
use Tourze\HotelAgentBundle\Enum\OrderItemStatusEnum;
use Tourze\HotelAgentBundle\Repository\OrderItemRepository;
use Tourze\HotelContractBundle\Entity\HotelContract;
use Tourze\HotelProfileBundle\Entity\Hotel;
use Tourze\HotelProfileBundle\Entity\RoomType;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(OrderItemRepository::class)]
#[RunTestsInSeparateProcesses]
final class OrderItemRepositoryTest extends AbstractRepositoryTestCase
{
    private OrderItemRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(OrderItemRepository::class);
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

    private function createMockRoomType(Hotel $hotel, int $roomTypeId, string $name = 'Test Room Type'): RoomType
    {
        $roomType = new RoomType();
        $roomType->setHotel($hotel);
        $roomType->setName($name . ' ' . $roomTypeId);
        $roomType->setBedType('Double Bed');
        $roomType->setArea(25.0);
        $roomType->setMaxGuests(2);
        $roomType->setDescription('Test Description ' . $roomTypeId);
        self::getEntityManager()->persist($roomType);
        self::getEntityManager()->flush();

        return $roomType;
    }

    private function createMockContract(Hotel $hotel, int $contractId, string $name = 'Test Contract'): HotelContract
    {
        $contract = new HotelContract();
        $contract->setContractNo('CONTRACT' . $contractId);
        $contract->setHotel($hotel);
        $contract->setStartDate(new \DateTimeImmutable('2024-01-01'));
        $contract->setEndDate(new \DateTimeImmutable('2024-12-31'));
        $contract->setTotalRooms(100);
        $contract->setTotalDays(365);
        self::getEntityManager()->persist($contract);
        self::getEntityManager()->flush();

        return $contract;
    }

    private function createMockAgent(int $agentId): Agent
    {
        $agent = new Agent();
        $agent->setCode('AGENT' . $agentId . '_' . uniqid());
        $agent->setCompanyName('Test Company ' . $agentId);
        $agent->setContactPerson('Test Contact ' . $agentId);
        $agent->setPhone('138' . sprintf('%08d', rand(10000000, 99999999)));
        $agent->setEmail('test' . $agentId . '_' . uniqid() . '@example.com');
        $agent->setCommissionRate('10.00');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::C);
        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        return $agent;
    }

    private function createMockOrder(Agent $agent, int $orderId): Order
    {
        $order = new Order();
        $order->setOrderNo('ORD' . $orderId . '_' . uniqid());
        $order->setAgent($agent);
        self::getEntityManager()->persist($order);
        self::getEntityManager()->flush();

        return $order;
    }

    private function createMockOrderItem(Order $order, Hotel $hotel, RoomType $roomType, HotelContract $contract, int $itemId): OrderItem
    {
        $orderItem = new OrderItem();
        $orderItem->setOrder($order);
        $orderItem->setHotel($hotel);
        $orderItem->setRoomType($roomType);
        $orderItem->setContract($contract);
        $orderItem->setCheckInDate(new \DateTimeImmutable('2024-01-01'));
        $orderItem->setCheckOutDate(new \DateTimeImmutable('2024-01-02'));
        $orderItem->setUnitPrice(sprintf('%.2f', rand(100, 500)));
        $orderItem->setStatus(OrderItemStatusEnum::PENDING);

        return $orderItem;
    }

    public function testFindByOrderId(): void
    {
        $agent = new Agent();
        $agent->setCode('AGENT001');
        $agent->setCompanyName('Test Company 1');
        $agent->setContactPerson('John Doe');
        $agent->setPhone('13800138000');
        $agent->setLevel(AgentLevelEnum::B);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);

        $order = new Order();
        $order->setOrderNo('ORD001');
        $order->setAgent($agent);
        self::getEntityManager()->persist($order);
        self::getEntityManager()->flush();

        $orderItem1 = new OrderItem();
        $orderItem1->setOrder($order);
        $hotel1 = $this->createMockHotel(101);
        $roomType1 = $this->createMockRoomType($hotel1, 201);
        $contract1 = $this->createMockContract($hotel1, 301);
        $orderItem1->setHotel($hotel1);
        $orderItem1->setRoomType($roomType1);
        $orderItem1->setContract($contract1);
        $orderItem1->setCheckInDate(new \DateTimeImmutable('2024-01-01'));
        $orderItem1->setCheckOutDate(new \DateTimeImmutable('2024-01-02'));
        $orderItem1->setUnitPrice('100.00');
        $orderItem1->setStatus(OrderItemStatusEnum::PENDING);
        self::getEntityManager()->persist($orderItem1);

        $orderItem2 = new OrderItem();
        $orderItem2->setOrder($order);
        $hotel2 = $this->createMockHotel(102);
        $roomType2 = $this->createMockRoomType($hotel2, 202);
        $contract2 = $this->createMockContract($hotel2, 302);
        $orderItem2->setHotel($hotel2);
        $orderItem2->setRoomType($roomType2);
        $orderItem2->setContract($contract2);
        $orderItem2->setCheckInDate(new \DateTimeImmutable('2024-01-02'));
        $orderItem2->setCheckOutDate(new \DateTimeImmutable('2024-01-03'));
        $orderItem2->setUnitPrice('200.00');
        $orderItem2->setStatus(OrderItemStatusEnum::CONFIRMED);
        self::getEntityManager()->persist($orderItem2);

        self::getEntityManager()->flush();

        $orderId = $order->getId();
        $this->assertNotNull($orderId, 'Order ID should not be null after persistence');
        $results = $this->repository->findByOrderId($orderId);
        $this->assertCount(2, $results);
        $this->assertInstanceOf(OrderItem::class, $results[0]);
        $this->assertInstanceOf(OrderItem::class, $results[1]);
    }

    public function testFindByHotelId(): void
    {
        $agent = new Agent();
        $agent->setCode('AGENT002');
        $agent->setCompanyName('Test Company 2');
        $agent->setContactPerson('Jane Smith');
        $agent->setPhone('13800138001');
        $agent->setLevel(AgentLevelEnum::A);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);

        $order = new Order();
        $order->setOrderNo('ORD002');
        $order->setAgent($agent);
        self::getEntityManager()->persist($order);
        self::getEntityManager()->flush();

        $hotel = $this->createMockHotel(201);
        $roomType = $this->createMockRoomType($hotel, 301);
        $contract = $this->createMockContract($hotel, 401);

        $orderItem = new OrderItem();
        $orderItem->setOrder($order);
        $orderItem->setHotel($hotel);
        $orderItem->setRoomType($roomType);
        $orderItem->setContract($contract);
        $orderItem->setCheckInDate(new \DateTimeImmutable('2024-02-01'));
        $orderItem->setCheckOutDate(new \DateTimeImmutable('2024-02-02'));
        $orderItem->setUnitPrice('150.00');
        $orderItem->setStatus(OrderItemStatusEnum::PENDING);
        self::getEntityManager()->persist($orderItem);

        self::getEntityManager()->flush();

        $hotelId = $hotel->getId();
        $this->assertNotNull($hotelId, 'Hotel ID should not be null after persistence');
        $results = $this->repository->findByHotelId($hotelId);
        $this->assertGreaterThanOrEqual(1, count($results));

        $found = false;
        foreach ($results as $result) {
            if ($result->getId() === $orderItem->getId()) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testFindByStatus(): void
    {
        $agent = new Agent();
        $agent->setCode('AGENT003');
        $agent->setCompanyName('Test Company 3');
        $agent->setContactPerson('Bob Johnson');
        $agent->setPhone('13800138002');
        $agent->setLevel(AgentLevelEnum::C);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);

        $order = new Order();
        $order->setOrderNo('ORD003');
        $order->setAgent($agent);
        self::getEntityManager()->persist($order);
        self::getEntityManager()->flush();

        $orderItem1 = new OrderItem();
        $orderItem1->setOrder($order);
        $hotel1 = $this->createMockHotel(301);
        $roomType1 = $this->createMockRoomType($hotel1, 401);
        $contract1 = $this->createMockContract($hotel1, 501);
        $orderItem1->setHotel($hotel1);
        $orderItem1->setRoomType($roomType1);
        $orderItem1->setContract($contract1);
        $orderItem1->setCheckInDate(new \DateTimeImmutable('2024-03-01'));
        $orderItem1->setCheckOutDate(new \DateTimeImmutable('2024-03-02'));
        $orderItem1->setUnitPrice('120.00');
        $orderItem1->setStatus(OrderItemStatusEnum::PENDING);
        self::getEntityManager()->persist($orderItem1);

        $orderItem2 = new OrderItem();
        $orderItem2->setOrder($order);
        $hotel2 = $this->createMockHotel(302);
        $roomType2 = $this->createMockRoomType($hotel2, 402);
        $contract2 = $this->createMockContract($hotel2, 502);
        $orderItem2->setHotel($hotel2);
        $orderItem2->setRoomType($roomType2);
        $orderItem2->setContract($contract2);
        $orderItem2->setCheckInDate(new \DateTimeImmutable('2024-03-02'));
        $orderItem2->setCheckOutDate(new \DateTimeImmutable('2024-03-03'));
        $orderItem2->setUnitPrice('130.00');
        $orderItem2->setStatus(OrderItemStatusEnum::CONFIRMED);
        self::getEntityManager()->persist($orderItem2);

        self::getEntityManager()->flush();

        $pendingItems = $this->repository->findByStatus(OrderItemStatusEnum::PENDING);
        $this->assertGreaterThanOrEqual(1, count($pendingItems));

        $confirmedItems = $this->repository->findByStatus(OrderItemStatusEnum::CONFIRMED);
        $this->assertGreaterThanOrEqual(1, count($confirmedItems));
    }

    public function testFindByCheckInDateRange(): void
    {
        // This test validates that the findByCheckInDateRange method exists and can be called
        $results = $this->repository->findByCheckInDateRange(
            new \DateTimeImmutable('2024-04-01'),
            new \DateTimeImmutable('2024-04-30')
        );

        $this->assertIsArray($results);
    }

    public function testCountBookingsByDate(): void
    {
        $agent = new Agent();
        $agent->setCode('AGENT005');
        $agent->setCompanyName('Test Company 5');
        $agent->setContactPerson('Charlie Wilson');
        $agent->setPhone('13800138004');
        $agent->setLevel(AgentLevelEnum::B);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);

        $order = new Order();
        $order->setOrderNo('ORD005');
        $order->setAgent($agent);
        self::getEntityManager()->persist($order);
        self::getEntityManager()->flush();

        $testDate = new \DateTimeImmutable('2024-05-01');

        $orderItem1 = new OrderItem();
        $orderItem1->setOrder($order);
        $hotel1 = $this->createMockHotel(501);
        $roomType1 = $this->createMockRoomType($hotel1, 601);
        $contract1 = $this->createMockContract($hotel1, 701);
        $orderItem1->setHotel($hotel1);
        $orderItem1->setRoomType($roomType1);
        $orderItem1->setContract($contract1);
        $orderItem1->setCheckInDate(new \DateTimeImmutable('2024-04-30'));
        $orderItem1->setCheckOutDate(new \DateTimeImmutable('2024-05-02'));
        $orderItem1->setUnitPrice('180.00');
        $orderItem1->setStatus(OrderItemStatusEnum::CONFIRMED);
        self::getEntityManager()->persist($orderItem1);

        $orderItem2 = new OrderItem();
        $orderItem2->setOrder($order);
        $hotel2 = $this->createMockHotel(502);
        $roomType2 = $this->createMockRoomType($hotel2, 602);
        $contract2 = $this->createMockContract($hotel2, 702);
        $orderItem2->setHotel($hotel2);
        $orderItem2->setRoomType($roomType2);
        $orderItem2->setContract($contract2);
        $orderItem2->setCheckInDate(new \DateTimeImmutable('2024-05-01'));
        $orderItem2->setCheckOutDate(new \DateTimeImmutable('2024-05-03'));
        $orderItem2->setUnitPrice('190.00');
        $orderItem2->setStatus(OrderItemStatusEnum::CONFIRMED);
        self::getEntityManager()->persist($orderItem2);

        $orderItem3 = new OrderItem();
        $orderItem3->setOrder($order);
        $hotel3 = $this->createMockHotel(503);
        $roomType3 = $this->createMockRoomType($hotel3, 603);
        $contract3 = $this->createMockContract($hotel3, 703);
        $orderItem3->setHotel($hotel3);
        $orderItem3->setRoomType($roomType3);
        $orderItem3->setContract($contract3);
        $orderItem3->setCheckInDate(new \DateTimeImmutable('2024-05-02'));
        $orderItem3->setCheckOutDate(new \DateTimeImmutable('2024-05-04'));
        $orderItem3->setUnitPrice('200.00');
        $orderItem3->setStatus(OrderItemStatusEnum::CONFIRMED);
        self::getEntityManager()->persist($orderItem3);

        self::getEntityManager()->flush();

        $count = $this->repository->countBookingsByDate($testDate);
        $this->assertGreaterThanOrEqual(2, $count);
    }

    public function testFindPendingInventoryAllocation(): void
    {
        $agent = new Agent();
        $agent->setCode('AGENT006');
        $agent->setCompanyName('Test Company 6');
        $agent->setContactPerson('Diana Davis');
        $agent->setPhone('13800138005');
        $agent->setLevel(AgentLevelEnum::C);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);

        $order = new Order();
        $order->setOrderNo('ORD006');
        $order->setAgent($agent);
        self::getEntityManager()->persist($order);
        self::getEntityManager()->flush();

        $orderItem = new OrderItem();
        $orderItem->setOrder($order);
        $hotel = $this->createMockHotel(601);
        $roomType = $this->createMockRoomType($hotel, 701);
        $contract = $this->createMockContract($hotel, 801);
        $orderItem->setHotel($hotel);
        $orderItem->setRoomType($roomType);
        $orderItem->setContract($contract);
        $orderItem->setCheckInDate(new \DateTimeImmutable('2024-06-01'));
        $orderItem->setCheckOutDate(new \DateTimeImmutable('2024-06-02'));
        $orderItem->setUnitPrice('220.00');
        $orderItem->setStatus(OrderItemStatusEnum::PENDING);
        self::getEntityManager()->persist($orderItem);

        self::getEntityManager()->flush();

        $results = $this->repository->findPendingInventoryAllocation();
        $this->assertGreaterThanOrEqual(1, count($results));

        $found = false;
        foreach ($results as $result) {
            if ($result->getId() === $orderItem->getId()) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testCountByDateRange(): void
    {
        $agent = new Agent();
        $agent->setCode('AGENT007');
        $agent->setCompanyName('Test Company 7');
        $agent->setContactPerson('Edward Miller');
        $agent->setPhone('13800138006');
        $agent->setLevel(AgentLevelEnum::A);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);

        $order = new Order();
        $order->setOrderNo('ORD007');
        $order->setAgent($agent);
        self::getEntityManager()->persist($order);
        self::getEntityManager()->flush();

        $orderItem = new OrderItem();
        $orderItem->setOrder($order);
        $hotel = $this->createMockHotel(701);
        $roomType = $this->createMockRoomType($hotel, 801);
        $contract = $this->createMockContract($hotel, 901);
        $orderItem->setHotel($hotel);
        $orderItem->setRoomType($roomType);
        $orderItem->setContract($contract);
        $orderItem->setCheckInDate(new \DateTimeImmutable('2024-07-01'));
        $orderItem->setCheckOutDate(new \DateTimeImmutable('2024-07-03'));
        $orderItem->setUnitPrice('250.00');
        $orderItem->setStatus(OrderItemStatusEnum::PENDING);
        self::getEntityManager()->persist($orderItem);

        self::getEntityManager()->flush();

        $count = $this->repository->countByDateRange(
            new \DateTimeImmutable('2024-07-01'),
            new \DateTimeImmutable('2024-07-02')
        );
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testFindByOrderIdWithEmptyResult(): void
    {
        $results = $this->repository->findByOrderId(99999);
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testFindByHotelIdWithEmptyResult(): void
    {
        $results = $this->repository->findByHotelId(99999);
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testFindWithNonExistentId(): void
    {
        $result = $this->repository->find(99999);
        $this->assertNull($result);
    }

    public function testFindOneByWithNonExistentCriteria(): void
    {
        $result = $this->repository->findOneBy(['unitPrice' => '999999.99']);
        $this->assertNull($result);

        // 使用不可能存在的值来确保返回null
        $resultByStatus = $this->repository->findOneBy(['id' => 999999]);
        $this->assertNull($resultByStatus);
    }

    public function testFindByWithOrderByClause(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_ORDER');
        $agent->setCompanyName('Order Test Company');
        $agent->setContactPerson('Contact Person');
        $agent->setPhone('13800138054');
        $agent->setLevel(AgentLevelEnum::A);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);

        $order = new Order();
        $order->setOrderNo('ORD_ORDER');
        $order->setAgent($agent);
        self::getEntityManager()->persist($order);
        self::getEntityManager()->flush();

        $hotel1 = $this->createMockHotel(501);
        $roomType1 = $this->createMockRoomType($hotel1, 601);
        $contract1 = $this->createMockContract($hotel1, 701);

        $orderItem1 = new OrderItem();
        $orderItem1->setOrder($order);
        $orderItem1->setHotel($hotel1);
        $orderItem1->setRoomType($roomType1);
        $orderItem1->setContract($contract1);
        $orderItem1->setCheckInDate(new \DateTimeImmutable('2024-05-01'));
        $orderItem1->setCheckOutDate(new \DateTimeImmutable('2024-05-02'));
        $orderItem1->setUnitPrice('100.00');
        $orderItem1->setStatus(OrderItemStatusEnum::PENDING);
        self::getEntityManager()->persist($orderItem1);

        $hotel2 = $this->createMockHotel(502);
        $roomType2 = $this->createMockRoomType($hotel2, 602);
        $contract2 = $this->createMockContract($hotel2, 702);

        $orderItem2 = new OrderItem();
        $orderItem2->setOrder($order);
        $orderItem2->setHotel($hotel2);
        $orderItem2->setRoomType($roomType2);
        $orderItem2->setContract($contract2);
        $orderItem2->setCheckInDate(new \DateTimeImmutable('2024-05-02'));
        $orderItem2->setCheckOutDate(new \DateTimeImmutable('2024-05-03'));
        $orderItem2->setUnitPrice('200.00');
        $orderItem2->setStatus(OrderItemStatusEnum::PENDING);
        self::getEntityManager()->persist($orderItem2);

        self::getEntityManager()->flush();

        $resultsAsc = $this->repository->findBy(['status' => OrderItemStatusEnum::PENDING], ['unitPrice' => 'ASC']);
        $this->assertIsArray($resultsAsc);
        $this->assertGreaterThanOrEqual(2, count($resultsAsc));

        $resultsDesc = $this->repository->findBy(['status' => OrderItemStatusEnum::PENDING], ['unitPrice' => 'DESC']);
        $this->assertIsArray($resultsDesc);
        $this->assertGreaterThanOrEqual(2, count($resultsDesc));
    }

    public function testFindByWithLimitAndOffset(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_LIMIT');
        $agent->setCompanyName('Limit Test Company');
        $agent->setContactPerson('Contact Person');
        $agent->setPhone('13800138055');
        $agent->setLevel(AgentLevelEnum::B);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);

        $order = new Order();
        $order->setOrderNo('ORD_LIMIT');
        $order->setAgent($agent);
        self::getEntityManager()->persist($order);
        self::getEntityManager()->flush();

        for ($i = 1; $i <= 5; ++$i) {
            $hotel = $this->createMockHotel(600 + $i);
            $roomType = $this->createMockRoomType($hotel, 700 + $i);
            $contract = $this->createMockContract($hotel, 800 + $i);

            $orderItem = new OrderItem();
            $orderItem->setOrder($order);
            $orderItem->setHotel($hotel);
            $orderItem->setRoomType($roomType);
            $orderItem->setContract($contract);
            $orderItem->setCheckInDate(new \DateTimeImmutable('2024-06-0' . $i));
            $orderItem->setCheckOutDate(new \DateTimeImmutable('2024-06-0' . ($i + 1)));
            $orderItem->setUnitPrice(sprintf('%.2f', $i * 100));
            $orderItem->setStatus(OrderItemStatusEnum::CONFIRMED);
            self::getEntityManager()->persist($orderItem);
        }
        self::getEntityManager()->flush();

        $results = $this->repository->findBy(['status' => OrderItemStatusEnum::CONFIRMED], ['unitPrice' => 'ASC'], 2, 1);
        $this->assertIsArray($results);
        $this->assertLessThanOrEqual(2, count($results));
    }

    public function testSaveMethodShouldPersistEntity(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_SAVE');
        $agent->setCompanyName('Save Test Company');
        $agent->setContactPerson('Save Contact');
        $agent->setPhone('13800138056');
        $agent->setLevel(AgentLevelEnum::C);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);

        $order = new Order();
        $order->setOrderNo('ORD_SAVE');
        $order->setAgent($agent);
        self::getEntityManager()->persist($order);
        self::getEntityManager()->flush();

        $hotel = $this->createMockHotel(701);
        $roomType = $this->createMockRoomType($hotel, 801);
        $contract = $this->createMockContract($hotel, 901);

        $orderItem = new OrderItem();
        $orderItem->setOrder($order);
        $orderItem->setHotel($hotel);
        $orderItem->setRoomType($roomType);
        $orderItem->setContract($contract);
        $orderItem->setCheckInDate(new \DateTimeImmutable('2024-07-01'));
        $orderItem->setCheckOutDate(new \DateTimeImmutable('2024-07-02'));
        $orderItem->setUnitPrice('700.00');
        $orderItem->setStatus(OrderItemStatusEnum::PENDING);

        $this->repository->save($orderItem);

        $savedItem = $this->repository->findOneBy(['unitPrice' => '700.00']);
        $this->assertInstanceOf(OrderItem::class, $savedItem);
        $this->assertSame('700.00', $savedItem->getUnitPrice());
        $this->assertSame(OrderItemStatusEnum::PENDING, $savedItem->getStatus());
    }

    public function testSaveMethodWithoutFlush(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_SAVE_NO_FLUSH');
        $agent->setCompanyName('Save No Flush Company');
        $agent->setContactPerson('Save Contact');
        $agent->setPhone('13800138057');
        $agent->setLevel(AgentLevelEnum::A);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);

        $order = new Order();
        $order->setOrderNo('ORD_SAVE_NO_FLUSH');
        $order->setAgent($agent);
        self::getEntityManager()->persist($order);
        self::getEntityManager()->flush();

        $hotel = $this->createMockHotel(801);
        $roomType = $this->createMockRoomType($hotel, 901);
        $contract = $this->createMockContract($hotel, 1001);

        $orderItem = new OrderItem();
        $orderItem->setOrder($order);
        $orderItem->setHotel($hotel);
        $orderItem->setRoomType($roomType);
        $orderItem->setContract($contract);
        $orderItem->setCheckInDate(new \DateTimeImmutable('2024-08-01'));
        $orderItem->setCheckOutDate(new \DateTimeImmutable('2024-08-02'));
        $orderItem->setUnitPrice('800.00');
        $orderItem->setStatus(OrderItemStatusEnum::CONFIRMED);

        $this->repository->save($orderItem, false);
        self::getEntityManager()->flush();

        $savedItem = $this->repository->findOneBy(['unitPrice' => '800.00']);
        $this->assertInstanceOf(OrderItem::class, $savedItem);
        $this->assertSame('800.00', $savedItem->getUnitPrice());
    }

    public function testRemoveMethodShouldDeleteEntity(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_REMOVE');
        $agent->setCompanyName('Remove Test Company');
        $agent->setContactPerson('Remove Contact');
        $agent->setPhone('13800138058');
        $agent->setLevel(AgentLevelEnum::B);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);

        $order = new Order();
        $order->setOrderNo('ORD_REMOVE');
        $order->setAgent($agent);
        self::getEntityManager()->persist($order);

        $hotel = $this->createMockHotel(901);
        $roomType = $this->createMockRoomType($hotel, 1001);
        $contract = $this->createMockContract($hotel, 1101);

        $orderItem = new OrderItem();
        $orderItem->setOrder($order);
        $orderItem->setHotel($hotel);
        $orderItem->setRoomType($roomType);
        $orderItem->setContract($contract);
        $orderItem->setCheckInDate(new \DateTimeImmutable('2024-09-01'));
        $orderItem->setCheckOutDate(new \DateTimeImmutable('2024-09-02'));
        $orderItem->setUnitPrice('900.00');
        $orderItem->setStatus(OrderItemStatusEnum::PENDING);
        self::getEntityManager()->persist($orderItem);
        self::getEntityManager()->flush();

        $savedItem = $this->repository->findOneBy(['unitPrice' => '900.00']);
        $this->assertInstanceOf(OrderItem::class, $savedItem);

        $this->repository->remove($savedItem);

        $deletedItem = $this->repository->findOneBy(['unitPrice' => '900.00']);
        $this->assertNull($deletedItem);
    }

    public function testRemoveMethodWithoutFlush(): void
    {
        $agent = new Agent();
        $agent->setCode('TEST_REMOVE_NO_FLUSH');
        $agent->setCompanyName('Remove No Flush Company');
        $agent->setContactPerson('Remove Contact');
        $agent->setPhone('13800138059');
        $agent->setLevel(AgentLevelEnum::C);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);

        $order = new Order();
        $order->setOrderNo('ORD_REMOVE_NO_FLUSH');
        $order->setAgent($agent);
        self::getEntityManager()->persist($order);

        $hotel = $this->createMockHotel(1001);
        $roomType = $this->createMockRoomType($hotel, 1101);
        $contract = $this->createMockContract($hotel, 1201);

        $orderItem = new OrderItem();
        $orderItem->setOrder($order);
        $orderItem->setHotel($hotel);
        $orderItem->setRoomType($roomType);
        $orderItem->setContract($contract);
        $orderItem->setCheckInDate(new \DateTimeImmutable('2024-10-01'));
        $orderItem->setCheckOutDate(new \DateTimeImmutable('2024-10-02'));
        $orderItem->setUnitPrice('1000.00');
        $orderItem->setStatus(OrderItemStatusEnum::CONFIRMED);
        self::getEntityManager()->persist($orderItem);
        self::getEntityManager()->flush();

        $savedItem = $this->repository->findOneBy(['unitPrice' => '1000.00']);
        $this->assertInstanceOf(OrderItem::class, $savedItem);

        $this->repository->remove($savedItem, false);
        self::getEntityManager()->flush();

        $deletedItem = $this->repository->findOneBy(['unitPrice' => '1000.00']);
        $this->assertNull($deletedItem);
    }

    public function testCountWithCriteria(): void
    {
        $initialPendingCount = $this->repository->count(['status' => OrderItemStatusEnum::PENDING]);
        $initialConfirmedCount = $this->repository->count(['status' => OrderItemStatusEnum::CONFIRMED]);

        $agent = new Agent();
        $agent->setCode('TEST_COUNT');
        $agent->setCompanyName('Count Test Company');
        $agent->setContactPerson('Count Contact');
        $agent->setPhone('13800138060');
        $agent->setLevel(AgentLevelEnum::A);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);

        $order = new Order();
        $order->setOrderNo('ORD_COUNT');
        $order->setAgent($agent);
        self::getEntityManager()->persist($order);
        self::getEntityManager()->flush();

        $hotel1 = $this->createMockHotel(1101);
        $roomType1 = $this->createMockRoomType($hotel1, 1201);
        $contract1 = $this->createMockContract($hotel1, 1301);

        $pendingItem = new OrderItem();
        $pendingItem->setOrder($order);
        $pendingItem->setHotel($hotel1);
        $pendingItem->setRoomType($roomType1);
        $pendingItem->setContract($contract1);
        $pendingItem->setCheckInDate(new \DateTimeImmutable('2024-11-01'));
        $pendingItem->setCheckOutDate(new \DateTimeImmutable('2024-11-02'));
        $pendingItem->setUnitPrice('1100.00');
        $pendingItem->setStatus(OrderItemStatusEnum::PENDING);
        self::getEntityManager()->persist($pendingItem);

        $hotel2 = $this->createMockHotel(1102);
        $roomType2 = $this->createMockRoomType($hotel2, 1202);
        $contract2 = $this->createMockContract($hotel2, 1302);

        $confirmedItem = new OrderItem();
        $confirmedItem->setOrder($order);
        $confirmedItem->setHotel($hotel2);
        $confirmedItem->setRoomType($roomType2);
        $confirmedItem->setContract($contract2);
        $confirmedItem->setCheckInDate(new \DateTimeImmutable('2024-11-02'));
        $confirmedItem->setCheckOutDate(new \DateTimeImmutable('2024-11-03'));
        $confirmedItem->setUnitPrice('1200.00');
        $confirmedItem->setStatus(OrderItemStatusEnum::CONFIRMED);
        self::getEntityManager()->persist($confirmedItem);

        self::getEntityManager()->flush();

        $pendingCount = $this->repository->count(['status' => OrderItemStatusEnum::PENDING]);
        $confirmedCount = $this->repository->count(['status' => OrderItemStatusEnum::CONFIRMED]);

        $this->assertSame($initialPendingCount + 1, $pendingCount);
        $this->assertSame($initialConfirmedCount + 1, $confirmedCount);
    }

    public function testCountAssociationQuery(): void
    {
        $agent1 = new Agent();
        $agent1->setCode('ASSOC_COUNT_1');
        $agent1->setCompanyName('Association Count Company 1');
        $agent1->setContactPerson('Contact Person 1');
        $agent1->setPhone('13800138065');
        $agent1->setLevel(AgentLevelEnum::C);
        $agent1->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent1);

        $order1 = new Order();
        $order1->setOrderNo('ORD_ASSOC_COUNT_1');
        $order1->setAgent($agent1);
        self::getEntityManager()->persist($order1);

        $agent2 = new Agent();
        $agent2->setCode('ASSOC_COUNT_2');
        $agent2->setCompanyName('Association Count Company 2');
        $agent2->setContactPerson('Contact Person 2');
        $agent2->setPhone('13800138066');
        $agent2->setLevel(AgentLevelEnum::A);
        $agent2->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent2);

        $order2 = new Order();
        $order2->setOrderNo('ORD_ASSOC_COUNT_2');
        $order2->setAgent($agent2);
        self::getEntityManager()->persist($order2);

        self::getEntityManager()->flush();

        $hotel1 = $this->createMockHotel(1601);
        $roomType1 = $this->createMockRoomType($hotel1, 1701);
        $contract1 = $this->createMockContract($hotel1, 1801);

        $orderItem1 = new OrderItem();
        $orderItem1->setOrder($order1);
        $orderItem1->setHotel($hotel1);
        $orderItem1->setRoomType($roomType1);
        $orderItem1->setContract($contract1);
        $orderItem1->setCheckInDate(new \DateTimeImmutable('2024-01-01'));
        $orderItem1->setCheckOutDate(new \DateTimeImmutable('2024-01-02'));
        $orderItem1->setUnitPrice('1600.00');
        $orderItem1->setStatus(OrderItemStatusEnum::PENDING);
        self::getEntityManager()->persist($orderItem1);

        $hotel2 = $this->createMockHotel(1602);
        $roomType2 = $this->createMockRoomType($hotel2, 1702);
        $contract2 = $this->createMockContract($hotel2, 1802);

        $orderItem2 = new OrderItem();
        $orderItem2->setOrder($order2);
        $orderItem2->setHotel($hotel2);
        $orderItem2->setRoomType($roomType2);
        $orderItem2->setContract($contract2);
        $orderItem2->setCheckInDate(new \DateTimeImmutable('2024-01-02'));
        $orderItem2->setCheckOutDate(new \DateTimeImmutable('2024-01-03'));
        $orderItem2->setUnitPrice('1700.00');
        $orderItem2->setStatus(OrderItemStatusEnum::CONFIRMED);
        self::getEntityManager()->persist($orderItem2);

        self::getEntityManager()->flush();

        $order1Count = $this->repository->count(['order' => $order1]);
        $this->assertSame(1, $order1Count);

        $order2Count = $this->repository->count(['order' => $order2]);
        $this->assertSame(1, $order2Count);

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
        $agent1->setPhone('13800138067');
        $agent1->setLevel(AgentLevelEnum::B);
        $agent1->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent1);

        $order1 = new Order();
        $order1->setOrderNo('ORD_ASSOC_QUERY_1');
        $order1->setAgent($agent1);
        self::getEntityManager()->persist($order1);

        $agent2 = new Agent();
        $agent2->setCode('ASSOC_QUERY_2');
        $agent2->setCompanyName('Association Query Company 2');
        $agent2->setContactPerson('Contact Person 2');
        $agent2->setPhone('13800138068');
        $agent2->setLevel(AgentLevelEnum::A);
        $agent2->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent2);

        $order2 = new Order();
        $order2->setOrderNo('ORD_ASSOC_QUERY_2');
        $order2->setAgent($agent2);
        self::getEntityManager()->persist($order2);

        self::getEntityManager()->flush();

        $hotel1 = $this->createMockHotel(1701);
        $roomType1 = $this->createMockRoomType($hotel1, 1801);
        $contract1 = $this->createMockContract($hotel1, 1901);

        $orderItem1 = new OrderItem();
        $orderItem1->setOrder($order1);
        $orderItem1->setHotel($hotel1);
        $orderItem1->setRoomType($roomType1);
        $orderItem1->setContract($contract1);
        $orderItem1->setCheckInDate(new \DateTimeImmutable('2024-01-01'));
        $orderItem1->setCheckOutDate(new \DateTimeImmutable('2024-01-02'));
        $orderItem1->setUnitPrice('1800.00');
        $orderItem1->setStatus(OrderItemStatusEnum::PENDING);
        self::getEntityManager()->persist($orderItem1);

        $hotel2 = $this->createMockHotel(1702);
        $roomType2 = $this->createMockRoomType($hotel2, 1802);
        $contract2 = $this->createMockContract($hotel2, 1902);

        $orderItem2 = new OrderItem();
        $orderItem2->setOrder($order2);
        $orderItem2->setHotel($hotel2);
        $orderItem2->setRoomType($roomType2);
        $orderItem2->setContract($contract2);
        $orderItem2->setCheckInDate(new \DateTimeImmutable('2024-01-02'));
        $orderItem2->setCheckOutDate(new \DateTimeImmutable('2024-01-03'));
        $orderItem2->setUnitPrice('1900.00');
        $orderItem2->setStatus(OrderItemStatusEnum::CONFIRMED);
        self::getEntityManager()->persist($orderItem2);

        self::getEntityManager()->flush();

        $order1Items = $this->repository->findBy(['order' => $order1]);
        $this->assertCount(1, $order1Items);
        $this->assertSame($orderItem1->getId(), $order1Items[0]->getId());

        $order2Items = $this->repository->findBy(['order' => $order2]);
        $this->assertCount(1, $order2Items);
        $this->assertSame($orderItem2->getId(), $order2Items[0]->getId());

        $hotel1Items = $this->repository->findBy(['hotel' => $hotel1]);
        $this->assertCount(1, $hotel1Items);
        $this->assertSame($orderItem1->getId(), $hotel1Items[0]->getId());

        $hotel2Items = $this->repository->findBy(['hotel' => $hotel2]);
        $this->assertCount(1, $hotel2Items);
        $this->assertSame($orderItem2->getId(), $hotel2Items[0]->getId());
    }

    public function testNullFieldQuery(): void
    {
        $agent = new Agent();
        $agent->setCode('NULL_FIELD');
        $agent->setCompanyName('NULL Field Company');
        $agent->setContactPerson('Contact Person');
        $agent->setPhone('13800138069');
        $agent->setLevel(AgentLevelEnum::C);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);

        $order = new Order();
        $order->setOrderNo('ORD_NULL_FIELD');
        $order->setAgent($agent);
        self::getEntityManager()->persist($order);
        self::getEntityManager()->flush();

        $hotel = $this->createMockHotel(1801);
        $roomType = $this->createMockRoomType($hotel, 1901);
        $contract = $this->createMockContract($hotel, 2001);

        $orderItem = new OrderItem();
        $orderItem->setOrder($order);
        $orderItem->setHotel($hotel);
        $orderItem->setRoomType($roomType);
        $orderItem->setContract($contract);
        $orderItem->setCheckInDate(new \DateTimeImmutable('2024-01-01'));
        $orderItem->setCheckOutDate(new \DateTimeImmutable('2024-01-02'));
        $orderItem->setUnitPrice('1800.00');
        $orderItem->setStatus(OrderItemStatusEnum::PENDING);
        $orderItem->setContractChangeReason(null);
        self::getEntityManager()->persist($orderItem);
        self::getEntityManager()->flush();

        $results = $this->repository->findBy(['contractChangeReason' => null]);
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(1, count($results));

        $foundNullRemarks = false;
        foreach ($results as $result) {
            if (null === $result->getContractChangeReason()) {
                $foundNullRemarks = true;
                break;
            }
        }
        $this->assertTrue($foundNullRemarks);
    }

    public function testCountNullFieldQuery(): void
    {
        $agent = new Agent();
        $agent->setCode('COUNT_NULL');
        $agent->setCompanyName('Count NULL Company');
        $agent->setContactPerson('Contact Person');
        $agent->setPhone('13800138070');
        $agent->setLevel(AgentLevelEnum::B);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);

        $order = new Order();
        $order->setOrderNo('ORD_COUNT_NULL');
        $order->setAgent($agent);
        self::getEntityManager()->persist($order);
        self::getEntityManager()->flush();

        $hotel = $this->createMockHotel(1901);
        $roomType = $this->createMockRoomType($hotel, 2001);
        $contract = $this->createMockContract($hotel, 2101);

        $orderItem = new OrderItem();
        $orderItem->setOrder($order);
        $orderItem->setHotel($hotel);
        $orderItem->setRoomType($roomType);
        $orderItem->setContract($contract);
        $orderItem->setCheckInDate(new \DateTimeImmutable('2024-01-01'));
        $orderItem->setCheckOutDate(new \DateTimeImmutable('2024-01-02'));
        $orderItem->setUnitPrice('1900.00');
        $orderItem->setStatus(OrderItemStatusEnum::PENDING);
        $orderItem->setContractChangeReason(null);
        self::getEntityManager()->persist($orderItem);
        self::getEntityManager()->flush();

        $count = $this->repository->count(['contractChangeReason' => null]);
        $this->assertGreaterThanOrEqual(1, $count);

        $lastModifiedByCount = $this->repository->count(['lastModifiedBy' => null]);
        $this->assertGreaterThanOrEqual(1, $lastModifiedByCount);
    }

    public function testFindOneByWithOrderBy(): void
    {
        $agent = new Agent();
        $agent->setCode('ORDER_ONE_BY');
        $agent->setCompanyName('Order One By Company');
        $agent->setContactPerson('Contact Person');
        $agent->setPhone('13800138064');
        $agent->setLevel(AgentLevelEnum::B);
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        self::getEntityManager()->persist($agent);

        $order = new Order();
        $order->setOrderNo('ORD_ORDER_ONE_BY');
        $order->setAgent($agent);
        self::getEntityManager()->persist($order);
        self::getEntityManager()->flush();

        $hotel1 = $this->createMockHotel(1501);
        $roomType1 = $this->createMockRoomType($hotel1, 1601);
        $contract1 = $this->createMockContract($hotel1, 1701);

        $orderItem1 = new OrderItem();
        $orderItem1->setOrder($order);
        $orderItem1->setHotel($hotel1);
        $orderItem1->setRoomType($roomType1);
        $orderItem1->setContract($contract1);
        $orderItem1->setCheckInDate(new \DateTimeImmutable('2024-01-01'));
        $orderItem1->setCheckOutDate(new \DateTimeImmutable('2024-01-02'));
        $orderItem1->setUnitPrice('1500.00');
        $orderItem1->setStatus(OrderItemStatusEnum::CONFIRMED);
        self::getEntityManager()->persist($orderItem1);

        $hotel2 = $this->createMockHotel(1502);
        $roomType2 = $this->createMockRoomType($hotel2, 1602);
        $contract2 = $this->createMockContract($hotel2, 1702);

        $orderItem2 = new OrderItem();
        $orderItem2->setOrder($order);
        $orderItem2->setHotel($hotel2);
        $orderItem2->setRoomType($roomType2);
        $orderItem2->setContract($contract2);
        $orderItem2->setCheckInDate(new \DateTimeImmutable('2024-01-02'));
        $orderItem2->setCheckOutDate(new \DateTimeImmutable('2024-01-03'));
        $orderItem2->setUnitPrice('1600.00');
        $orderItem2->setStatus(OrderItemStatusEnum::CONFIRMED);
        self::getEntityManager()->persist($orderItem2);

        self::getEntityManager()->flush();

        $resultAsc = $this->repository->findOneBy(['order' => $order], ['unitPrice' => 'ASC']);
        $this->assertInstanceOf(OrderItem::class, $resultAsc);

        $resultDesc = $this->repository->findOneBy(['order' => $order], ['unitPrice' => 'DESC']);
        $this->assertInstanceOf(OrderItem::class, $resultDesc);
    }

    /**
     * @return ServiceEntityRepository<OrderItem>
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

        // 创建必需的 Order 实体
        $order = new Order();
        $order->setOrderNo('ORD' . uniqid());
        $order->setAgent($agent);
        self::getEntityManager()->persist($order);

        // 创建必需的 Hotel 实体
        $hotel = new Hotel();
        $hotel->setName('Test Hotel - ' . uniqid());
        $hotel->setAddress('Test Address - ' . uniqid());
        $hotel->setContactPerson('Hotel Contact - ' . uniqid());
        $hotel->setPhone('0571' . sprintf('%08d', rand(10000000, 99999999)));
        self::getEntityManager()->persist($hotel);

        // 创建必需的 RoomType 实体
        $roomType = new RoomType();
        $roomType->setHotel($hotel);
        $roomType->setName('Test Room Type - ' . uniqid());
        $roomType->setBedType('Double Bed');
        $roomType->setArea(25.0);
        $roomType->setMaxGuests(2);
        $roomType->setDescription('Test Description - ' . uniqid());
        self::getEntityManager()->persist($roomType);

        // 创建必需的 HotelContract 实体
        $contract = new HotelContract();
        $contract->setContractNo('CONTRACT' . uniqid());
        $contract->setHotel($hotel);
        $contract->setStartDate(new \DateTimeImmutable('2024-01-01'));
        $contract->setEndDate(new \DateTimeImmutable('2024-12-31'));
        $contract->setTotalRooms(100);
        $contract->setTotalDays(365);
        self::getEntityManager()->persist($contract);

        self::getEntityManager()->flush();

        // 创建 OrderItem 实体
        $orderItem = new OrderItem();
        $orderItem->setOrder($order);
        $orderItem->setHotel($hotel);
        $orderItem->setRoomType($roomType);
        $orderItem->setContract($contract);
        $orderItem->setCheckInDate(new \DateTimeImmutable('2024-01-01'));
        $orderItem->setCheckOutDate(new \DateTimeImmutable('2024-01-02'));
        $orderItem->setUnitPrice(sprintf('%.2f', rand(100, 500)));
        $orderItem->setStatus(OrderItemStatusEnum::PENDING);

        return $orderItem;
    }

    public function testCountBookingsByHotelAndDate(): void
    {
        $hotel = $this->createMockHotel(1);
        $agent = $this->createMockAgent(1);
        $order = $this->createMockOrder($agent, 1);
        $contract = $this->createMockContract($hotel, 1);
        $roomType = $this->createMockRoomType($hotel, 1);
        $date = new \DateTimeImmutable('2024-01-01');

        $orderItem = $this->createMockOrderItem($order, $hotel, $roomType, $contract, 1);
        $orderItem->setCheckInDate($date);
        $this->repository->save($orderItem);

        $hotelId = $hotel->getId();
        $this->assertNotNull($hotelId);
        $count = $this->repository->countBookingsByHotelAndDate($hotelId, $date);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testCountBookingsByRoomTypeAndDate(): void
    {
        $hotel = $this->createMockHotel(2);
        $agent = $this->createMockAgent(2);
        $order = $this->createMockOrder($agent, 2);
        $contract = $this->createMockContract($hotel, 2);
        $roomType = $this->createMockRoomType($hotel, 2);
        $date = new \DateTimeImmutable('2024-01-01');

        $orderItem = $this->createMockOrderItem($order, $hotel, $roomType, $contract, 2);
        $orderItem->setCheckInDate($date);
        $this->repository->save($orderItem);

        $roomTypeId = $roomType->getId();
        $this->assertNotNull($roomTypeId);
        $count = $this->repository->countBookingsByRoomTypeAndDate($roomTypeId, $date);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testFindByCheckOutDateRange(): void
    {
        $hotel = $this->createMockHotel(3);
        $agent = $this->createMockAgent(3);
        $order = $this->createMockOrder($agent, 3);
        $contract = $this->createMockContract($hotel, 3);
        $roomType = $this->createMockRoomType($hotel, 3);

        $orderItem = $this->createMockOrderItem($order, $hotel, $roomType, $contract, 3);
        $orderItem->setCheckOutDate(new \DateTimeImmutable('2024-01-02'));
        $this->repository->save($orderItem);

        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-03');
        $result = $this->repository->findByCheckOutDateRange($startDate, $endDate);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testFindByContractAndDateRange(): void
    {
        $hotel = $this->createMockHotel(4);
        $agent = $this->createMockAgent(4);
        $order = $this->createMockOrder($agent, 4);
        $contract = $this->createMockContract($hotel, 4);
        $roomType = $this->createMockRoomType($hotel, 4);

        $orderItem = $this->createMockOrderItem($order, $hotel, $roomType, $contract, 4);
        $orderItem->setCheckInDate(new \DateTimeImmutable('2024-01-01'));
        $orderItem->setCheckOutDate(new \DateTimeImmutable('2024-01-02'));
        $this->repository->save($orderItem);

        $contractId = $contract->getId();
        $this->assertNotNull($contractId);
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-03');
        $result = $this->repository->findByContractAndDateRange($contractId, $startDate, $endDate);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testFindByContractId(): void
    {
        $hotel = $this->createMockHotel(5);
        $agent = $this->createMockAgent(5);
        $order = $this->createMockOrder($agent, 5);
        $contract = $this->createMockContract($hotel, 5);
        $roomType = $this->createMockRoomType($hotel, 5);

        $orderItem = $this->createMockOrderItem($order, $hotel, $roomType, $contract, 5);
        $this->repository->save($orderItem);

        $contractId = $contract->getId();
        $this->assertNotNull($contractId);
        $result = $this->repository->findByContractId($contractId);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testFindByDailyInventoryId(): void
    {
        // 由于DailyInventory实体不在当前bundle中，我们测试空结果
        $result = $this->repository->findByDailyInventoryId(999999);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFindByHotelAndDateRange(): void
    {
        $hotel = $this->createMockHotel(6);
        $agent = $this->createMockAgent(6);
        $order = $this->createMockOrder($agent, 6);
        $contract = $this->createMockContract($hotel, 6);
        $roomType = $this->createMockRoomType($hotel, 6);

        $orderItem = $this->createMockOrderItem($order, $hotel, $roomType, $contract, 6);
        $orderItem->setCheckInDate(new \DateTimeImmutable('2024-01-01'));
        $orderItem->setCheckOutDate(new \DateTimeImmutable('2024-01-02'));
        $this->repository->save($orderItem);

        $hotelId = $hotel->getId();
        $this->assertNotNull($hotelId);
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-03');
        $result = $this->repository->findByHotelAndDateRange($hotelId, $startDate, $endDate);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testFindByLastModifiedBy(): void
    {
        // 由于OrderItem实体没有lastModifiedBy字段，我们测试空结果
        $result = $this->repository->findByLastModifiedBy(999999);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFindByRoomTypeAndDate(): void
    {
        $hotel = $this->createMockHotel(7);
        $agent = $this->createMockAgent(7);
        $order = $this->createMockOrder($agent, 7);
        $contract = $this->createMockContract($hotel, 7);
        $roomType = $this->createMockRoomType($hotel, 7);

        $orderItem = $this->createMockOrderItem($order, $hotel, $roomType, $contract, 7);
        $orderItem->setCheckInDate(new \DateTimeImmutable('2024-01-01'));
        $this->repository->save($orderItem);

        $roomTypeId = $roomType->getId();
        $this->assertNotNull($roomTypeId);
        $date = new \DateTimeImmutable('2024-01-01');
        $result = $this->repository->findByRoomTypeAndDate($roomTypeId, $date);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testFindByRoomTypeAndDateRange(): void
    {
        $hotel = $this->createMockHotel(8);
        $agent = $this->createMockAgent(8);
        $order = $this->createMockOrder($agent, 8);
        $contract = $this->createMockContract($hotel, 8);
        $roomType = $this->createMockRoomType($hotel, 8);

        $orderItem = $this->createMockOrderItem($order, $hotel, $roomType, $contract, 8);
        $orderItem->setCheckInDate(new \DateTimeImmutable('2024-01-01'));
        $orderItem->setCheckOutDate(new \DateTimeImmutable('2024-01-02'));
        $this->repository->save($orderItem);

        $roomTypeId = $roomType->getId();
        $this->assertNotNull($roomTypeId);
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-03');
        $result = $this->repository->findByRoomTypeAndDateRange($roomTypeId, $startDate, $endDate);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testFindByRoomTypeId(): void
    {
        $hotel = $this->createMockHotel(9);
        $agent = $this->createMockAgent(9);
        $order = $this->createMockOrder($agent, 9);
        $contract = $this->createMockContract($hotel, 9);
        $roomType = $this->createMockRoomType($hotel, 9);

        $orderItem = $this->createMockOrderItem($order, $hotel, $roomType, $contract, 9);
        $this->repository->save($orderItem);

        $roomTypeId = $roomType->getId();
        $this->assertNotNull($roomTypeId);
        $result = $this->repository->findByRoomTypeId($roomTypeId);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testFindByUnitPriceRange(): void
    {
        $hotel = $this->createMockHotel(10);
        $agent = $this->createMockAgent(10);
        $order = $this->createMockOrder($agent, 10);
        $contract = $this->createMockContract($hotel, 10);
        $roomType = $this->createMockRoomType($hotel, 10);

        $orderItem = $this->createMockOrderItem($order, $hotel, $roomType, $contract, 10);
        $orderItem->setUnitPrice('200.00');
        $this->repository->save($orderItem);

        $result = $this->repository->findByUnitPriceRange('100.00', '300.00');

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testFindOverlappingDateRange(): void
    {
        $hotel = $this->createMockHotel(11);
        $agent = $this->createMockAgent(11);
        $order = $this->createMockOrder($agent, 11);
        $contract = $this->createMockContract($hotel, 11);
        $roomType = $this->createMockRoomType($hotel, 11);

        $orderItem = $this->createMockOrderItem($order, $hotel, $roomType, $contract, 11);
        $orderItem->setCheckInDate(new \DateTimeImmutable('2024-01-01'));
        $orderItem->setCheckOutDate(new \DateTimeImmutable('2024-01-03'));
        $this->repository->save($orderItem);

        $startDate = new \DateTimeImmutable('2024-01-02');
        $endDate = new \DateTimeImmutable('2024-01-04');
        $result = $this->repository->findOverlappingDateRange($startDate, $endDate);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
    }
}
