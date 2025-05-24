<?php

namespace Tourze\HotelAgentBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Entity\OrderItem;
use Tourze\HotelAgentBundle\Enum\AuditStatusEnum;
use Tourze\HotelAgentBundle\Enum\OrderItemStatusEnum;
use Tourze\HotelAgentBundle\Enum\OrderSourceEnum;
use Tourze\HotelAgentBundle\Enum\OrderStatusEnum;
use Tourze\HotelProfileBundle\Entity\Hotel;
use Tourze\HotelProfileBundle\Entity\RoomType;

/**
 * 订单数据填充
 * 创建测试用的订单和订单项数据，供其他模块使用
 */
class OrderFixtures extends Fixture implements DependentFixtureInterface
{
    // 引用名称常量
    public const ORDER_CONFIRMED_REFERENCE = 'order-confirmed';
    public const ORDER_PENDING_REFERENCE = 'order-pending';
    public const ORDER_CANCELLED_REFERENCE = 'order-cancelled';
    public const ORDER_COMPLEX_REFERENCE = 'order-complex';

    // 基础数据引用
    public const HOTEL_SAMPLE_REFERENCE = 'hotel-sample';
    public const HOTEL_BUSINESS_REFERENCE = 'hotel-business';
    public const HOTEL_LUXURY_REFERENCE = 'hotel-luxury';
    public const ROOMTYPE_STANDARD_REFERENCE = 'roomtype-standard';
    public const ROOMTYPE_DELUXE_REFERENCE = 'roomtype-deluxe';
    public const ROOMTYPE_SUITE_REFERENCE = 'roomtype-suite';

