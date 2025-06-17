<?php

namespace Tourze\HotelAgentBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Entity\BillAuditLog;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;
use Tourze\HotelAgentBundle\Repository\BillAuditLogRepository;

/**
 * 账单审核服务
 */
class BillAuditService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BillAuditLogRepository $billAuditLogRepository,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
        private readonly Security $security
    ) {}

    /**
     * 记录账单状态变更
     */
    public function logStatusChange(
        AgentBill $agentBill,
        ?BillStatusEnum $fromStatus,
        BillStatusEnum $toStatus,
        ?string $remarks = null
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
            'operator' => $this->getCurrentUserName()
        ]);

        return $log;
    }

    /**
     * 记录账单重新计算
     */
    public function logRecalculation(
        AgentBill $agentBill,
        array $oldData,
        array $newData,
        ?string $remarks = null
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
                'new' => $newData
            ]
        ]);

        return $log;
    }

    /**
     * 记录审核操作
     */
    public function logAuditAction(
        AgentBill $agentBill,
        string $action,
        ?string $remarks = null
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
            'operator' => $this->getCurrentUserName()
        ]);

        return $log;
    }

    /**
     * 获取账单的审核历史
     */
    public function getBillAuditHistory(AgentBill $agentBill): array
    {
        return $this->billAuditLogRepository->findByAgentBill($agentBill);
    }

    /**
     * 获取审核统计数据
     */
    public function getAuditStatistics(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $rawData = $this->billAuditLogRepository->getAuditStatistics($startDate, $endDate);
        
        $statistics = [
            'total_actions' => 0,
            'actions_by_type' => [],
            'daily_stats' => []
        ];

        foreach ($rawData as $row) {
            $action = $row['action'];
            $count = (int) $row['count'];
            $date = $row['date'];

            $statistics['total_actions'] += $count;
            
            if (!isset($statistics['actions_by_type'][$action])) {
                $statistics['actions_by_type'][$action] = 0;
            }
            $statistics['actions_by_type'][$action] += $count;

            if (!isset($statistics['daily_stats'][$date])) {
                $statistics['daily_stats'][$date] = [];
            }
            $statistics['daily_stats'][$date][$action] = $count;
        }

        return $statistics;
    }

    /**
     * 批量审核账单
     */
    public function batchAuditBills(array $billIds, string $action, ?string $remarks = null): array
    {
        $results = [];
        
        foreach ($billIds as $billId) {
            try {
                $bill = $this->entityManager->getRepository(AgentBill::class)->find($billId);
                if (!$bill) {
                    $results[$billId] = ['success' => false, 'error' => '账单不存在'];
                    continue;
                }

                $this->logAuditAction($bill, $action, $remarks);
                $results[$billId] = ['success' => true];
                
            } catch (\Throwable $e) {
                $results[$billId] = ['success' => false, 'error' => $e->getMessage()];
                $this->logger->error('批量审核账单失败', [
                    'billId' => $billId,
                    'error' => $e->getMessage()
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
            '确认账单' => $currentStatus === BillStatusEnum::PENDING,
            '重新计算' => $currentStatus !== BillStatusEnum::PAID,
            '生成签章' => $currentStatus === BillStatusEnum::CONFIRMED,
            '标记支付' => $currentStatus === BillStatusEnum::CONFIRMED,
            default => false
        };
    }

    /**
     * 获取当前用户名
     */
    private function getCurrentUserName(): ?string
    {
        $user = $this->security->getUser();
        return $user ? $user->getUserIdentifier() : null;
    }

    /**
     * 获取当前IP地址
     */
    private function getCurrentIpAddress(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request ? $request->getClientIp() : null;
    }

    /**
     * 导出审核日志
     */
    public function exportAuditLogs(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $logs = $this->billAuditLogRepository->findByDateRange($startDate, $endDate);
        
        $exportData = [];
        foreach ($logs as $log) {
            $exportData[] = [
                'id' => $log->getId(),
                'bill_id' => $log->getAgentBill()->getId(),
                'agent_name' => $log->getAgentBill()->getAgent()->getCompanyName(),
                'bill_month' => $log->getAgentBill()->getBillMonth(),
                'action' => $log->getAction(),
                'from_status' => $log->getFromStatus()?->getLabel(),
                'to_status' => $log->getToStatus()?->getLabel(),
                'remarks' => $log->getRemarks(),
                'operator_name' => $log->getOperatorName(),
                'ip_address' => $log->getIpAddress(),
                'create_time' => $log->getCreateTime()?->format('Y-m-d H:i:s')
            ];
        }

        return $exportData;
    }
} 