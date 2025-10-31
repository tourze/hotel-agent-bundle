<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Entity\OrderItem;
use Tourze\HotelAgentBundle\Enum\AuditStatusEnum;
use Tourze\HotelAgentBundle\Enum\OrderItemStatusEnum;
use Tourze\HotelAgentBundle\Enum\OrderSourceEnum;
use Tourze\HotelAgentBundle\Enum\OrderStatusEnum;
use Tourze\HotelProfileBundle\DataFixtures\HotelFixtures;
use Tourze\HotelProfileBundle\DataFixtures\RoomTypeFixtures;
use Tourze\HotelProfileBundle\Entity\Hotel;
use Tourze\HotelProfileBundle\Entity\RoomType;

/**
 * 订单数据填充
 * 创建测试用的订单和订单项数据，供其他模块使用
 */
#[When(env: 'test')]
class OrderFixtures extends Fixture implements DependentFixtureInterface
{
    // 引用名称常量
    public const ORDER_CONFIRMED_REFERENCE = 'order-confirmed';
    public const ORDER_PENDING_REFERENCE = 'order-pending';
    public const ORDER_CANCELLED_REFERENCE = 'order-cancelled';
    public const ORDER_COMPLEX_REFERENCE = 'order-complex';

    // 为了向后兼容，保留这些引用常量作为别名
    public const HOTEL_SAMPLE_REFERENCE = HotelFixtures::BUDGET_HOTEL_REFERENCE;
    public const HOTEL_BUSINESS_REFERENCE = HotelFixtures::BUSINESS_HOTEL_REFERENCE;
    public const HOTEL_LUXURY_REFERENCE = HotelFixtures::LUXURY_HOTEL_REFERENCE;
    public const ROOMTYPE_STANDARD_REFERENCE = RoomTypeFixtures::STANDARD_ROOM_REFERENCE;
    public const ROOMTYPE_DELUXE_REFERENCE = RoomTypeFixtures::BUSINESS_ROOM_REFERENCE;
    public const ROOMTYPE_SUITE_REFERENCE = RoomTypeFixtures::LUXURY_SUITE_REFERENCE;

