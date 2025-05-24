<?php

namespace Tourze\HotelAgentBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;
use Tourze\HotelAgentBundle\Enum\OrderStatusEnum;
use Tourze\HotelAgentBundle\Enum\SettlementTypeEnum;
use Tourze\HotelAgentBundle\Repository\AgentBillRepository;
use Tourze\HotelAgentBundle\Repository\OrderRepository;

/**
 * 代理账单服务
 */
class AgentBillService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AgentBillRepository $agentBillRepository,
        private readonly OrderRepository $orderRepository,
        private readonly LoggerInterface $logger,
        private readonly ?BillAuditService $billAuditService = null
    ) {}

    /**
     * 自动生成指定月份的代理账单
     */
    public function generateMonthlyBills(string $billMonth, bool $force = false): array
    {
        $this->logger->info('开始生成代理账单', ['billMonth' => $billMonth]);
        
        $generatedBills = [];
        $startDate = new \DateTime($billMonth . '-01 00:00:00');
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');
        $endDate->setTime(23, 59, 59);

        // 获取所有活跃代理
        $agents = $this->entityManager->getRepository(Agent::class)
            ->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();

        foreach ($agents as $agent) {
            // 检查是否已存在该月账单
            $existingBill = $this->agentBillRepository->findOneBy([
                'agent' => $agent,
                'billMonth' => $billMonth
            ]);

            if ($existingBill && !$force) {
                $this->logger->warning('代理账单已存在', [
                    'agentId' => $agent->getId(),
                    'billMonth' => $billMonth
                ]);
                continue;
            }

            if ($existingBill && $force) {
                // 强制重新生成时，删除旧账单
                $this->entityManager->remove($existingBill);
                $this->billAuditService?->logAuditAction($existingBill, '强制重新生成', '删除旧账单并重新生成');
            }

            $bill = $this->generateAgentBill($agent, $billMonth, $startDate, $endDate);
            if ($bill) {
                $generatedBills[] = $bill;
                $this->billAuditService?->logAuditAction($bill, '自动生成账单', "系统自动生成{$billMonth}月结账单");
            }
        }

        $this->entityManager->flush();

        $this->logger->info('代理账单生成完成', [
            'billMonth' => $billMonth,
            'generatedCount' => count($generatedBills)
        ]);

        return $generatedBills;
    }

    /**
     * 为单个代理生成账单
     */
    public function generateAgentBill(
        Agent $agent,
        string $billMonth,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): ?AgentBill {
        // 查询该代理在指定时间范围内的已确认订单
        $orders = $this->orderRepository->createQueryBuilder('o')
            ->andWhere('o.agent = :agent')
            ->andWhere('o.status = :status')
            ->andWhere('o.createTime BETWEEN :startDate AND :endDate')
            ->setParameter('agent', $agent)
            ->setParameter('status', OrderStatusEnum::CONFIRMED)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();

        if (empty($orders)) {
            $this->logger->info('代理无有效订单，跳过账单生成', [
                'agentId' => $agent->getId(),
                'billMonth' => $billMonth
            ]);
            return null;
        }

        // 计算账单数据
        $billData = $this->calculateBillData($orders);

        // 创建账单
        $bill = new AgentBill();
        $bill->setAgent($agent)
            ->setBillMonth($billMonth)
            ->setOrderCount($billData['orderCount'])
            ->setTotalAmount($billData['totalAmount'])
            ->setCommissionRate($agent->getCommissionRate())
            ->setCommissionAmount($billData['commissionAmount'])
            ->setSettlementType(SettlementTypeEnum::MONTHLY)
            ->setStatus(BillStatusEnum::PENDING);

        $this->entityManager->persist($bill);

        $this->logger->info('生成代理账单', [
            'agentId' => $agent->getId(),
            'billMonth' => $billMonth,
            'orderCount' => $billData['orderCount'],
            'totalAmount' => $billData['totalAmount'],
            'commissionAmount' => $billData['commissionAmount']
        ]);

        return $bill;
    }

    /**
     * 计算账单数据
     */
    private function calculateBillData(array $orders): array
    {
        $orderCount = count($orders);
        $totalAmount = '0.00';
        $totalProfit = '0.00';

        foreach ($orders as $order) {
            $totalAmount = bcadd($totalAmount, $order->getTotalAmount(), 2);
            
            // 计算利润总额
            foreach ($order->getItems() as $item) {
                $itemProfit = bcsub($item->getAmount(), $item->getCostPrice(), 2);
                $totalProfit = bcadd($totalProfit, $itemProfit, 2);
            }
        }

        // 佣金 = 利润 * 佣金比例
        $agent = $orders[0]->getAgent();
        $commissionRate = $agent->getCommissionRate();
        $commissionAmount = bcmul($totalProfit, bcdiv($commissionRate, '100', 4), 2);

        return [
            'orderCount' => $orderCount,
            'totalAmount' => $totalAmount,
            'totalProfit' => $totalProfit,
            'commissionAmount' => $commissionAmount
        ];
    }

    /**
     * 确认账单
     */
    public function confirmBill(AgentBill $bill, ?string $remarks = null): bool
    {
        $oldStatus = $bill->getStatus();
        
        if ($oldStatus !== BillStatusEnum::PENDING) {
            $this->logger->warning('账单状态不正确，无法确认', [
                'billId' => $bill->getId(),
                'currentStatus' => $oldStatus->value
            ]);
            return false;
        }

        $bill->confirm();
        $this->entityManager->flush();

        // 记录审核日志
        $this->billAuditService?->logStatusChange($bill, $oldStatus, BillStatusEnum::CONFIRMED, $remarks);

        $this->logger->info('账单已确认', ['billId' => $bill->getId()]);
        return true;
    }

    /**
     * 重新计算账单
     */
    public function recalculateBill(AgentBill $bill, ?string $remarks = null): AgentBill
    {
        if ($bill->getStatus() === BillStatusEnum::PAID) {
            throw new \RuntimeException('已支付的账单不能重新计算');
        }

        // 保存旧数据用于审核日志
        $oldData = [
            'orderCount' => $bill->getOrderCount(),
            'totalAmount' => $bill->getTotalAmount(),
            'commissionAmount' => $bill->getCommissionAmount(),
            'commissionRate' => $bill->getCommissionRate()
        ];

        $startDate = new \DateTime($bill->getBillMonth() . '-01 00:00:00');
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');
        $endDate->setTime(23, 59, 59);

        // 重新查询订单
        $orders = $this->orderRepository->createQueryBuilder('o')
            ->andWhere('o.agent = :agent')
            ->andWhere('o.status = :status')
            ->andWhere('o.createTime BETWEEN :startDate AND :endDate')
            ->setParameter('agent', $bill->getAgent())
            ->setParameter('status', OrderStatusEnum::CONFIRMED)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();

        $billData = $this->calculateBillData($orders);

        $bill->setOrderCount($billData['orderCount'])
            ->setTotalAmount($billData['totalAmount'])
            ->setCommissionAmount($billData['commissionAmount'])
            ->setCommissionRate($bill->getAgent()->getCommissionRate());

        $this->entityManager->flush();

        // 记录重新计算日志
        $newData = [
            'orderCount' => $bill->getOrderCount(),
            'totalAmount' => $bill->getTotalAmount(),
            'commissionAmount' => $bill->getCommissionAmount(),
            'commissionRate' => $bill->getCommissionRate()
        ];

        $this->billAuditService?->logRecalculation($bill, $oldData, $newData, $remarks);

        $this->logger->info('账单重新计算完成', ['billId' => $bill->getId()]);
        return $bill;
    }

    /**
     * 标记账单为已支付
     */
    public function markBillAsPaid(AgentBill $bill, ?string $paymentReference = null, ?string $remarks = null): bool
    {
        $oldStatus = $bill->getStatus();
        
        if ($oldStatus !== BillStatusEnum::CONFIRMED) {
            $this->logger->warning('只有已确认的账单才能标记为已支付', [
                'billId' => $bill->getId(),
                'currentStatus' => $oldStatus->value
            ]);
            return false;
        }

        $bill->markAsPaid($paymentReference);
        $this->entityManager->flush();

        // 记录审核日志
        $this->billAuditService?->logStatusChange($bill, $oldStatus, BillStatusEnum::PAID, $remarks);

        $this->logger->info('账单已标记为已支付', ['billId' => $bill->getId()]);
        return true;
    }

    /**
     * 获取代理账单列表
     */
    public function getAgentBills(
        ?Agent $agent = null,
        ?BillStatusEnum $status = null,
        ?string $billMonth = null,
        int $page = 1,
        int $limit = 20
    ): array {
        $qb = $this->agentBillRepository->createQueryBuilder('ab')
            ->leftJoin('ab.agent', 'a')
            ->addSelect('a');

        if ($agent) {
            $qb->andWhere('ab.agent = :agent')
               ->setParameter('agent', $agent);
        }

        if ($status) {
            $qb->andWhere('ab.status = :status')
               ->setParameter('status', $status);
        }

        if ($billMonth) {
            $qb->andWhere('ab.billMonth = :billMonth')
               ->setParameter('billMonth', $billMonth);
        }

        $qb->orderBy('ab.createTime', 'DESC')
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * 获取账单统计数据
     */
    public function getBillStatistics(string $billMonth): array
    {
        $qb = $this->agentBillRepository->createQueryBuilder('ab')
            ->select([
                'ab.status',
                'COUNT(ab.id) as bill_count',
                'SUM(ab.totalAmount) as total_amount',
                'SUM(ab.commissionAmount) as total_commission'
            ])
            ->andWhere('ab.billMonth = :billMonth')
            ->setParameter('billMonth', $billMonth)
            ->groupBy('ab.status');

        return $qb->getQuery()->getResult();
    }

    /**
     * 获取账单详细统计报表
     */
    public function getDetailedBillReport(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $qb = $this->agentBillRepository->createQueryBuilder('ab')
            ->leftJoin('ab.agent', 'a')
            ->addSelect('a')
            ->andWhere('ab.createTime BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('ab.createTime', 'DESC');

        $bills = $qb->getQuery()->getResult();

        $report = [
            'total_bills' => count($bills),
            'total_amount' => '0.00',
            'total_commission' => '0.00',
            'status_summary' => [],
            'agent_summary' => [],
            'monthly_summary' => []
        ];

        foreach ($bills as $bill) {
            $report['total_amount'] = bcadd($report['total_amount'], $bill->getTotalAmount(), 2);
            $report['total_commission'] = bcadd($report['total_commission'], $bill->getCommissionAmount(), 2);

            // 状态统计
            $status = $bill->getStatus()->value;
            if (!isset($report['status_summary'][$status])) {
                $report['status_summary'][$status] = ['count' => 0, 'amount' => '0.00', 'commission' => '0.00'];
            }
            $report['status_summary'][$status]['count']++;
            $report['status_summary'][$status]['amount'] = bcadd($report['status_summary'][$status]['amount'], $bill->getTotalAmount(), 2);
            $report['status_summary'][$status]['commission'] = bcadd($report['status_summary'][$status]['commission'], $bill->getCommissionAmount(), 2);

            // 代理统计
            $agentCode = $bill->getAgent()->getCode();
            if (!isset($report['agent_summary'][$agentCode])) {
                $report['agent_summary'][$agentCode] = [
                    'name' => $bill->getAgent()->getCompanyName(),
                    'count' => 0,
                    'amount' => '0.00',
                    'commission' => '0.00'
                ];
            }
            $report['agent_summary'][$agentCode]['count']++;
            $report['agent_summary'][$agentCode]['amount'] = bcadd($report['agent_summary'][$agentCode]['amount'], $bill->getTotalAmount(), 2);
            $report['agent_summary'][$agentCode]['commission'] = bcadd($report['agent_summary'][$agentCode]['commission'], $bill->getCommissionAmount(), 2);

            // 月度统计
            $month = $bill->getBillMonth();
            if (!isset($report['monthly_summary'][$month])) {
                $report['monthly_summary'][$month] = ['count' => 0, 'amount' => '0.00', 'commission' => '0.00'];
            }
            $report['monthly_summary'][$month]['count']++;
            $report['monthly_summary'][$month]['amount'] = bcadd($report['monthly_summary'][$month]['amount'], $bill->getTotalAmount(), 2);
            $report['monthly_summary'][$month]['commission'] = bcadd($report['monthly_summary'][$month]['commission'], $bill->getCommissionAmount(), 2);
        }

        return $report;
    }
} 