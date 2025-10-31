<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Controller\Admin\BillReport;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\HotelAgentBundle\Service\BillAuditService;

/**
 * 获取审核统计数据
 */
final class AuditStatsController extends AbstractController
{
    public function __construct(
        private readonly BillAuditService $billAuditService,
    ) {
    }

    #[Route(path: '/admin/bill-report/audit-stats', name: 'admin_bill_report_audit_stats', methods: ['POST'])]
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

            $statistics = $this->billAuditService->getAuditStatistics($startDate, $endDate);

            return $this->json([
                'success' => true,
                'data' => $statistics,
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
