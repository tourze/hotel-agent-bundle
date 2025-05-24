<?php

namespace Tourze\HotelAgentBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Enum\AgentLevelEnum;
use Tourze\HotelAgentBundle\Enum\AgentStatusEnum;

/**
 * 代理销售账户仓库类
 *
 * @extends ServiceEntityRepository<Agent>
 *
 * @method Agent|null find($id, $lockMode = null, $lockVersion = null)
 * @method Agent|null findOneBy(array $criteria, array $orderBy = null)
 * @method Agent[]    findAll()
 * @method Agent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AgentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Agent::class);
    }

    /**
     * 保存代理实体
     */
    public function save(Agent $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除代理实体
     */
    public function remove(Agent $entity, bool $flush = false): void
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
        return $this->createQueryBuilder('a')
            ->andWhere('a.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * 根据公司名称查找代理
     */
    public function findByCompanyName(string $companyName): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.companyName LIKE :companyName')
            ->setParameter('companyName', '%' . $companyName . '%')
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据联系电话查找代理
     */
    public function findByPhone(string $phone): ?Agent
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.phone = :phone')
            ->setParameter('phone', $phone)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * 根据账户状态查找代理
     */
    public function findByStatus(AgentStatusEnum $status): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', $status)
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据等级查找代理
     */
    public function findByLevel(AgentLevelEnum $level): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.level = :level')
            ->setParameter('level', $level)
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找过期账户
     */
    public function findExpiredAgents(): array
    {
        $now = new \DateTime();
        return $this->createQueryBuilder('a')
            ->andWhere('a.expiryDate IS NOT NULL')
            ->andWhere('a.expiryDate < :now')
            ->andWhere('a.status != :expired')
            ->setParameter('now', $now)
            ->setParameter('expired', AgentStatusEnum::EXPIRED)
            ->orderBy('a.expiryDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找即将过期的账户
     */
    public function findAgentsExpiringInDays(int $days): array
    {
        $now = new \DateTime();
        $future = (new \DateTime())->modify('+' . $days . ' days');

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
            ->getResult();
    }
}
