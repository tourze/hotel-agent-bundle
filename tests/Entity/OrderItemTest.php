<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Entity\OrderItem;
use Tourze\HotelAgentBundle\Enum\OrderItemStatusEnum;
use Tourze\HotelContractBundle\Entity\DailyInventory;
use Tourze\HotelContractBundle\Entity\HotelContract;
use Tourze\HotelProfileBundle\Entity\Hotel;
use Tourze\HotelProfileBundle\Entity\RoomType;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(OrderItem::class)]
final class OrderItemTest extends AbstractEntityTestCase
{
    public function testConstructInitializesDefaults(): void
    {
        $orderItem = new OrderItem();
        $this->assertSame([], $orderItem->getContractChangeHistory());
        $this->assertSame(OrderItemStatusEnum::PENDING, $orderItem->getStatus());
    }

    public function testToStringReturnsHotelRoomtypeAndDateRange(): void
    {
        /*
         * 使用具体类 Hotel 创建 mock 对象的原因：
         * 1. Hotel 是来自 hotel-profile-bundle 的实体类，没有对应的接口定义
         * 2. 在测试中只需要模拟 getName() 方法的返回值，使用具体类是合理的
         * 3. 由于这是外部依赖的实体类，创建接口会增加不必要的复杂性
         */
        $hotel = $this->createMock(Hotel::class);
        $hotel->method('getName')->willReturn('测试酒店');

        /*
         * 使用具体类 RoomType 创建 mock 对象的原因：
         * 1. RoomType 是来自 hotel-profile-bundle 的实体类，没有对应的接口定义
         * 2. 在测试中只需要模拟 getName() 方法的返回值，使用具体类是合理的
         * 3. 由于这是外部依赖的实体类，创建接口会增加不必要的复杂性
         */
        $roomType = $this->createMock(RoomType::class);
        $roomType->method('getName')->willReturn('标准间');

        $checkIn = new \DateTimeImmutable('2024-01-01');
        $checkOut = new \DateTimeImmutable('2024-01-02');

        $orderItem = new OrderItem();
        $orderItem->setHotel($hotel);
        $orderItem->setRoomType($roomType);
        $orderItem->setCheckInDate($checkIn);
        $orderItem->setCheckOutDate($checkOut);

        $this->assertSame('测试酒店, 标准间, 2024-01-01 - 2024-01-02', $orderItem->__toString());
    }

    public function testToStringWithUnknownHotelAndRoomtype(): void
    {
        $checkIn = new \DateTimeImmutable('2024-01-01');
        $checkOut = new \DateTimeImmutable('2024-01-02');

        $orderItem = new OrderItem();
        $orderItem->setCheckInDate($checkIn);
        $orderItem->setCheckOutDate($checkOut);

        $this->assertSame('Unknown, Unknown, 2024-01-01 - 2024-01-02', $orderItem->__toString());
    }

    public function testToStringWithoutDates(): void
    {
        /*
         * 使用具体类 Hotel 创建 mock 对象的原因：
         * 1. Hotel 是来自 hotel-profile-bundle 的实体类，没有对应的接口定义
         * 2. 在测试中只需要模拟 getName() 方法的返回值，使用具体类是合理的
         * 3. 由于这是外部依赖的实体类，创建接口会增加不必要的复杂性
         */
        $hotel = $this->createMock(Hotel::class);
        $hotel->method('getName')->willReturn('测试酒店');

        $orderItem = new OrderItem();
        $orderItem->setHotel($hotel);

        $this->assertSame('测试酒店, Unknown, ', $orderItem->__toString());
    }

    public function testSetOrderWithValidOrder(): void
    {
        $order = new Order();
        $orderItem = new OrderItem();
        $orderItem->setOrder($order);
        $this->assertSame($order, $orderItem->getOrder());
    }

    public function testSetOrderWithNull(): void
    {
        $orderItem = new OrderItem();
        $orderItem->setOrder(null);
        $this->assertNull($orderItem->getOrder());
    }

