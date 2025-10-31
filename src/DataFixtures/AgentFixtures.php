<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Enum\AgentLevelEnum;
use Tourze\HotelAgentBundle\Enum\AgentStatusEnum;

/**
 * 代理测试数据夹具
 *
 * 创建不同等级和状态的代理账户，用于演示和测试代理管理功能
 */
#[When(env: 'test')]
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
        $agent1->setCode('AGT2025010101');
        $agent1->setCompanyName('优选旅游有限公司');
        $agent1->setContactPerson('张经理');
        $agent1->setPhone('13800138001');
        $agent1->setEmail('zhang@youxuan.com');
        $agent1->setLevel(AgentLevelEnum::A);
        $agent1->setStatus(AgentStatusEnum::ACTIVE);
        $agent1->setExpiryDate(new \DateTimeImmutable('+1 year'));

        $manager->persist($agent1);

        // 创建B级代理
        $agent2 = new Agent();
        $agent2->setCode('AGT2025010102');
        $agent2->setCompanyName('快乐假期旅行社');
        $agent2->setContactPerson('李主管');
        $agent2->setPhone('13800138002');
        $agent2->setEmail('li@kuaile.com');
        $agent2->setLevel(AgentLevelEnum::B);
        $agent2->setStatus(AgentStatusEnum::ACTIVE);
        $agent2->setExpiryDate(new \DateTimeImmutable('+6 months'));

        $manager->persist($agent2);

        // 创建C级代理
        $agent3 = new Agent();
        $agent3->setCode('AGT2025010103');
        $agent3->setCompanyName('小众旅游工作室');
        $agent3->setContactPerson('王先生');
        $agent3->setPhone('13800138003');
        $agent3->setEmail('wang@xiaozhong.com');
        $agent3->setLevel(AgentLevelEnum::C);
        $agent3->setStatus(AgentStatusEnum::ACTIVE);

        $manager->persist($agent3);

        // 创建冻结状态的代理
        $agent4 = new Agent();
        $agent4->setCode('AGT2025010104');
        $agent4->setCompanyName('已冻结旅游公司');
        $agent4->setContactPerson('赵总');
        $agent4->setPhone('13800138004');
        $agent4->setEmail('zhao@dongjie.com');
        $agent4->setLevel(AgentLevelEnum::B);
        $agent4->setStatus(AgentStatusEnum::FROZEN);

        $manager->persist($agent4);

        // 创建过期的代理
        $agent5 = new Agent();
        $agent5->setCode('AGT2025010105');
        $agent5->setCompanyName('已过期旅游服务');
        $agent5->setContactPerson('钱总监');
        $agent5->setPhone('13800138005');
        $agent5->setEmail('qian@guoqi.com');
        $agent5->setLevel(AgentLevelEnum::C);
        $agent5->setStatus(AgentStatusEnum::EXPIRED);
        $agent5->setExpiryDate(new \DateTimeImmutable('-1 month'));

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
