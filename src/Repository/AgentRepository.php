<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Enum\AgentLevelEnum;
use Tourze\HotelAgentBundle\Enum\AgentStatusEnum;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * 代理销售账户仓库类
 *
 * @extends ServiceEntityRepository<Agent>
 */
#[AsRepository(entityClass: Agent::class)]
class AgentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Agent::class);
    }

    public function save(Agent $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Agent $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 根据代理编号查找代理
     */
    public function findByCode(string $code): ?Agent
    {
        /** @var Agent|null */
        return $this->createQueryBuilder('a')
            ->andWhere('a.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 根据公司名称查找代理
     *
     * @return Agent[]
     */
    public function findByCompanyName(string $companyName): array
    {
        /** @var Agent[] */
        return $this->createQueryBuilder('a')
            ->andWhere('a.companyName LIKE :companyName')
            ->setParameter('companyName', '%' . $companyName . '%')
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据联系电话查找代理
     */
    public function findByPhone(string $phone): ?Agent
    {
        /** @var Agent|null */
        return $this->createQueryBuilder('a')
            ->andWhere('a.phone = :phone')
            ->setParameter('phone', $phone)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 根据账户状态查找代理
     *
     * @return Agent[]
     */
    public function findByStatus(AgentStatusEnum $status): array
    {
        /** @var Agent[] */
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', $status)
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据等级查找代理
     *
     * @return Agent[]
     */
    public function findByLevel(AgentLevelEnum $level): array
    {
        /** @var Agent[] */
        return $this->createQueryBuilder('a')
            ->andWhere('a.level = :level')
            ->setParameter('level', $level)
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找过期账户
     *
     * @return Agent[]
     */
    public function findExpiredAgents(): array
    {
        $now = new \DateTime();

        /** @var Agent[] */
        return $this->createQueryBuilder('a')
            ->andWhere('a.expiryDate IS NOT NULL')
            ->andWhere('a.expiryDate < :now')
            ->andWhere('a.status != :expired')
            ->setParameter('now', $now)
            ->setParameter('expired', AgentStatusEnum::EXPIRED)
            ->orderBy('a.expiryDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找即将过期的账户
     *
     * @return Agent[]
     */
    public function findAgentsExpiringInDays(int $days): array
    {
        $now = new \DateTime();
        $future = new \DateTime();
        $future->modify('+' . $days . ' days');

        /** @var Agent[] */
        return $this->createQueryBuilder('a')
            ->andWhere('a.expiryDate IS NOT NULL')
            ->andWhere('a.expiryDate > :now')
            ->andWhere('a.expiryDate <= :future')
            ->andWhere('a.status = :active')
            ->setParameter('now', $now)
            ->setParameter('future', $future)
            ->setParameter('active', AgentStatusEnum::ACTIVE)
            ->orderBy('a.expiryDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
