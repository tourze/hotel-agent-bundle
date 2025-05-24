<?php

namespace Tourze\HotelAgentBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;

/**
 * 代理账单仓库类
 *
 * @extends ServiceEntityRepository<AgentBill>
 *
 * @method AgentBill|null find($id, $lockMode = null, $lockVersion = null)
 * @method AgentBill|null findOneBy(array $criteria, array $orderBy = null)
 * @method AgentBill[]    findAll()
 * @method AgentBill[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AgentBillRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AgentBill::class);
    }

    /**
     * 保存账单实体
     */
    public function save(AgentBill $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除账单实体
     */
    public function remove(AgentBill $entity, bool $flush = false): void
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
        return $this->createQueryBuilder('ab')
            ->andWhere('ab.agent = :agent')
            ->andWhere('ab.billMonth = :billMonth')
            ->setParameter('agent', $agent)
            ->setParameter('billMonth', $billMonth)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * 根据代理查找账单
     */
    public function findByAgent(Agent $agent): array
    {
        return $this->createQueryBuilder('ab')
            ->andWhere('ab.agent = :agent')
            ->setParameter('agent', $agent)
            ->orderBy('ab.billMonth', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据账单状态查找账单
     */
    public function findByStatus(BillStatusEnum $status): array
    {
        return $this->createQueryBuilder('ab')
            ->andWhere('ab.status = :status')
            ->setParameter('status', $status)
            ->orderBy('ab.billMonth', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找指定月份的所有账单
     */
    public function findByMonth(string $billMonth): array
    {
        return $this->createQueryBuilder('ab')
            ->andWhere('ab.billMonth = :billMonth')
            ->setParameter('billMonth', $billMonth)
            ->orderBy('ab.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找指定时间段内的账单
     */
    public function findByPeriod(string $startMonth, string $endMonth): array
    {
        return $this->createQueryBuilder('ab')
            ->andWhere('ab.billMonth >= :startMonth')
            ->andWhere('ab.billMonth <= :endMonth')
            ->setParameter('startMonth', $startMonth)
            ->setParameter('endMonth', $endMonth)
            ->orderBy('ab.billMonth', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找待确认的账单
     */
    public function findPendingBills(): array
    {
        return $this->createQueryBuilder('ab')
            ->andWhere('ab.status = :status')
            ->setParameter('status', BillStatusEnum::PENDING)
            ->orderBy('ab.billMonth', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找已确认但未支付的账单
     */
    public function findConfirmedUnpaidBills(): array
    {
        return $this->createQueryBuilder('ab')
            ->andWhere('ab.status = :status')
            ->setParameter('status', BillStatusEnum::CONFIRMED)
            ->orderBy('ab.billMonth', 'ASC')
            ->getQuery()
            ->getResult();
    }
} 