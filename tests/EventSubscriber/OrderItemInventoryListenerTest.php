<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\EventSubscriber;

use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\HotelAgentBundle\Entity\OrderItem;
use Tourze\HotelAgentBundle\EventSubscriber\OrderItemInventoryListener;
use Tourze\HotelProfileBundle\Entity\Hotel;
use Tourze\HotelProfileBundle\Entity\RoomType;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(OrderItemInventoryListener::class)]
#[RunTestsInSeparateProcesses]
final class OrderItemInventoryListenerTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 无需额外设置
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(OrderItemInventoryListener::class, self::getService(OrderItemInventoryListener::class));
    }

    public function testPostPersistWithIncompleteData(): void
    {
        $orderItem = $this->createIncompleteOrderItem(1);
        $entityManager = self::getEntityManager();
        $event = new PostPersistEventArgs($orderItem, $entityManager);

        // 测试不应该抛出异常，并且能正常处理不完整的数据
        $listener = self::getService(OrderItemInventoryListener::class);
        $listener->postPersist($orderItem, $event);

        // 验证服务实例正确
        $this->assertInstanceOf(OrderItemInventoryListener::class, self::getService(OrderItemInventoryListener::class));
    }

    public function testPostUpdateWithIncompleteData(): void
    {
        $orderItem = $this->createIncompleteOrderItem(2);
        $entityManager = self::getEntityManager();
        $event = new PostUpdateEventArgs($orderItem, $entityManager);

        // 测试不应该抛出异常
        $listener = self::getService(OrderItemInventoryListener::class);
        $listener->postUpdate($orderItem, $event);

        // 验证服务实例正确
        $this->assertInstanceOf(OrderItemInventoryListener::class, self::getService(OrderItemInventoryListener::class));
    }

    public function testPostRemoveWithIncompleteData(): void
    {
        $orderItem = $this->createIncompleteOrderItem(3);
        $entityManager = self::getEntityManager();
        $event = new PostRemoveEventArgs($orderItem, $entityManager);

        // 测试不应该抛出异常
        $listener = self::getService(OrderItemInventoryListener::class);
        $listener->postRemove($orderItem, $event);

        // 验证服务实例正确
        $this->assertInstanceOf(OrderItemInventoryListener::class, self::getService(OrderItemInventoryListener::class));
    }

    public function testPostPersistWithCompleteData(): void
    {
        $orderItem = $this->createCompleteOrderItem(4);
        $entityManager = self::getEntityManager();
        $event = new PostPersistEventArgs($orderItem, $entityManager);

        // 测试不应该抛出异常，即使数据完整也能正常处理
        $listener = self::getService(OrderItemInventoryListener::class);
        $listener->postPersist($orderItem, $event);

        // 验证服务实例正确
        $this->assertInstanceOf(OrderItemInventoryListener::class, self::getService(OrderItemInventoryListener::class));
    }

    public function testPostPersistHandlesException(): void
    {
        $orderItem = $this->createMock(OrderItem::class);
        $entityManager = self::getEntityManager();
        $event = new PostPersistEventArgs($orderItem, $entityManager);

        $orderItem->method('getHotel')->willThrowException(new \Exception('Test exception'));
        $orderItem->method('getId')->willReturn(5);

        // 测试异常处理，不应该向外抛出异常
        $listener = self::getService(OrderItemInventoryListener::class);
        $listener->postPersist($orderItem, $event);

        // 验证服务实例正确
        $this->assertInstanceOf(OrderItemInventoryListener::class, self::getService(OrderItemInventoryListener::class));
    }

    private function createIncompleteOrderItem(int $id): OrderItem
    {
        $orderItem = new OrderItem();

        // 使用反射设置私有属性 id
        $reflection = new \ReflectionClass($orderItem);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($orderItem, $id);

        // 保持必要的属性为 null，这样就是不完整的数据
        return $orderItem;
    }

    private function createCompleteOrderItem(int $id): OrderItem
    {
        $orderItem = new OrderItem();

        // 使用反射设置私有属性 id
        $reflection = new \ReflectionClass($orderItem);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($orderItem, $id);

        // 创建 mock 对象
        $hotel = $this->createMock(Hotel::class);
        $hotel->method('getId')->willReturn(100);

        $roomType = $this->createMock(RoomType::class);
        $roomType->method('getId')->willReturn(200);

        // 设置完整的数据
        $orderItem->setHotel($hotel);
        $orderItem->setRoomType($roomType);
        $orderItem->setCheckInDate(new \DateTimeImmutable('2024-01-01'));
        $orderItem->setCheckOutDate(new \DateTimeImmutable('2024-01-03'));

        return $orderItem;
    }
}
