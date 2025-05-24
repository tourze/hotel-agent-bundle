<?php

namespace Tourze\HotelAgentBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\HotelAgentBundle\Entity\AgentHotelMapping;

/**
 * 代理可见酒店映射仓库类
 *
 * @extends ServiceEntityRepository<AgentHotelMapping>
 *
 * @method AgentHotelMapping|null find($id, $lockMode = null, $lockVersion = null)
 * @method AgentHotelMapping|null findOneBy(array $criteria, array $orderBy = null)
 * @method AgentHotelMapping[]    findAll()
 * @method AgentHotelMapping[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AgentHotelMappingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AgentHotelMapping::class);
    }

    /**
     * 保存代理可见酒店映射实体
     */
    public function save(AgentHotelMapping $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除代理可见酒店映射实体
     */
    public function remove(AgentHotelMapping $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 根据代理ID查找映射
     */
    public function findByAgentId(int $agentId): array
    {
        return $this->createQueryBuilder('ahm')
            ->andWhere('ahm.agent = :agentId')
            ->setParameter('agentId', $agentId)
            ->orderBy('ahm.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据酒店ID查找映射
     */
    public function findByHotelId(int $hotelId): array
    {
        return $this->createQueryBuilder('ahm')
            ->andWhere('ahm.hotel = :hotelId')
            ->setParameter('hotelId', $hotelId)
            ->orderBy('ahm.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据代理ID和酒店ID查找映射
     */
    public function findByAgentAndHotel(int $agentId, int $hotelId): ?AgentHotelMapping
    {
        return $this->createQueryBuilder('ahm')
            ->andWhere('ahm.agent = :agentId')
            ->andWhere('ahm.hotel = :hotelId')
            ->setParameter('agentId', $agentId)
            ->setParameter('hotelId', $hotelId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * 查找包含指定房型的映射
     */
    public function findByRoomTypeId(int $roomTypeId): array
    {
        return $this->createQueryBuilder('ahm')
            ->andWhere('JSON_CONTAINS(ahm.roomTypeIds, :roomTypeId) = 1')
            ->setParameter('roomTypeId', $roomTypeId)
            ->orderBy('ahm.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
} 