    public function testSetHotelWithValidHotel(): void
    {
        /*
         * 使用具体类 Hotel 创建 mock 对象的原因：
         * 1. Hotel 是来自 hotel-profile-bundle 的实体类，没有对应的接口定义
         * 2. 在测试中只需要模拟基本的 setter/getter 行为，使用具体类是合理的
         * 3. 由于这是外部依赖的实体类，创建接口会增加不必要的复杂性
         */
        $hotel = $this->createMock(Hotel::class);
        $orderItem = new OrderItem();
        $orderItem->setHotel($hotel);
        $this->assertSame($hotel, $orderItem->getHotel());
    }

    public function testSetRoomTypeWithValidRoomtype(): void
    {
        /*
         * 使用具体类 RoomType 创建 mock 对象的原因：
         * 1. RoomType 是来自 hotel-profile-bundle 的实体类，没有对应的接口定义
         * 2. 在测试中只需要模拟基本的 setter/getter 行为，使用具体类是合理的
         * 3. 由于这是外部依赖的实体类，创建接口会增加不必要的复杂性
         */
        $roomType = $this->createMock(RoomType::class);
        $orderItem = new OrderItem();
        $orderItem->setRoomType($roomType);
        $this->assertSame($roomType, $orderItem->getRoomType());
    }

    public function testSetCheckInDateCalculatesAmount(): void
    {
        $checkIn = new \DateTimeImmutable('2024-01-01');
        $checkOut = new \DateTimeImmutable('2024-01-03');
        $orderItem = new OrderItem();
        $orderItem->setCheckOutDate($checkOut);
        $orderItem->setUnitPrice('100.00');

        $orderItem->setCheckInDate($checkIn);
        $this->assertSame($checkIn, $orderItem->getCheckInDate());
        $this->assertSame('200.00', $orderItem->getAmount()); // 2 nights * 100
    }

    public function testSetCheckOutDateCalculatesAmount(): void
    {
        $checkIn = new \DateTimeImmutable('2024-01-01');
        $checkOut = new \DateTimeImmutable('2024-01-04');
        $orderItem = new OrderItem();
        $orderItem->setCheckInDate($checkIn);
        $orderItem->setUnitPrice('50.00');

        $orderItem->setCheckOutDate($checkOut);
        $this->assertSame($checkOut, $orderItem->getCheckOutDate());
        $this->assertSame('150.00', $orderItem->getAmount()); // 3 nights * 50
    }

    public function testSetUnitPriceCalculatesAmount(): void
    {
        $checkIn = new \DateTimeImmutable('2024-01-01');
        $checkOut = new \DateTimeImmutable('2024-01-03');
        $orderItem = new OrderItem();
        $orderItem->setCheckInDate($checkIn);
        $orderItem->setCheckOutDate($checkOut);

        $orderItem->setUnitPrice('75.50');
        $this->assertSame('75.50', $orderItem->getUnitPrice());
        $this->assertSame('151.00', $orderItem->getAmount()); // 2 nights * 75.50
    }

    public function testSetCostPriceCalculatesProfit(): void
    {
        $checkIn = new \DateTimeImmutable('2024-01-01');
        $checkOut = new \DateTimeImmutable('2024-01-03');
        $orderItem = new OrderItem();
        $orderItem->setCheckInDate($checkIn);
        $orderItem->setCheckOutDate($checkOut);
        $orderItem->setUnitPrice('100.00');

        $orderItem->setCostPrice('80.00');
        $this->assertSame('80.00', $orderItem->getCostPrice());
        $this->assertSame('40.00', $orderItem->getProfit()); // (100-80) * 2 nights
    }

    public function testSetAmountCalculatesProfit(): void
    {
        $checkIn = new \DateTimeImmutable('2024-01-01');
        $checkOut = new \DateTimeImmutable('2024-01-03');
        $orderItem = new OrderItem();
        $orderItem->setCheckInDate($checkIn);
        $orderItem->setCheckOutDate($checkOut);
        $orderItem->setCostPrice('60.00');

        $orderItem->setAmount('250.00');
        $this->assertSame('250.00', $orderItem->getAmount());
        $this->assertSame('130.00', $orderItem->getProfit()); // 250 - (60 * 2 nights)
    }

