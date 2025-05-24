<?php

namespace Tourze\HotelAgentBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Entity\Payment;
use Tourze\HotelAgentBundle\Enum\PaymentStatusEnum;

/**
 * 支付记录数据仓库
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    /**
     * 根据代理账单获取支付记录
     */
    public function findByAgentBill(AgentBill $agentBill): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.agentBill = :agentBill')
            ->setParameter('agentBill', $agentBill)
            ->orderBy('p.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据状态查找支付记录
     */
    public function findByStatus(PaymentStatusEnum $status): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', $status)
            ->orderBy('p.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据支付单号查找支付记录
     */
    public function findByPaymentNo(string $paymentNo): ?Payment
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.paymentNo = :paymentNo')
            ->setParameter('paymentNo', $paymentNo)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * 获取某个时间段内的支付统计
     */
    public function getPaymentStatistics(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('p')
            ->select([
                'p.paymentMethod',
                'p.status',
                'COUNT(p.id) as payment_count',
                'SUM(p.amount) as total_amount'
            ])
            ->andWhere('p.createTime BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('p.paymentMethod', 'p.status')
            ->getQuery()
            ->getResult();
    }

    /**
     * 获取待确认的支付记录
     */
    public function findPendingPayments(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', PaymentStatusEnum::PENDING)
            ->orderBy('p.createTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 获取某代理的支付历史
     */
    public function getAgentPaymentHistory(int $agentId, int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.agentBill', 'ab')
            ->join('ab.agent', 'a')
            ->andWhere('a.id = :agentId')
            ->setParameter('agentId', $agentId)
            ->orderBy('p.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 保存支付记录
     */
    public function save(Payment $payment, bool $flush = false): void
    {
        $this->getEntityManager()->persist($payment);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除支付记录
     */
    public function remove(Payment $payment, bool $flush = false): void
    {
        $this->getEntityManager()->remove($payment);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
} 