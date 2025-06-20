<?php

namespace Tourze\HotelAgentBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Entity\OrderItem;
use Tourze\HotelAgentBundle\Enum\OrderItemStatusEnum;
use Tourze\HotelContractBundle\Entity\DailyInventory;
use Tourze\HotelContractBundle\Entity\HotelContract;
use Tourze\HotelProfileBundle\Entity\Hotel;
use Tourze\HotelProfileBundle\Entity\RoomType;

class OrderItemTest extends TestCase
{
    private OrderItem $orderItem;

    protected function setUp(): void
    {
        $this->orderItem = new OrderItem();
    }

    public function test_construct_initializes_defaults(): void
    {
        $this->assertSame([], $this->orderItem->getContractChangeHistory());
        $this->assertSame(OrderItemStatusEnum::PENDING, $this->orderItem->getStatus());
    }

    public function test_toString_returns_hotel_roomtype_and_date_range(): void
    {
        $hotel = $this->createMock(Hotel::class);
        $hotel->method('getName')->willReturn('测试酒店');

        $roomType = $this->createMock(RoomType::class);
        $roomType->method('getName')->willReturn('标准间');

        $checkIn = new \DateTime('2024-01-01');
        $checkOut = new \DateTime('2024-01-02');

        $this->orderItem->setHotel($hotel)
            ->setRoomType($roomType)
            ->setCheckInDate($checkIn)
            ->setCheckOutDate($checkOut);

        $this->assertSame('测试酒店, 标准间, 2024-01-01 - 2024-01-02', $this->orderItem->__toString());
    }

    public function test_toString_with_unknown_hotel_and_roomtype(): void
    {
        $checkIn = new \DateTime('2024-01-01');
        $checkOut = new \DateTime('2024-01-02');

        $this->orderItem->setCheckInDate($checkIn)
            ->setCheckOutDate($checkOut);

        $this->assertSame('Unknown, Unknown, 2024-01-01 - 2024-01-02', $this->orderItem->__toString());
    }

    public function test_toString_without_dates(): void
    {
        $hotel = $this->createMock(Hotel::class);
        $hotel->method('getName')->willReturn('测试酒店');

        $this->orderItem->setHotel($hotel);

        $this->assertSame('测试酒店, Unknown, ', $this->orderItem->__toString());
    }

    public function test_setOrder_with_valid_order(): void
    {
        $order = new Order();
        $result = $this->orderItem->setOrder($order);

        $this->assertSame($this->orderItem, $result);
        $this->assertSame($order, $this->orderItem->getOrder());
    }

    public function test_setOrder_with_null(): void
    {
        $result = $this->orderItem->setOrder(null);

        $this->assertSame($this->orderItem, $result);
        $this->assertNull($this->orderItem->getOrder());
    }

    public function test_setHotel_with_valid_hotel(): void
    {
        $hotel = $this->createMock(Hotel::class);
        $result = $this->orderItem->setHotel($hotel);

        $this->assertSame($this->orderItem, $result);
        $this->assertSame($hotel, $this->orderItem->getHotel());
    }

    public function test_setRoomType_with_valid_roomtype(): void
    {
        $roomType = $this->createMock(RoomType::class);
        $result = $this->orderItem->setRoomType($roomType);

        $this->assertSame($this->orderItem, $result);
        $this->assertSame($roomType, $this->orderItem->getRoomType());
    }

    public function test_setCheckInDate_calculates_amount(): void
    {
        $checkIn = new \DateTime('2024-01-01');
        $checkOut = new \DateTime('2024-01-03');
        $this->orderItem->setCheckOutDate($checkOut)
            ->setUnitPrice('100.00');

        $result = $this->orderItem->setCheckInDate($checkIn);

        $this->assertSame($this->orderItem, $result);
        $this->assertSame($checkIn, $this->orderItem->getCheckInDate());
        $this->assertSame('200.00', $this->orderItem->getAmount()); // 2 nights * 100
    }

    public function test_setCheckOutDate_calculates_amount(): void
    {
        $checkIn = new \DateTime('2024-01-01');
        $checkOut = new \DateTime('2024-01-04');
        $this->orderItem->setCheckInDate($checkIn)
            ->setUnitPrice('50.00');

        $result = $this->orderItem->setCheckOutDate($checkOut);

        $this->assertSame($this->orderItem, $result);
        $this->assertSame($checkOut, $this->orderItem->getCheckOutDate());
        $this->assertSame('150.00', $this->orderItem->getAmount()); // 3 nights * 50
    }

