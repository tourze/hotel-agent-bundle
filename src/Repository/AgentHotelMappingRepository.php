<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\HotelAgentBundle\Entity\AgentHotelMapping;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * 代理可见酒店映射仓库类
 *
 * @extends ServiceEntityRepository<AgentHotelMapping>
 */
#[AsRepository(entityClass: AgentHotelMapping::class)]
class AgentHotelMappingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AgentHotelMapping::class);
    }

    public function save(AgentHotelMapping $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AgentHotelMapping $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 根据代理ID查找映射
     *
     * @return AgentHotelMapping[]
     */
    public function findByAgentId(int $agentId): array
    {
        /** @var AgentHotelMapping[] */
        return $this->createQueryBuilder('ahm')
            ->andWhere('ahm.agent = :agentId')
            ->setParameter('agentId', $agentId)
            ->orderBy('ahm.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据酒店ID查找映射
     *
     * @return AgentHotelMapping[]
     */
    public function findByHotelId(int $hotelId): array
    {
        /** @var AgentHotelMapping[] */
        return $this->createQueryBuilder('ahm')
            ->andWhere('ahm.hotel = :hotelId')
            ->setParameter('hotelId', $hotelId)
            ->orderBy('ahm.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据代理ID和酒店ID查找映射
     */
    public function findByAgentAndHotel(int $agentId, int $hotelId): ?AgentHotelMapping
    {
        /** @var AgentHotelMapping|null */
        return $this->createQueryBuilder('ahm')
            ->andWhere('ahm.agent = :agentId')
            ->andWhere('ahm.hotel = :hotelId')
            ->setParameter('agentId', $agentId)
            ->setParameter('hotelId', $hotelId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 查找包含指定房型的映射
     *
     * @return AgentHotelMapping[]
     */
    public function findByRoomTypeId(int $roomTypeId): array
    {
        $allMappings = $this->findAll();
        $result = [];

        foreach ($allMappings as $mapping) {
            if (in_array($roomTypeId, $mapping->getRoomTypeIds(), true)) {
                $result[] = $mapping;
            }
        }

        return $result;
    }
}
