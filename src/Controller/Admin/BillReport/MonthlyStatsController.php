<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Controller\Admin\BillReport;

use Brick\Math\BigDecimal;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;
use Tourze\HotelAgentBundle\Service\AgentBillService;

/**
 * 获取月度账单统计
 */
final class MonthlyStatsController extends AbstractController
{
    public function __construct(
        private readonly AgentBillService $agentBillService,
    ) {
    }

    #[Route(path: '/admin/bill-report/monthly-stats/{billMonth}', name: 'admin_bill_report_monthly_stats', methods: ['GET'])]
    public function __invoke(string $billMonth): JsonResponse
    {
        try {
            $statistics = $this->agentBillService->getBillStatistics($billMonth);

            $formattedStats = [];
            $totalBills = 0;
            $totalAmount = BigDecimal::zero();
            $totalCommission = BigDecimal::zero();

            foreach ($statistics as $stat) {
                $status = $stat['status'] ?? null;
                if (!$status instanceof BillStatusEnum) {
                    continue;
                }

                $count = isset($stat['bill_count']) && is_numeric($stat['bill_count']) ? (int) $stat['bill_count'] : 0;
                $amount = $stat['total_amount'] ?? '0.00';
                $commission = $stat['total_commission'] ?? '0.00';

                $formattedStats[] = [
                    'status' => $status->value,
                    'status_label' => $status->getLabel(),
                    'count' => $count,
                    'amount' => $amount,
                    'commission' => $commission,
                ];

                $totalBills += $count;
                $amountStr = is_scalar($amount) ? (string) $amount : '0.00';
                $commissionStr = is_scalar($commission) ? (string) $commission : '0.00';

                $totalAmount = $totalAmount->plus(BigDecimal::of($amountStr));
                $totalCommission = $totalCommission->plus(BigDecimal::of($commissionStr));
            }

            return $this->json([
                'success' => true,
                'data' => [
                    'bill_month' => $billMonth,
                    'total_bills' => $totalBills,
                    'total_amount' => $totalAmount->toScale(2)->__toString(),
                    'total_commission' => $totalCommission->toScale(2)->__toString(),
                    'status_breakdown' => $formattedStats,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
