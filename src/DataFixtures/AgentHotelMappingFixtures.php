<?php

namespace Tourze\HotelAgentBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentHotelMapping;
use Tourze\HotelProfileBundle\Entity\Hotel;

/**
 * 代理酒店映射测试数据夹具
 *
 * 为代理分配可见的酒店和房型权限，演示不同等级代理的权限控制逻辑
 */
class AgentHotelMappingFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // A级代理可以看到所有酒店的所有房型
        $mapping1 = new AgentHotelMapping();
        $mapping1->setAgent($this->getReference(AgentFixtures::AGENT_A_LEVEL_REFERENCE, Agent::class))
            ->setHotel($this->getReference(OrderFixtures::HOTEL_SAMPLE_REFERENCE, Hotel::class))
            ->setRoomTypeIds([]); // 空数组表示所有房型都可见

        $manager->persist($mapping1);

        $mapping2 = new AgentHotelMapping();
        $mapping2->setAgent($this->getReference(AgentFixtures::AGENT_A_LEVEL_REFERENCE, Agent::class))
            ->setHotel($this->getReference(OrderFixtures::HOTEL_BUSINESS_REFERENCE, Hotel::class))
            ->setRoomTypeIds([]);

        $manager->persist($mapping2);

        $mapping3 = new AgentHotelMapping();
        $mapping3->setAgent($this->getReference(AgentFixtures::AGENT_A_LEVEL_REFERENCE, Agent::class))
            ->setHotel($this->getReference(OrderFixtures::HOTEL_LUXURY_REFERENCE, Hotel::class))
            ->setRoomTypeIds([]);

        $manager->persist($mapping3);

        // B级代理只能看到部分酒店的部分房型
        $mapping4 = new AgentHotelMapping();
        $mapping4->setAgent($this->getReference(AgentFixtures::AGENT_B_LEVEL_REFERENCE, Agent::class))
            ->setHotel($this->getReference(OrderFixtures::HOTEL_SAMPLE_REFERENCE, Hotel::class))
            ->setRoomTypeIds([1, 2]); // 假设只能看到前两种房型
        
        $manager->persist($mapping4);

        $mapping5 = new AgentHotelMapping();
        $mapping5->setAgent($this->getReference(AgentFixtures::AGENT_B_LEVEL_REFERENCE, Agent::class))
            ->setHotel($this->getReference(OrderFixtures::HOTEL_BUSINESS_REFERENCE, Hotel::class))
            ->setRoomTypeIds([1, 3]); // 可以看到特定房型
        
        $manager->persist($mapping5);

        // C级代理只能看到少数酒店
        $mapping6 = new AgentHotelMapping();
        $mapping6->setAgent($this->getReference(AgentFixtures::AGENT_C_LEVEL_REFERENCE, Agent::class))
            ->setHotel($this->getReference(OrderFixtures::HOTEL_SAMPLE_REFERENCE, Hotel::class))
            ->setRoomTypeIds([1]); // 只能看到第一种房型
        
        $manager->persist($mapping6);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AgentFixtures::class,
            OrderFixtures::class,
        ];
    }
}
