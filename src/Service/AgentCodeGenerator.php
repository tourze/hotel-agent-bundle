<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Service;

use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Repository\AgentRepository;

/**
 * 代理编号生成服务
 *
 * 自动生成格式为 AGT + 8位数字的代理编号
 * 例如：AGT20250101
 */
readonly class AgentCodeGenerator
{
    public function __construct(
        private AgentRepository $agentRepository,
    ) {
    }

    /**
     * 生成下一个可用的代理编号
     */
    public function generateCode(): string
    {
        $prefix = 'AGT';
        $dateBase = date('Ymd');

        $nextNumber = $this->getNextSequenceNumber($prefix, $dateBase);
        $newCode = $this->buildCodeWithSequence($prefix, $dateBase, $nextNumber);

        return $this->ensureCodeUniqueness($newCode, $prefix, $dateBase, $nextNumber);
    }

    /**
     * 获取下一个序列号
     */
    private function getNextSequenceNumber(string $prefix, string $dateBase): int
    {
        /** @var Agent|null $lastAgent */
        $lastAgent = $this->agentRepository
            ->createQueryBuilder('a')
            ->where('a.code LIKE :pattern')
            ->setParameter('pattern', $prefix . $dateBase . '%')
            ->orderBy('a.code', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (null === $lastAgent) {
            return 1;
        }

        $lastCode = $lastAgent->getCode();
        $lastNumber = (int) substr($lastCode, -2); // 取后两位

        return $lastNumber + 1;
    }

    /**
     * 构建带序列号的代理编码
     */
    private function buildCodeWithSequence(string $prefix, string $dateBase, int $nextNumber): string
    {
        if ($nextNumber > 99) {
            $suffix = substr((string) time(), -8);
        } else {
            $suffix = str_pad((string) $nextNumber, 2, '0', STR_PAD_LEFT);
        }

        return $prefix . $dateBase . $suffix;
    }

    /**
     * 确保代理编码的唯一性
     */
    private function ensureCodeUniqueness(string $baseCode, string $prefix, string $dateBase, int $nextNumber): string
    {
        $newCode = $baseCode;
        $attempts = 0;

        while ($this->codeExists($newCode) && $attempts < 100) {
            ++$attempts;
            $newCode = $this->generateAlternativeCode($prefix, $dateBase, $nextNumber, $attempts);
        }

        if ($this->codeExists($newCode)) {
            // 最后的备选方案，使用微秒时间戳
            $newCode = $prefix . substr((string) (microtime(true) * 10000), -8);
        }

        return $newCode;
    }

    /**
     * 生成替代的代理编码
     */
    private function generateAlternativeCode(string $prefix, string $dateBase, int $nextNumber, int $attempts): string
    {
        $newNumber = $nextNumber + $attempts;

        if ($newNumber > 99) {
            $suffix = substr((string) (time() + $attempts), -8);
        } else {
            $suffix = str_pad((string) $newNumber, 2, '0', STR_PAD_LEFT);
        }

        return $prefix . $dateBase . $suffix;
    }

    /**
     * 检查代理编号是否已存在
     */
    private function codeExists(string $code): bool
    {
        $agent = $this->agentRepository->findOneBy(['code' => $code]);

        return null !== $agent;
    }

    /**
     * 验证代理编号格式
     */
    public function isValidCode(string $code): bool
    {
        return 1 === preg_match('/^AGT\d{8,10}$/', $code);
    }
}
