<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Controller\Admin\BillReport;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\HotelAgentBundle\Service\AgentBillService;

/**
 * 获取详细账单报表
 */
final class DetailedReportController extends AbstractController
{
    public function __construct(
        private readonly AgentBillService $agentBillService,
    ) {
    }

    #[Route(path: '/admin/bill-report/detailed-report', name: 'admin_bill_report_detailed_report', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $content = $request->getContent();
            $data = json_decode($content, true);
            if (!is_array($data)) {
                throw new \InvalidArgumentException('Invalid JSON data');
            }

            $startDateStr = is_string($data['start_date'] ?? null) ? $data['start_date'] : 'first day of last month';
            $endDateStr = is_string($data['end_date'] ?? null) ? $data['end_date'] : 'last day of last month';

            $startDate = new \DateTime($startDateStr);
            $endDate = new \DateTime($endDateStr);

            $report = $this->agentBillService->getDetailedBillReport($startDate, $endDate);

            return $this->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
