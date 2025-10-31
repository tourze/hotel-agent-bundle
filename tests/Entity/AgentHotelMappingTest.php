<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentHotelMapping;
use Tourze\HotelProfileBundle\Entity\Hotel;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(AgentHotelMapping::class)]
final class AgentHotelMappingTest extends AbstractEntityTestCase
{
    public function testToStringReturnsAgentAndHotelNames(): void
    {
        $agent = new Agent();
        $agent->setCompanyName('测试代理公司');

        /*
         * 使用具体类 Hotel 创建 mock 对象的原因：
         * 1. Hotel 是来自 hotel-profile-bundle 的实体类，没有对应的接口定义
         * 2. 在测试中只需要模拟 getName() 方法的返回值，使用具体类是合理的
         * 3. 由于这是外部依赖的实体类，创建接口会增加不必要的复杂性
         */
        $hotel = $this->createMock(Hotel::class);
        $hotel->method('getName')->willReturn('测试酒店');

        $mapping = new AgentHotelMapping();
        $mapping->setAgent($agent);
        $mapping->setHotel($hotel);

        $this->assertSame('测试代理公司 - 测试酒店', $mapping->__toString());
    }

    public function testToStringWithNullAgentAndHotel(): void
    {
        $mapping = new AgentHotelMapping();
        $result = $mapping->__toString();

        $this->assertSame('Unknown - Unknown', $result);
    }

    public function testToStringWithNullAgent(): void
    {
        /*
         * 使用具体类 Hotel 创建 mock 对象的原因：
         * 1. Hotel 是来自 hotel-profile-bundle 的实体类，没有对应的接口定义
         * 2. 在测试中只需要模拟 getName() 方法的返回值，使用具体类是合理的
         * 3. 由于这是外部依赖的实体类，创建接口会增加不必要的复杂性
         */
        $hotel = $this->createMock(Hotel::class);
        $hotel->method('getName')->willReturn('测试酒店');

        $mapping = new AgentHotelMapping();
        $mapping->setHotel($hotel);

        $this->assertSame('Unknown - 测试酒店', $mapping->__toString());
    }

    public function testToStringWithNullHotel(): void
    {
        $agent = new Agent();
        $agent->setCompanyName('测试代理公司');

        $mapping = new AgentHotelMapping();
        $mapping->setAgent($agent);

        $this->assertSame('测试代理公司 - Unknown', $mapping->__toString());
    }

    public function testSetAgentWithValidAgent(): void
    {
        $agent = new Agent();
        $mapping = new AgentHotelMapping();

        $mapping->setAgent($agent);
        $this->assertSame($agent, $mapping->getAgent());
    }

    public function testSetAgentWithNull(): void
    {
        $mapping = new AgentHotelMapping();
        $mapping->setAgent(null);
        $this->assertNull($mapping->getAgent());
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
        $mapping = new AgentHotelMapping();

        $mapping->setHotel($hotel);
        $this->assertSame($hotel, $mapping->getHotel());
    }

    public function testSetHotelWithNull(): void
    {
        $mapping = new AgentHotelMapping();
        $mapping->setHotel(null);
        $this->assertNull($mapping->getHotel());
    }

    public function testSetRoomTypeIdsWithValidArray(): void
    {
        $roomTypeIds = [1, 2, 3];
        $mapping = new AgentHotelMapping();

        $mapping->setRoomTypeIds($roomTypeIds);
        $this->assertSame($roomTypeIds, $mapping->getRoomTypeIds());
    }

    public function testSetRoomTypeIdsWithEmptyArray(): void
    {
        $mapping = new AgentHotelMapping();
        $mapping->setRoomTypeIds([]);
        $this->assertSame([], $mapping->getRoomTypeIds());
    }

    public function testAddRoomTypeIdAddsNewId(): void
    {
        $mapping = new AgentHotelMapping();
        $mapping->addRoomTypeId(1);
        $this->assertContains(1, $mapping->getRoomTypeIds());
    }

    public function testAddRoomTypeIdDoesNotAddDuplicate(): void
    {
        $mapping = new AgentHotelMapping();
        $mapping->setRoomTypeIds([1, 2]);

        $mapping->addRoomTypeId(1);

        $this->assertSame([1, 2], $mapping->getRoomTypeIds());
    }

    public function testAddRoomTypeIdWithZero(): void
    {
        $mapping = new AgentHotelMapping();
        $mapping->addRoomTypeId(0);
        $this->assertContains(0, $mapping->getRoomTypeIds());
    }

    public function testRemoveRoomTypeIdRemovesExistingId(): void
    {
        $mapping = new AgentHotelMapping();
        $mapping->setRoomTypeIds([1, 2, 3]);

        $mapping->removeRoomTypeId(2);
        $this->assertSame([0 => 1, 2 => 3], $mapping->getRoomTypeIds());
    }

    public function testRemoveRoomTypeIdWithNonExistingId(): void
    {
        $mapping = new AgentHotelMapping();
        $mapping->setRoomTypeIds([1, 2, 3]);

        $mapping->removeRoomTypeId(4);

        $this->assertSame([1, 2, 3], $mapping->getRoomTypeIds());
    }

    public function testRemoveRoomTypeIdFromEmptyArray(): void
    {
        $mapping = new AgentHotelMapping();
        $mapping->removeRoomTypeId(1);
        $this->assertSame([], $mapping->getRoomTypeIds());
    }

    public function testHasRoomTypeIdReturnsTrueWhenExists(): void
    {
        $mapping = new AgentHotelMapping();
        $mapping->setRoomTypeIds([1, 2, 3]);

        $this->assertTrue($mapping->hasRoomTypeId(2));
    }

    public function testHasRoomTypeIdReturnsFalseWhenNotExists(): void
    {
        $mapping = new AgentHotelMapping();
        $mapping->setRoomTypeIds([1, 2, 3]);

        $this->assertFalse($mapping->hasRoomTypeId(4));
    }

    public function testHasRoomTypeIdWithEmptyArray(): void
    {
        $mapping = new AgentHotelMapping();
        $this->assertFalse($mapping->hasRoomTypeId(1));
    }

    public function testHasRoomTypeIdWithZero(): void
    {
        $mapping = new AgentHotelMapping();
        $mapping->setRoomTypeIds([0, 1, 2]);

        $this->assertTrue($mapping->hasRoomTypeId(0));
    }

    public function testSetCreateTimeSetsTime(): void
    {
        $time = new \DateTimeImmutable();
        $mapping = new AgentHotelMapping();
        $mapping->setCreateTime($time);

        $this->assertSame($time, $mapping->getCreateTime());
    }

    public function testSetUpdateTimeSetsTime(): void
    {
        $time = new \DateTimeImmutable();
        $mapping = new AgentHotelMapping();
        $mapping->setUpdateTime($time);

        $this->assertSame($time, $mapping->getUpdateTime());
    }

    public static function propertiesProvider(): iterable
    {
        $agent = new Agent();
        $agent->setCode('TEST001');
        $agent->setCompanyName('测试代理公司');
        $agent->setCreatedBy('test-user');

        $hotel = new Hotel();
        $hotel->setName('测试酒店');

        yield 'valid_mapping' => ['agent', $agent];
        yield 'empty_room_types' => ['hotel', $hotel];
        yield 'single_room_type' => ['roomTypeIds', [5]];
    }

    protected function createEntity(): object
    {
        return new AgentHotelMapping();
    }
}
