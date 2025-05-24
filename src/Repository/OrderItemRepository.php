<?php

namespace Tourze\HotelAgentBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\HotelAgentBundle\Entity\OrderItem;
use Tourze\HotelAgentBundle\Enum\OrderItemStatusEnum;

/**
 * 订单明细仓库类
 *
 * @extends ServiceEntityRepository<OrderItem>
 *
 * @method OrderItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderItem[]    findAll()
 * @method OrderItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderItem::class);
    }

    /**
     * 查找指定订单的所有订单明细
     */
    public function findByOrderId(int $orderId): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.order = :orderId')
            ->setParameter('orderId', $orderId)
            ->orderBy('oi.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找指定酒店的订单明细
     */
    public function findByHotelId(int $hotelId): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.hotel = :hotelId')
            ->setParameter('hotelId', $hotelId)
            ->orderBy('oi.checkInDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找指定房型的订单明细
     */
    public function findByRoomTypeId(int $roomTypeId): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.roomType = :roomTypeId')
            ->setParameter('roomTypeId', $roomTypeId)
            ->orderBy('oi.checkInDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找指定合同的订单明细
     */
    public function findByContractId(int $contractId): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.contract = :contractId')
            ->setParameter('contractId', $contractId)
            ->orderBy('oi.checkInDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据日库存ID查找订单明细
     */
    public function findByDailyInventoryId(int $dailyInventoryId): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.dailyInventory = :dailyInventoryId')
            ->setParameter('dailyInventoryId', $dailyInventoryId)
            ->orderBy('oi.checkInDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据状态查找订单明细
     */
    public function findByStatus(OrderItemStatusEnum $status): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.status = :status')
            ->setParameter('status', $status)
            ->orderBy('oi.checkInDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据入住日期范围查找订单明细
     */
    public function findByCheckInDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.checkInDate >= :startDate')
            ->andWhere('oi.checkInDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('oi.checkInDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据退房日期范围查找订单明细
     */
    public function findByCheckOutDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.checkOutDate >= :startDate')
            ->andWhere('oi.checkOutDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('oi.checkOutDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找日期范围内有重叠的订单明细
     */
    public function findOverlappingDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('(oi.checkInDate <= :endDate AND oi.checkOutDate >= :startDate)')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('oi.checkInDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找指定酒店和日期范围的订单明细
     */
    public function findByHotelAndDateRange(int $hotelId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.hotel = :hotelId')
            ->andWhere('(oi.checkInDate <= :endDate AND oi.checkOutDate >= :startDate)')
            ->setParameter('hotelId', $hotelId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('oi.checkInDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找指定房型和日期范围的订单明细
     */
    public function findByRoomTypeAndDateRange(int $roomTypeId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.roomType = :roomTypeId')
            ->andWhere('(oi.checkInDate <= :endDate AND oi.checkOutDate >= :startDate)')
            ->setParameter('roomTypeId', $roomTypeId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('oi.checkInDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据房型ID和日期查找订单明细
     */
    public function findByRoomTypeAndDate(int $roomTypeId, \DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.roomType = :roomTypeId')
            ->andWhere('oi.checkInDate <= :date')
            ->andWhere('oi.checkOutDate > :date')
            ->setParameter('roomTypeId', $roomTypeId)
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找特定价格范围的订单明细
     */
    public function findByUnitPriceRange(string $minPrice, string $maxPrice): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.unitPrice >= :minPrice')
            ->andWhere('oi.unitPrice <= :maxPrice')
            ->setParameter('minPrice', $minPrice)
            ->setParameter('maxPrice', $maxPrice)
            ->orderBy('oi.unitPrice', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 计算特定日期已预订房间数
     */
    public function countBookingsByDate(\DateTimeInterface $date): int
    {
        return $this->createQueryBuilder('oi')
            ->select('COUNT(oi)')
            ->andWhere('oi.checkInDate <= :date')
            ->andWhere('oi.checkOutDate > :date')
            ->andWhere('oi.status != :canceledStatus')
            ->setParameter('date', $date)
            ->setParameter('canceledStatus', OrderItemStatusEnum::CANCELED)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    /**
     * 计算特定房型在特定日期的已预订房间数
     */
    public function countBookingsByRoomTypeAndDate(int $roomTypeId, \DateTimeInterface $date): int
    {
        return $this->createQueryBuilder('oi')
            ->select('COUNT(oi)')
            ->andWhere('oi.roomType = :roomTypeId')
            ->andWhere('oi.checkInDate <= :date')
            ->andWhere('oi.checkOutDate > :date')
            ->andWhere('oi.status != :canceledStatus')
            ->setParameter('roomTypeId', $roomTypeId)
            ->setParameter('date', $date)
            ->setParameter('canceledStatus', OrderItemStatusEnum::CANCELED)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    /**
     * 计算特定酒店在特定日期的已预订房间数
     */
    public function countBookingsByHotelAndDate(int $hotelId, \DateTimeInterface $date): int
    {
        return $this->createQueryBuilder('oi')
            ->select('COUNT(oi)')
            ->andWhere('oi.hotel = :hotelId')
            ->andWhere('oi.checkInDate <= :date')
            ->andWhere('oi.checkOutDate > :date')
            ->andWhere('oi.status != :canceledStatus')
            ->setParameter('hotelId', $hotelId)
            ->setParameter('date', $date)
            ->setParameter('canceledStatus', OrderItemStatusEnum::CANCELED)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    /**
     * 查找指定合同和日期范围的订单明细
     */
    public function findByContractAndDateRange(int $contractId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.contract = :contractId')
            ->andWhere('(oi.checkInDate <= :endDate AND oi.checkOutDate >= :startDate)')
            ->setParameter('contractId', $contractId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('oi.checkInDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 统计指定日期范围内的订单明细数量
     */
    public function countByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): int
    {
        return $this->createQueryBuilder('oi')
            ->select('COUNT(oi.id)')
            ->andWhere('(oi.checkInDate <= :endDate AND oi.checkOutDate >= :startDate)')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * 查找需要分配库存的订单明细（dailyInventory为空）
     */
    public function findPendingInventoryAllocation(): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.dailyInventory IS NULL')
            ->andWhere('oi.status = :pendingStatus')
            ->setParameter('pendingStatus', OrderItemStatusEnum::PENDING)
            ->orderBy('oi.createTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据最后修改人查找订单明细
     */
    public function findByLastModifiedBy(int $userId): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.lastModifiedBy = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('oi.updateTime', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
