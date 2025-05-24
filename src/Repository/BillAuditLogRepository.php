<?php

namespace Tourze\HotelAgentBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Entity\BillAuditLog;

/**
 * 账单审核日志Repository
 */
class BillAuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BillAuditLog::class);
    }

    /**
     * 获取账单的审核日志
     */
    public function findByAgentBill(AgentBill $agentBill): array
    {
        return $this->createQueryBuilder('bal')
            ->andWhere('bal.agentBill = :agentBill')
            ->setParameter('agentBill', $agentBill)
            ->orderBy('bal.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 获取指定时间范围内的审核日志
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('bal')
            ->andWhere('bal.createTime BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('bal.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 获取指定操作类型的日志
     */
    public function findByAction(string $action): array
    {
        return $this->createQueryBuilder('bal')
            ->andWhere('bal.action = :action')
            ->setParameter('action', $action)
            ->orderBy('bal.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 获取审核统计数据
     */
    public function getAuditStatistics(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('bal')
            ->select([
                'bal.action',
                'COUNT(bal.id) as count',
                'DATE(bal.createTime) as date'
            ])
            ->andWhere('bal.createTime BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('bal.action', 'DATE(bal.createTime)')
            ->orderBy('date', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 