    public function load(ObjectManager $manager): void
    {
        // 创建基础酒店数据
        $this->createHotelsAndRoomTypes($manager);

        // 获取代理引用
        $agentA = $this->getReference(AgentFixtures::AGENT_A_LEVEL_REFERENCE, Agent::class);
        $agentB = $this->getReference(AgentFixtures::AGENT_B_LEVEL_REFERENCE, Agent::class);
        $agentC = $this->getReference(AgentFixtures::AGENT_C_LEVEL_REFERENCE, Agent::class);

        // 获取酒店和房型引用
        $hotelSample = $this->getReference(self::HOTEL_SAMPLE_REFERENCE, Hotel::class);
        $hotelBusiness = $this->getReference(self::HOTEL_BUSINESS_REFERENCE, Hotel::class);
        $hotelLuxury = $this->getReference(self::HOTEL_LUXURY_REFERENCE, Hotel::class);
        $roomTypeStandard = $this->getReference(self::ROOMTYPE_STANDARD_REFERENCE, RoomType::class);
        $roomTypeDeluxe = $this->getReference(self::ROOMTYPE_DELUXE_REFERENCE, RoomType::class);
        $roomTypeSuite = $this->getReference(self::ROOMTYPE_SUITE_REFERENCE, RoomType::class);

        // 创建已确认订单
        $order1 = new Order();
        $order1->setOrderNo('ORD' . date('Ymd') . '001');
        $order1->setAgent($agentA);
        $order1->setStatus(OrderStatusEnum::CONFIRMED);
        $order1->setSource(OrderSourceEnum::MANUAL_INPUT);
        $order1->setAuditStatus(AuditStatusEnum::APPROVED);
        $order1->setRemark('A级代理确认订单');
        $order1->setCreatedBy(1);

        // 为订单1添加订单项
        $orderItem1 = new OrderItem();
        $orderItem1->setOrder($order1);
        $orderItem1->setHotel($hotelSample);
        $orderItem1->setRoomType($roomTypeStandard);
        $orderItem1->setCheckInDate(new \DateTime('+3 days'));
        $orderItem1->setCheckOutDate(new \DateTime('+5 days'));
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
        $order2->setCreatedBy(1);

        // 为订单2添加订单项
        $orderItem2 = new OrderItem();
        $orderItem2->setOrder($order2);
        $orderItem2->setHotel($hotelBusiness);
        $orderItem2->setRoomType($roomTypeDeluxe);
        $orderItem2->setCheckInDate(new \DateTime('+7 days'));
        $orderItem2->setCheckOutDate(new \DateTime('+10 days'));
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
        $order3->setCancelTime(new \DateTime('-1 day'));
        $order3->setCancelledBy(1);
        $order3->setCreatedBy(1);

        // 为订单3添加订单项
        $orderItem3 = new OrderItem();
        $orderItem3->setOrder($order3);
        $orderItem3->setHotel($hotelSample);
        $orderItem3->setRoomType($roomTypeStandard);
        $orderItem3->setCheckInDate(new \DateTime('+1 day'));
        $orderItem3->setCheckOutDate(new \DateTime('+3 days'));
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
        $order4->setCreatedBy(1);

        // 为复合订单添加多个订单项
        $orderItem4a = new OrderItem();
        $orderItem4a->setOrder($order4);
        $orderItem4a->setHotel($hotelBusiness);
        $orderItem4a->setRoomType($roomTypeDeluxe);
        $orderItem4a->setCheckInDate(new \DateTime('+14 days'));
        $orderItem4a->setCheckOutDate(new \DateTime('+16 days'));
        $orderItem4a->setUnitPrice('600.00');
        $orderItem4a->setCostPrice('480.00');
        $orderItem4a->setStatus(OrderItemStatusEnum::CONFIRMED);

        $orderItem4b = new OrderItem();
        $orderItem4b->setOrder($order4);
        $orderItem4b->setHotel($hotelLuxury);
        $orderItem4b->setRoomType($roomTypeSuite);
        $orderItem4b->setCheckInDate(new \DateTime('+16 days'));
        $orderItem4b->setCheckOutDate(new \DateTime('+18 days'));
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

    private function createHotelsAndRoomTypes(ObjectManager $manager): void
    {
        // 创建示例酒店
        $hotel1 = new Hotel();
        $hotel1->setName('示例经济型酒店');
        $hotel1->setAddress('北京市朝阳区示例路123号');
        $hotel1->setPhone('010-12345678');
        $hotel1->setStarLevel(3);
        $hotel1->setContactPerson('张经理');
        
        $hotel2 = new Hotel();
        $hotel2->setName('示例商务酒店');
        $hotel2->setAddress('上海市浦东新区商务大道456号');
        $hotel2->setPhone('021-87654321');
        $hotel2->setStarLevel(4);
        $hotel2->setContactPerson('李经理');
        
        $hotel3 = new Hotel();
        $hotel3->setName('示例豪华酒店');
        $hotel3->setAddress('深圳市南山区豪华街789号');
        $hotel3->setPhone('0755-11223344');
        $hotel3->setStarLevel(5);
        $hotel3->setContactPerson('王经理');

        $manager->persist($hotel1);
        $manager->persist($hotel2);
        $manager->persist($hotel3);

        // 创建房型
        $roomType1 = new RoomType();
        $roomType1->setHotel($hotel1);
        $roomType1->setName('标准间');
        $roomType1->setDescription('标准双床房，配备基础设施');
        $roomType1->setBedType('双床');
        $roomType1->setMaxGuests(2);
        $roomType1->setArea(25.0);

        $roomType2 = new RoomType();
        $roomType2->setHotel($hotel2);
        $roomType2->setName('豪华间');
        $roomType2->setDescription('豪华大床房，配备高端设施');
        $roomType2->setBedType('大床');
        $roomType2->setMaxGuests(2);
        $roomType2->setArea(35.0);

        $roomType3 = new RoomType();
        $roomType3->setHotel($hotel3);
        $roomType3->setName('套房');
        $roomType3->setDescription('豪华套房，独立客厅卧室');
        $roomType3->setBedType('大床');
        $roomType3->setMaxGuests(4);
        $roomType3->setArea(60.0);

        $manager->persist($roomType1);
        $manager->persist($roomType2);
        $manager->persist($roomType3);

        // 设置引用
        $this->addReference(self::HOTEL_SAMPLE_REFERENCE, $hotel1);
        $this->addReference(self::HOTEL_BUSINESS_REFERENCE, $hotel2);
        $this->addReference(self::HOTEL_LUXURY_REFERENCE, $hotel3);
        $this->addReference(self::ROOMTYPE_STANDARD_REFERENCE, $roomType1);
        $this->addReference(self::ROOMTYPE_DELUXE_REFERENCE, $roomType2);
        $this->addReference(self::ROOMTYPE_SUITE_REFERENCE, $roomType3);
    }

    public function getDependencies(): array
    {
        return [
            AgentFixtures::class,
        ];
    }
}