    public function testSetContractWithValidContract(): void
    {
        /*
         * 使用具体类 HotelContract 创建 mock 对象的原因：
         * 1. HotelContract 是来自 hotel-contract-bundle 的实体类，没有对应的接口定义
         * 2. 在测试中只需要模拟基本的 setter/getter 行为，使用具体类是合理的
         * 3. 由于这是外部依赖的实体类，创建接口会增加不必要的复杂性
         */
        $contract = $this->createMock(HotelContract::class);
        $orderItem = new OrderItem();
        $orderItem->setContract($contract);
        $this->assertSame($contract, $orderItem->getContract());
    }

    public function testSetDailyInventoryWithValidInventory(): void
    {
        /*
         * 使用具体类 DailyInventory 创建 mock 对象的原因：
         * 1. DailyInventory 是来自 hotel-contract-bundle 的实体类，没有对应的接口定义
         * 2. 在测试中只需要模拟基本的 setter/getter 行为，使用具体类是合理的
         * 3. 由于这是外部依赖的实体类，创建接口会增加不必要的复杂性
         */
        $inventory = $this->createMock(DailyInventory::class);
        $orderItem = new OrderItem();
        $orderItem->setDailyInventory($inventory);
        $this->assertSame($inventory, $orderItem->getDailyInventory());
    }

    public function testSetStatusWithValidStatus(): void
    {
        $orderItem = new OrderItem();
        $orderItem->setStatus(OrderItemStatusEnum::CONFIRMED);
        $this->assertSame(OrderItemStatusEnum::CONFIRMED, $orderItem->getStatus());
    }

    public function testSetContractChangeHistoryWithValidHistory(): void
    {
        $history = [['timestamp' => '2024-01-01', 'action' => 'change']];
        $orderItem = new OrderItem();
        $orderItem->setContractChangeHistory($history);
        $this->assertSame($history, $orderItem->getContractChangeHistory());
    }

    public function testSetContractChangeHistoryWithNull(): void
    {
        $orderItem = new OrderItem();
        $orderItem->setContractChangeHistory(null);
        $this->assertNull($orderItem->getContractChangeHistory());
    }

    public function testSetContractChangeReasonWithValidReason(): void
    {
        $orderItem = new OrderItem();
        $orderItem->setContractChangeReason('合同价格调整');
        $this->assertSame('合同价格调整', $orderItem->getContractChangeReason());
    }

    public function testSetLastModifiedByWithUserId(): void
    {
        $orderItem = new OrderItem();
        $orderItem->setLastModifiedBy(123);
        $this->assertSame(123, $orderItem->getLastModifiedBy());
    }

    public function testChangeContractRecordsChangeHistory(): void
    {
        /*
         * 使用具体类 HotelContract 创建 mock 对象的原因：
         * 1. HotelContract 是来自 hotel-contract-bundle 的实体类，没有对应的接口定义
         * 2. 在测试中只需要模拟 getId() 方法的返回值，使用具体类是合理的
         * 3. 由于这是外部依赖的实体类，创建接口会增加不必要的复杂性
         */
        $oldContract = $this->createMock(HotelContract::class);
        $oldContract->method('getId')->willReturn(1);

        /*
         * 使用具体类 HotelContract 创建 mock 对象的原因：
         * 1. HotelContract 是来自 hotel-contract-bundle 的实体类，没有对应的接口定义
         * 2. 在测试中只需要模拟 getId() 方法的返回值，使用具体类是合理的
         * 3. 由于这是外部依赖的实体类，创建接口会增加不必要的复杂性
         */
        $newContract = $this->createMock(HotelContract::class);
        $newContract->method('getId')->willReturn(2);

        $orderItem = new OrderItem();
        $orderItem->setContract($oldContract);

        $result = $orderItem->changeContract($newContract, '价格调整', 123);

        $this->assertSame($orderItem, $result);
        $this->assertSame($newContract, $orderItem->getContract());
        $this->assertSame('价格调整', $orderItem->getContractChangeReason());
        $this->assertSame(123, $orderItem->getLastModifiedBy());

        $history = $orderItem->getContractChangeHistory();
        $this->assertIsArray($history);
        $this->assertCount(1, $history);
        $this->assertArrayHasKey(0, $history);
        $this->assertSame(1, $history[0]['oldContractId']);
        $this->assertSame(2, $history[0]['newContractId']);
        $this->assertSame('价格调整', $history[0]['reason']);
        $this->assertSame(123, $history[0]['operatorId']);
    }

