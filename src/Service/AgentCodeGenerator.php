<?php

namespace Tourze\HotelAgentBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\HotelAgentBundle\Entity\Agent;

/**
 * 代理编号生成服务
 * 
 * 自动生成格式为 AGT + 8位数字的代理编号
 * 例如：AGT20250101
 */
class AgentCodeGenerator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * 生成下一个可用的代理编号
     */
    public function generateCode(): string
    {
        $prefix = 'AGT';
        
        // 获取当前年月日作为基础
        $dateBase = date('Ymd');
        
        // 查找同一天创建的最大编号
        $lastAgent = $this->entityManager->getRepository(Agent::class)
            ->createQueryBuilder('a')
            ->where('a.code LIKE :pattern')
            ->setParameter('pattern', $prefix . $dateBase . '%')
            ->orderBy('a.code', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($lastAgent) {
            // 提取最后的序号并递增
            $lastCode = $lastAgent->getCode();
            $lastNumber = (int) substr($lastCode, -2); // 取后两位
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        // 如果当天编号超过99，使用时间戳后8位
        if ($nextNumber > 99) {
            $suffix = substr((string) time(), -8);
        } else {
            $suffix = str_pad((string) $nextNumber, 2, '0', STR_PAD_LEFT);
        }

        $newCode = $prefix . $dateBase . $suffix;

        // 确保编号唯一
        $attempts = 0;
        while ($this->codeExists($newCode) && $attempts < 100) {
            $attempts++;
            $suffix = str_pad((string) ($nextNumber + $attempts), 2, '0', STR_PAD_LEFT);
            if ($nextNumber + $attempts > 99) {
                $suffix = substr((string) (time() + $attempts), -8);
            }
            $newCode = $prefix . $dateBase . $suffix;
        }

        if ($this->codeExists($newCode)) {
            // 最后的备选方案，使用微秒时间戳
            $newCode = $prefix . substr((string) (microtime(true) * 10000), -8);
        }

        return $newCode;
    }

    /**
     * 检查代理编号是否已存在
     */
    private function codeExists(string $code): bool
    {
        $agent = $this->entityManager->getRepository(Agent::class)
            ->findByCode($code);
        
        return $agent !== null;
    }

    /**
     * 验证代理编号格式
     */
    public function isValidCode(string $code): bool
    {
        return preg_match('/^AGT\d{8,10}$/', $code) === 1;
    }
}
