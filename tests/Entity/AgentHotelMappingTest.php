<?php

namespace Tourze\HotelAgentBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentHotelMapping;
use Tourze\HotelProfileBundle\Entity\Hotel;

class AgentHotelMappingTest extends TestCase
{
    private AgentHotelMapping $mapping;

    protected function setUp(): void
    {
        $this->mapping = new AgentHotelMapping();
    }

    public function test_toString_returns_agent_and_hotel_names(): void
    {
        $agent = new Agent();
        $agent->setCompanyName('测试代理公司');

        $hotel = $this->createMock(Hotel::class);
        $hotel->method('getName')->willReturn('测试酒店');

        $this->mapping->setAgent($agent)->setHotel($hotel);

        $this->assertSame('测试代理公司 - 测试酒店', $this->mapping->__toString());
    }

    public function test_toString_with_null_agent_and_hotel(): void
    {
        $result = $this->mapping->__toString();

        $this->assertSame('Unknown - Unknown', $result);
    }

    public function test_toString_with_null_agent(): void
    {
        $hotel = $this->createMock(Hotel::class);
        $hotel->method('getName')->willReturn('测试酒店');

        $this->mapping->setHotel($hotel);

        $this->assertSame('Unknown - 测试酒店', $this->mapping->__toString());
    }

    public function test_toString_with_null_hotel(): void
    {
        $agent = new Agent();
        $agent->setCompanyName('测试代理公司');

        $this->mapping->setAgent($agent);

        $this->assertSame('测试代理公司 - Unknown', $this->mapping->__toString());
    }

    public function test_setAgent_with_valid_agent(): void
    {
        $agent = new Agent();

        $result = $this->mapping->setAgent($agent);

        $this->assertSame($this->mapping, $result);
        $this->assertSame($agent, $this->mapping->getAgent());
    }

    public function test_setAgent_with_null(): void
    {
        $result = $this->mapping->setAgent(null);

        $this->assertSame($this->mapping, $result);
        $this->assertNull($this->mapping->getAgent());
    }

    public function test_setHotel_with_valid_hotel(): void
    {
        $hotel = $this->createMock(Hotel::class);

        $result = $this->mapping->setHotel($hotel);

        $this->assertSame($this->mapping, $result);
        $this->assertSame($hotel, $this->mapping->getHotel());
    }

    public function test_setHotel_with_null(): void
    {
        $result = $this->mapping->setHotel(null);

        $this->assertSame($this->mapping, $result);
        $this->assertNull($this->mapping->getHotel());
    }

    public function test_setRoomTypeIds_with_valid_array(): void
    {
        $roomTypeIds = [1, 2, 3];

        $result = $this->mapping->setRoomTypeIds($roomTypeIds);

        $this->assertSame($this->mapping, $result);
        $this->assertSame($roomTypeIds, $this->mapping->getRoomTypeIds());
    }

    public function test_setRoomTypeIds_with_empty_array(): void
    {
        $result = $this->mapping->setRoomTypeIds([]);

        $this->assertSame($this->mapping, $result);
        $this->assertSame([], $this->mapping->getRoomTypeIds());
    }

    public function test_addRoomTypeId_adds_new_id(): void
    {
        $result = $this->mapping->addRoomTypeId(1);

        $this->assertSame($this->mapping, $result);
        $this->assertContains(1, $this->mapping->getRoomTypeIds());
    }

    public function test_addRoomTypeId_does_not_add_duplicate(): void
    {
        $this->mapping->setRoomTypeIds([1, 2]);

        $this->mapping->addRoomTypeId(1);

        $this->assertSame([1, 2], $this->mapping->getRoomTypeIds());
    }

    public function test_addRoomTypeId_with_zero(): void
    {
        $result = $this->mapping->addRoomTypeId(0);

        $this->assertSame($this->mapping, $result);
        $this->assertContains(0, $this->mapping->getRoomTypeIds());
    }

    public function test_removeRoomTypeId_removes_existing_id(): void
    {
        $this->mapping->setRoomTypeIds([1, 2, 3]);

        $result = $this->mapping->removeRoomTypeId(2);

        $this->assertSame($this->mapping, $result);
        $this->assertSame([0 => 1, 2 => 3], $this->mapping->getRoomTypeIds());
    }

    public function test_removeRoomTypeId_with_non_existing_id(): void
    {
        $this->mapping->setRoomTypeIds([1, 2, 3]);

        $this->mapping->removeRoomTypeId(4);

        $this->assertSame([1, 2, 3], $this->mapping->getRoomTypeIds());
    }

    public function test_removeRoomTypeId_from_empty_array(): void
    {
        $result = $this->mapping->removeRoomTypeId(1);

        $this->assertSame($this->mapping, $result);
        $this->assertSame([], $this->mapping->getRoomTypeIds());
    }

    public function test_hasRoomTypeId_returns_true_when_exists(): void
    {
        $this->mapping->setRoomTypeIds([1, 2, 3]);

        $this->assertTrue($this->mapping->hasRoomTypeId(2));
    }

    public function test_hasRoomTypeId_returns_false_when_not_exists(): void
    {
        $this->mapping->setRoomTypeIds([1, 2, 3]);

        $this->assertFalse($this->mapping->hasRoomTypeId(4));
    }

    public function test_hasRoomTypeId_with_empty_array(): void
    {
        $this->assertFalse($this->mapping->hasRoomTypeId(1));
    }

    public function test_hasRoomTypeId_with_zero(): void
    {
        $this->mapping->setRoomTypeIds([0, 1, 2]);

        $this->assertTrue($this->mapping->hasRoomTypeId(0));
    }

    public function test_setCreateTime_sets_time(): void
    {
        $time = new \DateTimeImmutable();

        $this->mapping->setCreateTime($time);

        $this->assertSame($time, $this->mapping->getCreateTime());
    }

    public function test_setUpdateTime_sets_time(): void
    {
        $time = new \DateTimeImmutable();

        $this->mapping->setUpdateTime($time);

        $this->assertSame($time, $this->mapping->getUpdateTime());
    }

    public function test_default_values(): void
    {
        $this->assertNull($this->mapping->getId());
        $this->assertNull($this->mapping->getAgent());
        $this->assertNull($this->mapping->getHotel());
        $this->assertSame([], $this->mapping->getRoomTypeIds());
        $this->assertNull($this->mapping->getCreateTime());
        $this->assertNull($this->mapping->getUpdateTime());
        $this->assertNull($this->mapping->getCreatedBy());
    }
}
