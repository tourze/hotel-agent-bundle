<?php

namespace Tourze\HotelAgentBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Enum\AgentLevelEnum;
use Tourze\HotelAgentBundle\Enum\AgentStatusEnum;

/**
 * 代理测试数据夹具
 *
 * 创建不同等级和状态的代理账户，用于演示和测试代理管理功能
 */
class AgentFixtures extends Fixture
{
    // 引用常量定义
    public const AGENT_A_LEVEL_REFERENCE = 'agent-a-level';
    public const AGENT_B_LEVEL_REFERENCE = 'agent-b-level';
    public const AGENT_C_LEVEL_REFERENCE = 'agent-c-level';
    public const AGENT_FROZEN_REFERENCE = 'agent-frozen';
    public const AGENT_EXPIRED_REFERENCE = 'agent-expired';

    public function load(ObjectManager $manager): void
    {
        // 创建A级代理
        $agent1 = new Agent();
        $agent1->setCode('AGT2025010101')
            ->setCompanyName('优选旅游有限公司')
            ->setContactPerson('张经理')
            ->setPhone('13800138001')
            ->setEmail('zhang@youxuan.com')
            ->setLevel(AgentLevelEnum::A)
            ->setStatus(AgentStatusEnum::ACTIVE)
            ->setExpiryDate(new \DateTimeImmutable('+1 year'));
        
        $manager->persist($agent1);

        // 创建B级代理
        $agent2 = new Agent();
        $agent2->setCode('AGT2025010102')
            ->setCompanyName('快乐假期旅行社')
            ->setContactPerson('李主管')
            ->setPhone('13800138002')
            ->setEmail('li@kuaile.com')
            ->setLevel(AgentLevelEnum::B)
            ->setStatus(AgentStatusEnum::ACTIVE)
            ->setExpiryDate(new \DateTimeImmutable('+6 months'));
        
        $manager->persist($agent2);

        // 创建C级代理
        $agent3 = new Agent();
        $agent3->setCode('AGT2025010103')
            ->setCompanyName('小众旅游工作室')
            ->setContactPerson('王先生')
            ->setPhone('13800138003')
            ->setEmail('wang@xiaozhong.com')
            ->setLevel(AgentLevelEnum::C)
            ->setStatus(AgentStatusEnum::ACTIVE);
        
        $manager->persist($agent3);

        // 创建冻结状态的代理
        $agent4 = new Agent();
        $agent4->setCode('AGT2025010104')
            ->setCompanyName('已冻结旅游公司')
            ->setContactPerson('赵总')
            ->setPhone('13800138004')
            ->setEmail('zhao@dongjie.com')
            ->setLevel(AgentLevelEnum::B)
            ->setStatus(AgentStatusEnum::FROZEN);
        
        $manager->persist($agent4);

        // 创建过期的代理
        $agent5 = new Agent();
        $agent5->setCode('AGT2025010105')
            ->setCompanyName('已过期旅游服务')
            ->setContactPerson('钱总监')
            ->setPhone('13800138005')
            ->setEmail('qian@guoqi.com')
            ->setLevel(AgentLevelEnum::C)
            ->setStatus(AgentStatusEnum::EXPIRED)
            ->setExpiryDate(new \DateTimeImmutable('-1 month'));
        
        $manager->persist($agent5);

        $manager->flush();

        // 设置引用，供其他 Fixtures 使用
        $this->addReference(self::AGENT_A_LEVEL_REFERENCE, $agent1);
        $this->addReference(self::AGENT_B_LEVEL_REFERENCE, $agent2);
        $this->addReference(self::AGENT_C_LEVEL_REFERENCE, $agent3);
        $this->addReference(self::AGENT_FROZEN_REFERENCE, $agent4);
        $this->addReference(self::AGENT_EXPIRED_REFERENCE, $agent5);
    }
} 