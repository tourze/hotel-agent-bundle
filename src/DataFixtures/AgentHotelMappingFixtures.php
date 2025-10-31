<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentHotelMapping;
use Tourze\HotelProfileBundle\DataFixtures\HotelFixtures;
use Tourze\HotelProfileBundle\DataFixtures\RoomTypeFixtures;
use Tourze\HotelProfileBundle\Entity\Hotel;

/**
 * 代理酒店映射测试数据夹具
 *
 * 为代理分配可见的酒店和房型权限，演示不同等级代理的权限控制逻辑
 */
#[When(env: 'test')]
class AgentHotelMappingFixtures extends Fixture implements DependentFixtureInterface
{
    public const AGENT_LUXURY_HOTEL_MAPPING_REFERENCE = 'agent-luxury-hotel-mapping';
    public const AGENT_BUSINESS_HOTEL_MAPPING_REFERENCE = 'agent-business-hotel-mapping';
    public const AGENT_B_BUSINESS_HOTEL_MAPPING_REFERENCE = 'agent-b-business-hotel-mapping';

    public function load(ObjectManager $manager): void
    {
        // 检查是否存在必要的依赖，如果不存在则跳过
        if (!$this->hasReference(AgentFixtures::AGENT_A_LEVEL_REFERENCE, Agent::class)) {
            return;
        }

        // 检查是否有酒店数据
        if (!$this->hasReference(HotelFixtures::LUXURY_HOTEL_REFERENCE, Hotel::class)) {
            return;
        }

        $agentA = $this->getReference(AgentFixtures::AGENT_A_LEVEL_REFERENCE, Agent::class);
        $agentB = $this->hasReference(AgentFixtures::AGENT_B_LEVEL_REFERENCE, Agent::class) ?
            $this->getReference(AgentFixtures::AGENT_B_LEVEL_REFERENCE, Agent::class) : null;

        $luxuryHotel = $this->getReference(HotelFixtures::LUXURY_HOTEL_REFERENCE, Hotel::class);
        $businessHotel = $this->hasReference(HotelFixtures::BUSINESS_HOTEL_REFERENCE, Hotel::class) ?
            $this->getReference(HotelFixtures::BUSINESS_HOTEL_REFERENCE, Hotel::class) : null;

        // A级代理可以看到所有酒店的所有房型
        $mapping1 = new AgentHotelMapping();
        $mapping1->setAgent($agentA);
        $mapping1->setHotel($luxuryHotel);
        $mapping1->setRoomTypeIds([]); // 空数组表示所有房型都可见
        $manager->persist($mapping1);
        $this->addReference(self::AGENT_LUXURY_HOTEL_MAPPING_REFERENCE, $mapping1);

        if (null !== $businessHotel) {
            $mapping2 = new AgentHotelMapping();
            $mapping2->setAgent($agentA);
            $mapping2->setHotel($businessHotel);
            $mapping2->setRoomTypeIds([]);
            $manager->persist($mapping2);
            $this->addReference(self::AGENT_BUSINESS_HOTEL_MAPPING_REFERENCE, $mapping2);
        }

        // B级代理只能看到部分房型（如果存在B级代理和房型数据）
        if (null !== $agentB && null !== $businessHotel) {
            $mapping3 = new AgentHotelMapping();
            $mapping3->setAgent($agentB);
            $mapping3->setHotel($businessHotel);
            // 假设只能看到特定房型ID（需要根据实际房型fixture调整）
            $mapping3->setRoomTypeIds([1, 2]);
            $manager->persist($mapping3);
            $this->addReference(self::AGENT_B_BUSINESS_HOTEL_MAPPING_REFERENCE, $mapping3);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        $dependencies = [AgentFixtures::class];

        // 检查是否有hotel-profile-bundle的fixtures
        if (class_exists(HotelFixtures::class)) {
            $dependencies[] = HotelFixtures::class;
        }

        if (class_exists(RoomTypeFixtures::class)) {
            $dependencies[] = RoomTypeFixtures::class;
        }

        return $dependencies;
    }
}