    public function test_setUnitPrice_calculates_amount(): void
    {
        $checkIn = new \DateTime('2024-01-01');
        $checkOut = new \DateTime('2024-01-03');
        $this->orderItem->setCheckInDate($checkIn)
            ->setCheckOutDate($checkOut);

        $result = $this->orderItem->setUnitPrice('75.50');

        $this->assertSame($this->orderItem, $result);
        $this->assertSame('75.50', $this->orderItem->getUnitPrice());
        $this->assertSame('151.00', $this->orderItem->getAmount()); // 2 nights * 75.50
    }

    public function test_setCostPrice_calculates_profit(): void
    {
        $checkIn = new \DateTime('2024-01-01');
        $checkOut = new \DateTime('2024-01-03');
        $this->orderItem->setCheckInDate($checkIn)
            ->setCheckOutDate($checkOut)
            ->setUnitPrice('100.00');

        $result = $this->orderItem->setCostPrice('80.00');

        $this->assertSame($this->orderItem, $result);
        $this->assertSame('80.00', $this->orderItem->getCostPrice());
        $this->assertSame('40.00', $this->orderItem->getProfit()); // (100-80) * 2 nights
    }

    public function test_setAmount_calculates_profit(): void
    {
        $checkIn = new \DateTime('2024-01-01');
        $checkOut = new \DateTime('2024-01-03');
        $this->orderItem->setCheckInDate($checkIn)
            ->setCheckOutDate($checkOut)
            ->setCostPrice('60.00');

        $result = $this->orderItem->setAmount('250.00');

        $this->assertSame($this->orderItem, $result);
        $this->assertSame('250.00', $this->orderItem->getAmount());
        $this->assertSame('130.00', $this->orderItem->getProfit()); // 250 - (60 * 2 nights)
    }

    public function test_setContract_with_valid_contract(): void
    {
        $contract = $this->createMock(HotelContract::class);
        $result = $this->orderItem->setContract($contract);

        $this->assertSame($this->orderItem, $result);
        $this->assertSame($contract, $this->orderItem->getContract());
    }

    public function test_setDailyInventory_with_valid_inventory(): void
    {
        $inventory = $this->createMock(DailyInventory::class);
        $result = $this->orderItem->setDailyInventory($inventory);

        $this->assertSame($this->orderItem, $result);
        $this->assertSame($inventory, $this->orderItem->getDailyInventory());
    }

    public function test_setStatus_with_valid_status(): void
    {
        $result = $this->orderItem->setStatus(OrderItemStatusEnum::CONFIRMED);

        $this->assertSame($this->orderItem, $result);
        $this->assertSame(OrderItemStatusEnum::CONFIRMED, $this->orderItem->getStatus());
    }

    public function test_setContractChangeHistory_with_valid_history(): void
    {
        $history = [['timestamp' => '2024-01-01', 'action' => 'change']];
        $result = $this->orderItem->setContractChangeHistory($history);

        $this->assertSame($this->orderItem, $result);
        $this->assertSame($history, $this->orderItem->getContractChangeHistory());
    }

    public function test_setContractChangeHistory_with_null(): void
    {
        $result = $this->orderItem->setContractChangeHistory(null);

        $this->assertSame($this->orderItem, $result);
        $this->assertNull($this->orderItem->getContractChangeHistory());
    }

    public function test_setContractChangeReason_with_valid_reason(): void
    {
        $result = $this->orderItem->setContractChangeReason('合同价格调整');

        $this->assertSame($this->orderItem, $result);
        $this->assertSame('合同价格调整', $this->orderItem->getContractChangeReason());
    }

    public function test_setLastModifiedBy_with_user_id(): void
    {
        $result = $this->orderItem->setLastModifiedBy(123);

        $this->assertSame($this->orderItem, $result);
        $this->assertSame(123, $this->orderItem->getLastModifiedBy());
    }