    public function testChangeContractWithNullOldContract(): void
    {
        /*
         * 使用具体类 HotelContract 创建 mock 对象的原因：
         * 1. HotelContract 是来自 hotel-contract-bundle 的实体类，没有对应的接口定义
         * 2. 在测试中只需要模拟 getId() 方法的返回值，使用具体类是合理的
         * 3. 由于这是外部依赖的实体类，创建接口会增加不必要的复杂性
         */
        $newContract = $this->createMock(HotelContract::class);
        $newContract->method('getId')->willReturn(2);

        $orderItem = new OrderItem();
        $orderItem->changeContract($newContract, '初次分配', 123);

        $history = $orderItem->getContractChangeHistory();
        $this->assertIsArray($history);
        $this->assertArrayHasKey(0, $history);
        $this->assertNull($history[0]['oldContractId']);
        $this->assertSame(2, $history[0]['newContractId']);
    }

    public function testCancelUpdatesStatus(): void
    {
        $orderItem = new OrderItem();
        $result = $orderItem->cancel();

        $this->assertSame($orderItem, $result);
        $this->assertSame(OrderItemStatusEnum::CANCELED, $orderItem->getStatus());
    }

    public function testConfirmUpdatesStatus(): void
    {
        $orderItem = new OrderItem();
        $result = $orderItem->confirm();

        $this->assertSame($orderItem, $result);
        $this->assertSame(OrderItemStatusEnum::CONFIRMED, $orderItem->getStatus());
    }

    public function testCompleteUpdatesStatus(): void
    {
        $orderItem = new OrderItem();
        $result = $orderItem->complete();

        $this->assertSame($orderItem, $result);
        $this->assertSame(OrderItemStatusEnum::COMPLETED, $orderItem->getStatus());
    }

    public function testSetCreateTimeSetsTime(): void
    {
        $time = new \DateTimeImmutable();
        $orderItem = new OrderItem();
        $orderItem->setCreateTime($time);

        $this->assertSame($time, $orderItem->getCreateTime());
    }

    public function testSetUpdateTimeSetsTime(): void
    {
        $time = new \DateTimeImmutable();
        $orderItem = new OrderItem();
        $orderItem->setUpdateTime($time);

        $this->assertSame($time, $orderItem->getUpdateTime());
    }

    public function testAmountCalculationWithZeroNights(): void
    {
        $checkIn = new \DateTimeImmutable('2024-01-01');
        $checkOut = new \DateTimeImmutable('2024-01-01'); // 同一天
        $orderItem = new OrderItem();
        $orderItem->setCheckInDate($checkIn);
        $orderItem->setCheckOutDate($checkOut);
        $orderItem->setUnitPrice('100.00');

        $this->assertSame('0.00', $orderItem->getAmount());
    }

    public function testProfitCalculationWithZeroCost(): void
    {
        $checkIn = new \DateTimeImmutable('2024-01-01');
        $checkOut = new \DateTimeImmutable('2024-01-03');
        $orderItem = new OrderItem();
        $orderItem->setCheckInDate($checkIn);
        $orderItem->setCheckOutDate($checkOut);
        $orderItem->setUnitPrice('100.00');
        $orderItem->setCostPrice('0.00');

        $this->assertSame('200.00', $orderItem->getProfit());
    }

    public static function propertiesProvider(): iterable
    {
        $order = new Order();
        $order->setOrderNo('ORD20250101001');
        $order->setCreatedBy('123');

        $hotel = new Hotel();
        $hotel->setName('测试酒店');

        $roomType = new RoomType();
        $roomType->setName('标准间');

        $checkIn = new \DateTimeImmutable('2025-01-01');
        $checkOut = new \DateTimeImmutable('2025-01-03');

        yield 'pending_item' => ['order', $order];
        yield 'confirmed_item' => ['hotel', $hotel];
        yield 'completed_item' => ['roomType', $roomType];
        yield 'canceled_item' => ['status', OrderItemStatusEnum::CANCELED];
    }

    protected function createEntity(): object
    {
        return new OrderItem();
    }
}
