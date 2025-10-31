<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Entity\BillAuditLog;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;
use Tourze\HotelAgentBundle\Repository\AgentBillRepository;
use Tourze\HotelAgentBundle\Repository\BillAuditLogRepository;

/**
 * 账单审核服务
 */
#[WithMonologChannel(channel: 'hotel_agent')]
readonly class BillAuditService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BillAuditLogRepository $billAuditLogRepository,
        private AgentBillRepository $agentBillRepository,
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private Security $security,
    ) {
    }

    /**
     * 记录账单状态变更
     */
    public function logStatusChange(
        AgentBill $agentBill,
        ?BillStatusEnum $fromStatus,
        BillStatusEnum $toStatus,
        ?string $remarks = null,
    ): BillAuditLog {
        $log = BillAuditLog::createStatusChangeLog(
            $agentBill,
            $fromStatus,
            $toStatus,
            $remarks,
            $this->getCurrentUserName(),
            $this->getCurrentIpAddress()
        );

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        $this->logger->info('记录账单状态变更', [
            'billId' => $agentBill->getId(),
            'fromStatus' => $fromStatus?->value,
            'toStatus' => $toStatus->value,
            'operator' => $this->getCurrentUserName(),
        ]);

        return $log;
    }

    /**
     * 记录账单重新计算
     * @param array<string, mixed> $oldData
     * @param array<string, mixed> $newData
     */
    public function logRecalculation(
        AgentBill $agentBill,
        array $oldData,
        array $newData,
        ?string $remarks = null,
    ): BillAuditLog {
        $log = BillAuditLog::createRecalculateLog(
            $agentBill,
            $oldData,
            $newData,
            $remarks,
            $this->getCurrentUserName(),
            $this->getCurrentIpAddress()
        );

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        $this->logger->info('记录账单重新计算', [
            'billId' => $agentBill->getId(),
            'operator' => $this->getCurrentUserName(),
            'changes' => [
                'old' => $oldData,
                'new' => $newData,
            ],
        ]);

        return $log;
    }

    /**
     * 记录审核操作
     */
    public function logAuditAction(
        AgentBill $agentBill,
        string $action,
        ?string $remarks = null,
    ): BillAuditLog {
        $log = BillAuditLog::createAuditLog(
            $agentBill,
            $action,
            $remarks,
            $this->getCurrentUserName(),
            $this->getCurrentIpAddress()
        );

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        $this->logger->info('记录账单审核操作', [
            'billId' => $agentBill->getId(),
            'action' => $action,
            'operator' => $this->getCurrentUserName(),
        ]);

        return $log;
    }

    /**
     * 获取账单的审核历史
     * @return array<BillAuditLog>
     */
    public function getBillAuditHistory(AgentBill $agentBill): array
    {
        return $this->billAuditLogRepository->findByAgentBill($agentBill);
    }

    /**
     * 获取审核统计数据
     * @return array<string, mixed>
     */
    public function getAuditStatistics(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->billAuditLogRepository->getAuditStatistics($startDate, $endDate);

        // Repository 已经返回了正确格式的数据，直接返回
    }

    /**
     * 批量审核账单
     * @param array<int> $billIds
     * @return array<int, array{success: bool, error?: string}>
     */
    public function batchAuditBills(array $billIds, string $action, ?string $remarks = null): array
    {
        $results = [];

        foreach ($billIds as $billId) {
            try {
                $bill = $this->agentBillRepository->find($billId);
                if (null === $bill) {
                    $results[$billId] = ['success' => false, 'error' => '账单不存在'];
                    continue;
                }

                $this->logAuditAction($bill, $action, $remarks);
                $results[$billId] = ['success' => true];
            } catch (\Throwable $e) {
                $results[$billId] = ['success' => false, 'error' => $e->getMessage()];
                $this->logger->error('批量审核账单失败', [
                    'billId' => $billId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * 验证账单审核权限
     */
    public function canAuditBill(AgentBill $agentBill, string $action): bool
    {
        // 根据账单状态和操作类型验证权限
        $currentStatus = $agentBill->getStatus();

        return match ($action) {
            '确认账单' => BillStatusEnum::PENDING === $currentStatus,
            '重新计算' => BillStatusEnum::PAID !== $currentStatus,
            '生成签章' => BillStatusEnum::CONFIRMED === $currentStatus,
            '标记支付' => BillStatusEnum::CONFIRMED === $currentStatus,
            default => false,
        };
    }

    /**
     * 获取当前用户名
     */
    private function getCurrentUserName(): ?string
    {
        $user = $this->security->getUser();

        return null !== $user ? $user->getUserIdentifier() : null;
    }

    /**
     * 获取当前IP地址
     */
    private function getCurrentIpAddress(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();

        return null !== $request ? $request->getClientIp() : null;
    }

    /**
     * 导出审核日志
     * @return array<int, array<string, mixed>>
     */
    public function exportAuditLogs(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $logs = $this->billAuditLogRepository->findByDateRange($startDate, $endDate);

        $exportData = [];
        foreach ($logs as $log) {
            $exportData[] = [
                'id' => $log->getId(),
                'bill_id' => $log->getAgentBill()->getId(),
                'agent_name' => $log->getAgentBill()->getAgent()?->getCompanyName() ?? 'N/A',
                'bill_month' => $log->getAgentBill()->getBillMonth(),
                'action' => $log->getAction(),
                'from_status' => $log->getFromStatus()?->getLabel(),
                'to_status' => $log->getToStatus()?->getLabel(),
                'remarks' => $log->getRemarks(),
                'operator_name' => $log->getOperatorName(),
                'ip_address' => $log->getIpAddress(),
                'create_time' => $log->getCreateTime()?->format('Y-m-d H:i:s'),
            ];
        }

        return $exportData;
    }
}