    public function test_changeContract_records_change_history(): void
    {
        $oldContract = $this->createMock(HotelContract::class);
        $oldContract->method('getId')->willReturn(1);

        $newContract = $this->createMock(HotelContract::class);
        $newContract->method('getId')->willReturn(2);

        $this->orderItem->setContract($oldContract);

        $result = $this->orderItem->changeContract($newContract, '价格调整', 123);

        $this->assertSame($this->orderItem, $result);
        $this->assertSame($newContract, $this->orderItem->getContract());
        $this->assertSame('价格调整', $this->orderItem->getContractChangeReason());
        $this->assertSame(123, $this->orderItem->getLastModifiedBy());

        $history = $this->orderItem->getContractChangeHistory();
        $this->assertCount(1, $history);
        $this->assertSame(1, $history[0]['oldContractId']);
        $this->assertSame(2, $history[0]['newContractId']);
        $this->assertSame('价格调整', $history[0]['reason']);
        $this->assertSame(123, $history[0]['operatorId']);
    }

    public function test_changeContract_with_null_old_contract(): void
    {
        $newContract = $this->createMock(HotelContract::class);
        $newContract->method('getId')->willReturn(2);

        $this->orderItem->changeContract($newContract, '初次分配', 123);

        $history = $this->orderItem->getContractChangeHistory();
        $this->assertNull($history[0]['oldContractId']);
        $this->assertSame(2, $history[0]['newContractId']);
    }

    public function test_cancel_updates_status(): void
    {
        $result = $this->orderItem->cancel();

        $this->assertSame($this->orderItem, $result);
        $this->assertSame(OrderItemStatusEnum::CANCELED, $this->orderItem->getStatus());
    }

    public function test_confirm_updates_status(): void
    {
        $result = $this->orderItem->confirm();

        $this->assertSame($this->orderItem, $result);
        $this->assertSame(OrderItemStatusEnum::CONFIRMED, $this->orderItem->getStatus());
    }

    public function test_complete_updates_status(): void
    {
        $result = $this->orderItem->complete();

        $this->assertSame($this->orderItem, $result);
        $this->assertSame(OrderItemStatusEnum::COMPLETED, $this->orderItem->getStatus());
    }

    public function test_setCreateTime_sets_time(): void
    {
        $time = new \DateTimeImmutable();

        $this->orderItem->setCreateTime($time);

        $this->assertSame($time, $this->orderItem->getCreateTime());
    }

    public function test_setUpdateTime_sets_time(): void
    {
        $time = new \DateTimeImmutable();

        $this->orderItem->setUpdateTime($time);

        $this->assertSame($time, $this->orderItem->getUpdateTime());
    }

    public function test_amount_calculation_with_zero_nights(): void
    {
        $checkIn = new \DateTime('2024-01-01');
        $checkOut = new \DateTime('2024-01-01'); // 同一天
        $this->orderItem->setCheckInDate($checkIn)
            ->setCheckOutDate($checkOut)
            ->setUnitPrice('100.00');

        $this->assertSame('0.00', $this->orderItem->getAmount());
    }

    public function test_profit_calculation_with_zero_cost(): void
    {
        $checkIn = new \DateTime('2024-01-01');
        $checkOut = new \DateTime('2024-01-03');
        $this->orderItem->setCheckInDate($checkIn)
            ->setCheckOutDate($checkOut)
            ->setUnitPrice('100.00')
            ->setCostPrice('0.00');

        $this->assertSame('200.00', $this->orderItem->getProfit());
    }

    public function test_default_values(): void
    {
        $this->assertNull($this->orderItem->getId());
        $this->assertNull($this->orderItem->getOrder());
        $this->assertNull($this->orderItem->getHotel());
        $this->assertNull($this->orderItem->getRoomType());
        $this->assertNull($this->orderItem->getCheckInDate());
        $this->assertNull($this->orderItem->getCheckOutDate());
        $this->assertSame('0.00', $this->orderItem->getUnitPrice());
        $this->assertSame('0.00', $this->orderItem->getCostPrice());
        $this->assertSame('0.00', $this->orderItem->getAmount());
        $this->assertSame('0.00', $this->orderItem->getProfit());
        $this->assertNull($this->orderItem->getContract());
        $this->assertNull($this->orderItem->getDailyInventory());
        $this->assertSame(OrderItemStatusEnum::PENDING, $this->orderItem->getStatus());
        $this->assertSame([], $this->orderItem->getContractChangeHistory());
        $this->assertNull($this->orderItem->getContractChangeReason());
        $this->assertNull($this->orderItem->getCreateTime());
        $this->assertNull($this->orderItem->getUpdateTime());
        $this->assertNull($this->orderItem->getLastModifiedBy());
    }
}
