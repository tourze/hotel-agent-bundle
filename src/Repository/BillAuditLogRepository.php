<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Entity\BillAuditLog;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * 账单审核日志Repository
 *
 * @extends ServiceEntityRepository<BillAuditLog>
 */
#[AsRepository(entityClass: BillAuditLog::class)]
class BillAuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BillAuditLog::class);
    }

    public function save(BillAuditLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(BillAuditLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 获取账单的审核日志
     *
     * @return BillAuditLog[]
     */
    public function findByAgentBill(AgentBill $agentBill): array
    {
        /** @var BillAuditLog[] */
        return $this->createQueryBuilder('bal')
            ->andWhere('bal.agentBill = :agentBill')
            ->setParameter('agentBill', $agentBill)
            ->orderBy('bal.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 获取指定时间范围内的审核日志
     *
     * @return BillAuditLog[]
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        /** @var BillAuditLog[] */
        return $this->createQueryBuilder('bal')
            ->andWhere('bal.createTime BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('bal.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 获取指定操作类型的日志
     *
     * @return BillAuditLog[]
     */
    public function findByAction(string $action): array
    {
        /** @var BillAuditLog[] */
        return $this->createQueryBuilder('bal')
            ->andWhere('bal.action = :action')
            ->setParameter('action', $action)
            ->orderBy('bal.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 获取审核统计数据
     *
     * @return array{
     *     actions_by_type: list<array{action: string, count: int}>,
     *     daily_stats: list<mixed>,
     *     total_actions: int
     * }
     */
    public function getAuditStatistics(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        // 为了兼容不同数据库，使用纯 DQL 查询而不依赖特定数据库函数
        $qb = $this->createQueryBuilder('bal')
            ->select([
                'bal.action',
                'COUNT(bal.id) as count',
            ])
            ->andWhere('bal.createTime BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('bal.action')
            ->orderBy('bal.createTime', 'DESC')
        ;

        /** @var list<array{action: string, count: int|string}> $rawResult */
        $rawResult = $qb->getQuery()->getResult();

        // 确保返回类型精确匹配注解
        $actionsByType = array_map(
            static fn (array $row): array => [
                'action' => $row['action'],
                'count' => (int) $row['count'],
            ],
            $rawResult
        );

        // 简化的总计查询
        $totalCount = $this->createQueryBuilder('bal2')
            ->select('COUNT(bal2.id) as total')
            ->andWhere('bal2.createTime BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return [
            'actions_by_type' => $actionsByType,
            'daily_stats' => [], // 简化处理，不提供每日统计
            'total_actions' => (int) $totalCount,
        ];
    }
}
