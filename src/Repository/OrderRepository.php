<?php

namespace Tourze\HotelAgentBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Enum\AuditStatusEnum;
use Tourze\HotelAgentBundle\Enum\OrderSourceEnum;
use Tourze\HotelAgentBundle\Enum\OrderStatusEnum;

/**
 * 订单仓库类
 *
 * @extends ServiceEntityRepository<Order>
 *
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * 保存订单实体
     */
    public function save(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除订单实体
     */
    public function remove(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 根据订单编号查找订单
     */
    public function findByOrderNo(string $orderNo): ?Order
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.orderNo = :orderNo')
            ->setParameter('orderNo', $orderNo)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * 根据代理ID查找订单
     */
    public function findByAgentId(int $agentId): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.agent = :agentId')
            ->setParameter('agentId', $agentId)
            ->orderBy('o.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据订单状态查找订单
     */
    public function findByStatus(OrderStatusEnum $status): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.status = :status')
            ->setParameter('status', $status)
            ->orderBy('o.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据审核状态查找订单
     */
    public function findByAuditStatus(AuditStatusEnum $auditStatus): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.auditStatus = :auditStatus')
            ->setParameter('auditStatus', $auditStatus)
            ->orderBy('o.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据订单来源查找订单
     */
    public function findBySource(OrderSourceEnum $source): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.source = :source')
            ->setParameter('source', $source)
            ->orderBy('o.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找复合订单
     */
    public function findComplexOrders(): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.isComplex = :isComplex')
            ->setParameter('isComplex', true)
            ->orderBy('o.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据日期范围查找订单
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.createTime >= :startDate')
            ->andWhere('o.createTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('o.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找待审核的订单
     */
    public function findOrdersRequiringAudit(): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.auditStatus = :auditStatus')
            ->setParameter('auditStatus', AuditStatusEnum::RISK_REVIEW)
            ->orderBy('o.createTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找代理最近的订单
     */
    public function findRecentOrdersByAgentId(int $agentId, int $limit = 10): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.agent = :agentId')
            ->setParameter('agentId', $agentId)
            ->orderBy('o.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找需要关闭的过期订单
     */
    public function findExpiredPendingOrders(\DateTimeInterface $expireDate): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.status = :status')
            ->andWhere('o.createTime < :expireDate')
            ->setParameter('status', OrderStatusEnum::PENDING)
            ->setParameter('expireDate', $expireDate)
            ->getQuery()
            ->getResult();
    }
}
