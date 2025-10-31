<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;
use Tourze\HotelAgentBundle\Enum\SettlementTypeEnum;

/**
 * 代理账单测试数据夹具
 *
 * 为代理创建不同状态的月度账单，演示账单生成、确认和支付流程
 */
#[When(env: 'test')]
class AgentBillFixtures extends Fixture implements DependentFixtureInterface
{
    // 引用常量定义
    public const AGENT_BILL_A_LEVEL_REFERENCE = 'agent-bill-a-level';
    public const AGENT_BILL_B_LEVEL_REFERENCE = 'agent-bill-b-level';
    public const AGENT_BILL_C_LEVEL_REFERENCE = 'agent-bill-c-level';

    public function load(ObjectManager $manager): void
    {
        // 为A级代理创建账单（已支付）
        $billA1 = new AgentBill();
        $billA1->setAgent($this->getReference(AgentFixtures::AGENT_A_LEVEL_REFERENCE, Agent::class));
        $billA1->setBillMonth('2024-12');
        $billA1->setOrderCount(25);
        $billA1->setTotalAmount('125000.00');
        $billA1->setCommissionRate('0.10');
        $billA1->setCommissionAmount('12500.00');
        $billA1->setSettlementType(SettlementTypeEnum::MONTHLY);
        $billA1->setStatus(BillStatusEnum::PAID);
        $billA1->setConfirmTime(new \DateTimeImmutable('2024-12-28 10:30:00'));
        $billA1->setPayTime(new \DateTimeImmutable('2024-12-30 15:45:00'));
        $billA1->setPaymentReference('PAY20241230001');
        $billA1->setRemarks('A级代理12月账单，正常结算');

        $manager->persist($billA1);

        // 为A级代理创建账单（已确认，待支付）
        $billA2 = new AgentBill();
        $billA2->setAgent($this->getReference(AgentFixtures::AGENT_A_LEVEL_REFERENCE, Agent::class));
        $billA2->setBillMonth('2025-01');
        $billA2->setOrderCount(18);
        $billA2->setTotalAmount('89000.00');
        $billA2->setCommissionRate('0.10');
        $billA2->setCommissionAmount('8900.00');
        $billA2->setSettlementType(SettlementTypeEnum::MONTHLY);
        $billA2->setStatus(BillStatusEnum::CONFIRMED);
        $billA2->setConfirmTime(new \DateTimeImmutable('2025-01-28 09:20:00'));
        $billA2->setRemarks('A级代理1月账单，已确认待支付');

        $manager->persist($billA2);

        // 为B级代理创建账单（待确认）
        $billB1 = new AgentBill();
        $billB1->setAgent($this->getReference(AgentFixtures::AGENT_B_LEVEL_REFERENCE, Agent::class));
        $billB1->setBillMonth('2024-12');
        $billB1->setOrderCount(15);
        $billB1->setTotalAmount('75000.00');
        $billB1->setCommissionRate('0.08');
        $billB1->setCommissionAmount('6000.00');
        $billB1->setSettlementType(SettlementTypeEnum::MONTHLY);
        $billB1->setStatus(BillStatusEnum::PENDING);
        $billB1->setRemarks('B级代理12月账单，待确认');

        $manager->persist($billB1);

        // 为B级代理创建账单（已支付）
        $billB2 = new AgentBill();
        $billB2->setAgent($this->getReference(AgentFixtures::AGENT_B_LEVEL_REFERENCE, Agent::class));
        $billB2->setBillMonth('2024-11');
        $billB2->setOrderCount(22);
        $billB2->setTotalAmount('88000.00');
        $billB2->setCommissionRate('0.08');
        $billB2->setCommissionAmount('7040.00');
        $billB2->setSettlementType(SettlementTypeEnum::MONTHLY);
        $billB2->setStatus(BillStatusEnum::PAID);
        $billB2->setConfirmTime(new \DateTimeImmutable('2024-11-28 14:15:00'));
        $billB2->setPayTime(new \DateTimeImmutable('2024-12-02 11:30:00'));
        $billB2->setPaymentReference('PAY20241202003');
        $billB2->setRemarks('B级代理11月账单，已完成结算');

        $manager->persist($billB2);

        // 为C级代理创建账单（已支付）
        $billC1 = new AgentBill();
        $billC1->setAgent($this->getReference(AgentFixtures::AGENT_C_LEVEL_REFERENCE, Agent::class));
        $billC1->setBillMonth('2024-12');
        $billC1->setOrderCount(8);
        $billC1->setTotalAmount('24000.00');
        $billC1->setCommissionRate('0.05');
        $billC1->setCommissionAmount('1200.00');
        $billC1->setSettlementType(SettlementTypeEnum::MONTHLY);
        $billC1->setStatus(BillStatusEnum::PAID);
        $billC1->setConfirmTime(new \DateTimeImmutable('2024-12-29 16:00:00'));
        $billC1->setPayTime(new \DateTimeImmutable('2024-12-31 10:20:00'));
        $billC1->setPaymentReference('PAY20241231005');
        $billC1->setRemarks('C级代理12月账单，小额结算');

        $manager->persist($billC1);

        // 为C级代理创建账单（待确认）
        $billC2 = new AgentBill();
        $billC2->setAgent($this->getReference(AgentFixtures::AGENT_C_LEVEL_REFERENCE, Agent::class));
        $billC2->setBillMonth('2025-01');
        $billC2->setOrderCount(5);
        $billC2->setTotalAmount('15000.00');
        $billC2->setCommissionRate('0.05');
        $billC2->setCommissionAmount('750.00');
        $billC2->setSettlementType(SettlementTypeEnum::MONTHLY);
        $billC2->setStatus(BillStatusEnum::PENDING);
        $billC2->setRemarks('C级代理1月账单，订单较少');

        $manager->persist($billC2);

        $manager->flush();

        // 设置引用，供其他 Fixtures 使用
        $this->addReference(self::AGENT_BILL_A_LEVEL_REFERENCE, $billA1);
        $this->addReference(self::AGENT_BILL_B_LEVEL_REFERENCE, $billB1);
        $this->addReference(self::AGENT_BILL_C_LEVEL_REFERENCE, $billC1);
    }

    public function getDependencies(): array
    {
        return [
            AgentFixtures::class,
            OrderFixtures::class,
        ];
    }
}
