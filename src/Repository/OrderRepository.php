<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Enum\AuditStatusEnum;
use Tourze\HotelAgentBundle\Enum\OrderSourceEnum;
use Tourze\HotelAgentBundle\Enum\OrderStatusEnum;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * 订单仓库类
 *
 * @extends ServiceEntityRepository<Order>
 */
#[AsRepository(entityClass: Order::class)]
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function save(Order $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Order $entity, bool $flush = true): void
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
        /** @var Order|null */
        return $this->createQueryBuilder('o')
            ->andWhere('o.orderNo = :orderNo')
            ->setParameter('orderNo', $orderNo)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 根据代理ID查找订单
     *
     * @return Order[]
     */
    public function findByAgentId(int $agentId): array
    {
        /** @var Order[] */
        return $this->createQueryBuilder('o')
            ->andWhere('o.agent = :agentId')
            ->setParameter('agentId', $agentId)
            ->orderBy('o.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据订单状态查找订单
     *
     * @return Order[]
     */
    public function findByStatus(OrderStatusEnum $status): array
    {
        /** @var Order[] */
        return $this->createQueryBuilder('o')
            ->andWhere('o.status = :status')
            ->setParameter('status', $status)
            ->orderBy('o.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据审核状态查找订单
     *
     * @return Order[]
     */
    public function findByAuditStatus(AuditStatusEnum $auditStatus): array
    {
        /** @var Order[] */
        return $this->createQueryBuilder('o')
            ->andWhere('o.auditStatus = :auditStatus')
            ->setParameter('auditStatus', $auditStatus)
            ->orderBy('o.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据订单来源查找订单
     *
     * @return Order[]
     */
    public function findBySource(OrderSourceEnum $source): array
    {
        /** @var Order[] */
        return $this->createQueryBuilder('o')
            ->andWhere('o.source = :source')
            ->setParameter('source', $source)
            ->orderBy('o.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找复合订单
     *
     * @return Order[]
     */
    public function findComplexOrders(): array
    {
        /** @var Order[] */
        return $this->createQueryBuilder('o')
            ->andWhere('o.isComplex = :isComplex')
            ->setParameter('isComplex', true)
            ->orderBy('o.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据日期范围查找订单
     *
     * @return Order[]
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        /** @var Order[] */
        return $this->createQueryBuilder('o')
            ->andWhere('o.createTime >= :startDate')
            ->andWhere('o.createTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('o.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找待审核的订单
     *
     * @return Order[]
     */
    public function findOrdersRequiringAudit(): array
    {
        /** @var Order[] */
        return $this->createQueryBuilder('o')
            ->andWhere('o.auditStatus = :auditStatus')
            ->setParameter('auditStatus', AuditStatusEnum::RISK_REVIEW)
            ->orderBy('o.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找代理最近的订单
     *
     * @return Order[]
     */
    public function findRecentOrdersByAgentId(int $agentId, int $limit = 10): array
    {
        /** @var Order[] */
        return $this->createQueryBuilder('o')
            ->andWhere('o.agent = :agentId')
            ->setParameter('agentId', $agentId)
            ->orderBy('o.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找需要关闭的过期订单
     *
     * @return Order[]
     */
    public function findExpiredPendingOrders(\DateTimeInterface $expireDate): array
    {
        /** @var Order[] */
        return $this->createQueryBuilder('o')
            ->andWhere('o.status = :status')
            ->andWhere('o.createTime < :expireDate')
            ->setParameter('status', OrderStatusEnum::PENDING)
            ->setParameter('expireDate', $expireDate)
            ->getQuery()
            ->getResult()
        ;
    }
}
