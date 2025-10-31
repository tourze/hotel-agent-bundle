<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Entity\BillAuditLog;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;

class BillAuditLogFixtures extends Fixture implements DependentFixtureInterface
{
    public const BILL_AUDIT_LOG_1_REFERENCE = 'bill-audit-log-1';
    public const BILL_AUDIT_LOG_2_REFERENCE = 'bill-audit-log-2';
    public const BILL_AUDIT_LOG_3_REFERENCE = 'bill-audit-log-3';

    public function load(ObjectManager $manager): void
    {
        $agentBill = $this->getReference(AgentBillFixtures::AGENT_BILL_A_LEVEL_REFERENCE, AgentBill::class);

        $auditLog1 = BillAuditLog::createStatusChangeLog(
            $agentBill,
            null,
            BillStatusEnum::PENDING,
            '账单初始创建',
            '系统管理员',
            '127.0.0.1'
        );
        $auditLog1->setCreatedBy('1');
        $manager->persist($auditLog1);

        $auditLog2 = BillAuditLog::createStatusChangeLog(
            $agentBill,
            BillStatusEnum::PENDING,
            BillStatusEnum::CONFIRMED,
            '账单确认',
            '财务管理员',
            '127.0.0.1'
        );
        $auditLog2->setCreatedBy('1');
        $manager->persist($auditLog2);

        $auditLog3 = BillAuditLog::createRecalculateLog(
            $agentBill,
            ['totalAmount' => 10000, 'commissionAmount' => 800],
            ['totalAmount' => 12000, 'commissionAmount' => 960],
            '重新计算佣金',
            '系统管理员',
            '127.0.0.1'
        );
        $auditLog3->setCreatedBy('1');
        $manager->persist($auditLog3);

        $manager->flush();

        $this->addReference(self::BILL_AUDIT_LOG_1_REFERENCE, $auditLog1);
        $this->addReference(self::BILL_AUDIT_LOG_2_REFERENCE, $auditLog2);
        $this->addReference(self::BILL_AUDIT_LOG_3_REFERENCE, $auditLog3);
    }

    public function getDependencies(): array
    {
        return [
            AgentBillFixtures::class,
        ];
    }
}
