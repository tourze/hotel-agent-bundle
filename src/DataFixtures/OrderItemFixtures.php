<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Entity\OrderItem;
use Tourze\HotelAgentBundle\Enum\OrderItemStatusEnum;

/**
 * 订单项测试数据夹具
 *
 * 注意：此夹具依赖外部Bundle的Hotel和RoomType实体，仅在集成测试环境中可用
 */
#[When(env: 'test')]
class OrderItemFixtures extends Fixture implements DependentFixtureInterface
{
    public const ORDER_ITEM_1_REFERENCE = 'order-item-1';
    public const ORDER_ITEM_2_REFERENCE = 'order-item-2';
    public const ORDER_ITEM_3_REFERENCE = 'order-item-3';

    public function load(ObjectManager $manager): void
    {
        // 此夹具依赖外部Bundle的Hotel和RoomType实体，在单元测试中跳过
        // 检查是否存在必要的依赖，如果不存在则跳过
        if (!$this->hasReference(OrderFixtures::ORDER_CONFIRMED_REFERENCE, Order::class)) {
            return;
        }

        // 注意：实际的OrderItem创建需要在集成测试中配置Hotel和RoomType实体
        // 这里只提供数据结构示例，不创建实际的订单项

        // 示例代码仅用于展示数据结构，实际订单项需要有效的Hotel和RoomType实体
        /*
        $order = $this->getReference(OrderFixtures::ORDER_CONFIRMED_REFERENCE, Order::class);

        $orderItem1 = new OrderItem();
        $orderItem1->setOrder($order);
        // $orderItem1->setHotel($hotelEntity); // 需要有效的Hotel实体
        // $orderItem1->setRoomType($roomTypeEntity); // 需要有效的RoomType实体
        $orderItem1->setCheckInDate(new \DateTimeImmutable('2024-01-01'));
        $orderItem1->setCheckOutDate(new \DateTimeImmutable('2024-01-03'));
        $orderItem1->setUnitPrice('299.00');
        $orderItem1->setCostPrice('199.00');
        $orderItem1->setStatus(OrderItemStatusEnum::CONFIRMED);
        $orderItem1->setLastModifiedBy(1);
        $manager->persist($orderItem1);
        */

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            OrderFixtures::class,
            // HotelFixtures::class, // 移除跨Bundle依赖
            // RoomTypeFixtures::class, // 移除跨Bundle依赖
        ];
    }
}
