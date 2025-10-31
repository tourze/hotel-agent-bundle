<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * 代理账单仓库类
 *
 * @extends ServiceEntityRepository<AgentBill>
 */
#[AsRepository(entityClass: AgentBill::class)]
class AgentBillRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AgentBill::class);
    }

    public function save(AgentBill $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AgentBill $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 根据代理和月份查找账单
     */
    public function findByAgentAndMonth(Agent $agent, string $billMonth): ?AgentBill
    {
        /** @var AgentBill|null */
        return $this->createQueryBuilder('ab')
            ->andWhere('ab.agent = :agent')
            ->andWhere('ab.billMonth = :billMonth')
            ->setParameter('agent', $agent)
            ->setParameter('billMonth', $billMonth)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 根据代理查找账单
     *
     * @return AgentBill[]
     */
    public function findByAgent(Agent $agent): array
    {
        /** @var AgentBill[] */
        return $this->createQueryBuilder('ab')
            ->andWhere('ab.agent = :agent')
            ->setParameter('agent', $agent)
            ->orderBy('ab.billMonth', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据账单状态查找账单
     *
     * @return AgentBill[]
     */
    public function findByStatus(BillStatusEnum $status): array
    {
        /** @var AgentBill[] */
        return $this->createQueryBuilder('ab')
            ->andWhere('ab.status = :status')
            ->setParameter('status', $status)
            ->orderBy('ab.billMonth', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找指定月份的所有账单
     *
     * @return AgentBill[]
     */
    public function findByMonth(string $billMonth): array
    {
        /** @var AgentBill[] */
        return $this->createQueryBuilder('ab')
            ->andWhere('ab.billMonth = :billMonth')
            ->setParameter('billMonth', $billMonth)
            ->orderBy('ab.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找指定时间段内的账单
     *
     * @return AgentBill[]
     */
    public function findByPeriod(string $startMonth, string $endMonth): array
    {
        /** @var AgentBill[] */
        return $this->createQueryBuilder('ab')
            ->andWhere('ab.billMonth >= :startMonth')
            ->andWhere('ab.billMonth <= :endMonth')
            ->setParameter('startMonth', $startMonth)
            ->setParameter('endMonth', $endMonth)
            ->orderBy('ab.billMonth', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找待确认的账单
     *
     * @return AgentBill[]
     */
    public function findPendingBills(): array
    {
        /** @var AgentBill[] */
        return $this->createQueryBuilder('ab')
            ->andWhere('ab.status = :status')
            ->setParameter('status', BillStatusEnum::PENDING)
            ->orderBy('ab.billMonth', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找已确认但未支付的账单
     *
     * @return AgentBill[]
     */
    public function findConfirmedUnpaidBills(): array
    {
        /** @var AgentBill[] */
        return $this->createQueryBuilder('ab')
            ->andWhere('ab.status = :status')
            ->setParameter('status', BillStatusEnum::CONFIRMED)
            ->orderBy('ab.billMonth', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