    public function load(ObjectManager $manager): void
    {
        // 获取代理引用
        $agentA = $this->getReference(AgentFixtures::AGENT_A_LEVEL_REFERENCE, Agent::class);
        $agentB = $this->getReference(AgentFixtures::AGENT_B_LEVEL_REFERENCE, Agent::class);
        $agentC = $this->getReference(AgentFixtures::AGENT_C_LEVEL_REFERENCE, Agent::class);

        // 获取酒店和房型引用（使用标准的HotelFixtures和RoomTypeFixtures）
        $hotelSample = $this->getReference(HotelFixtures::BUDGET_HOTEL_REFERENCE, Hotel::class);
        $hotelBusiness = $this->getReference(HotelFixtures::BUSINESS_HOTEL_REFERENCE, Hotel::class);
        $hotelLuxury = $this->getReference(HotelFixtures::LUXURY_HOTEL_REFERENCE, Hotel::class);
        $roomTypeStandard = $this->getReference(RoomTypeFixtures::STANDARD_ROOM_REFERENCE, RoomType::class);
        $roomTypeDeluxe = $this->getReference(RoomTypeFixtures::BUSINESS_ROOM_REFERENCE, RoomType::class);
        $roomTypeSuite = $this->getReference(RoomTypeFixtures::LUXURY_SUITE_REFERENCE, RoomType::class);

        // 创建已确认订单
        $order1 = new Order();
        $order1->setOrderNo('ORD' . date('Ymd') . '001');
        $order1->setAgent($agentA);
        $order1->setStatus(OrderStatusEnum::CONFIRMED);
        $order1->setSource(OrderSourceEnum::MANUAL_INPUT);
        $order1->setAuditStatus(AuditStatusEnum::APPROVED);
        $order1->setRemark('A级代理确认订单');
        $order1->setCreatedBy('1');

        // 为订单1添加订单项
        $orderItem1 = new OrderItem();
        $orderItem1->setOrder($order1);
        $orderItem1->setHotel($hotelSample);
        $orderItem1->setRoomType($roomTypeStandard);
        $orderItem1->setCheckInDate(new \DateTimeImmutable('+3 days'));
        $orderItem1->setCheckOutDate(new \DateTimeImmutable('+5 days'));
        $orderItem1->setUnitPrice('300.00');
        $orderItem1->setCostPrice('250.00');
        $orderItem1->setStatus(OrderItemStatusEnum::CONFIRMED);

        $order1->addOrderItem($orderItem1);
        $order1->recalculateTotalAmount();

        $manager->persist($order1);
        $manager->persist($orderItem1);
        $this->addReference(self::ORDER_CONFIRMED_REFERENCE, $order1);

        // 创建待确认订单
        $order2 = new Order();
        $order2->setOrderNo('ORD' . date('Ymd') . '002');
        $order2->setAgent($agentB);
        $order2->setStatus(OrderStatusEnum::PENDING);
        $order2->setSource(OrderSourceEnum::MANUAL_INPUT);
        $order2->setAuditStatus(AuditStatusEnum::PENDING);
        $order2->setRemark('B级代理待确认订单');
        $order2->setCreatedBy('1');

        // 为订单2添加订单项
        $orderItem2 = new OrderItem();
        $orderItem2->setOrder($order2);
        $orderItem2->setHotel($hotelBusiness);
        $orderItem2->setRoomType($roomTypeDeluxe);
        $orderItem2->setCheckInDate(new \DateTimeImmutable('+7 days'));
        $orderItem2->setCheckOutDate(new \DateTimeImmutable('+10 days'));
        $orderItem2->setUnitPrice('500.00');
        $orderItem2->setCostPrice('400.00');
        $orderItem2->setStatus(OrderItemStatusEnum::PENDING);

        $order2->addOrderItem($orderItem2);
        $order2->recalculateTotalAmount();

        $manager->persist($order2);
        $manager->persist($orderItem2);
        $this->addReference(self::ORDER_PENDING_REFERENCE, $order2);

        // 创建已取消订单
        $order3 = new Order();
        $order3->setOrderNo('ORD' . date('Ymd') . '003');
        $order3->setAgent($agentC);
        $order3->setStatus(OrderStatusEnum::CANCELED);
        $order3->setSource(OrderSourceEnum::EXCEL_IMPORT);
        $order3->setAuditStatus(AuditStatusEnum::REJECTED);
        $order3->setRemark('C级代理取消订单');
        $order3->setCancelReason('客户要求取消');
        $order3->setCancelTime(new \DateTimeImmutable('-1 day'));
        $order3->setCancelledBy(1);
        $order3->setCreatedBy('1');

        // 为订单3添加订单项
        $orderItem3 = new OrderItem();
        $orderItem3->setOrder($order3);
        $orderItem3->setHotel($hotelSample);
        $orderItem3->setRoomType($roomTypeStandard);
        $orderItem3->setCheckInDate(new \DateTimeImmutable('+1 day'));
        $orderItem3->setCheckOutDate(new \DateTimeImmutable('+3 days'));
        $orderItem3->setUnitPrice('280.00');
        $orderItem3->setCostPrice('230.00');
        $orderItem3->setStatus(OrderItemStatusEnum::CANCELED);

        $order3->addOrderItem($orderItem3);
        $order3->recalculateTotalAmount();

        $manager->persist($order3);
        $manager->persist($orderItem3);
        $this->addReference(self::ORDER_CANCELLED_REFERENCE, $order3);

        // 创建复合订单（多个订单项）
        $order4 = new Order();
        $order4->setOrderNo('ORD' . date('Ymd') . '004');
        $order4->setAgent($agentA);
        $order4->setStatus(OrderStatusEnum::CONFIRMED);
        $order4->setSource(OrderSourceEnum::MANUAL_INPUT);
        $order4->setAuditStatus(AuditStatusEnum::APPROVED);
        $order4->setRemark('A级代理复合订单');
        $order4->setIsComplex(true);
        $order4->setCreatedBy('1');

        // 为复合订单添加多个订单项
        $orderItem4a = new OrderItem();
        $orderItem4a->setOrder($order4);
        $orderItem4a->setHotel($hotelBusiness);
        $orderItem4a->setRoomType($roomTypeDeluxe);
        $orderItem4a->setCheckInDate(new \DateTimeImmutable('+14 days'));
        $orderItem4a->setCheckOutDate(new \DateTimeImmutable('+16 days'));
        $orderItem4a->setUnitPrice('600.00');
        $orderItem4a->setCostPrice('480.00');
        $orderItem4a->setStatus(OrderItemStatusEnum::CONFIRMED);

        $orderItem4b = new OrderItem();
        $orderItem4b->setOrder($order4);
        $orderItem4b->setHotel($hotelLuxury);
        $orderItem4b->setRoomType($roomTypeSuite);
        $orderItem4b->setCheckInDate(new \DateTimeImmutable('+16 days'));
        $orderItem4b->setCheckOutDate(new \DateTimeImmutable('+18 days'));
        $orderItem4b->setUnitPrice('1200.00');
        $orderItem4b->setCostPrice('900.00');
        $orderItem4b->setStatus(OrderItemStatusEnum::CONFIRMED);

        $order4->addOrderItem($orderItem4a);
        $order4->addOrderItem($orderItem4b);
        $order4->recalculateTotalAmount();

        $manager->persist($order4);
        $manager->persist($orderItem4a);
        $manager->persist($orderItem4b);
        $this->addReference(self::ORDER_COMPLEX_REFERENCE, $order4);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AgentFixtures::class,
            HotelFixtures::class,
            RoomTypeFixtures::class,
        ];
    }
}
