<?php

namespace Tourze\HotelAgentBundle\Controller\Admin;

use Brick\Math\BigDecimal;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Tourze\HotelAgentBundle\Service\AgentBillService;
use Tourze\HotelAgentBundle\Service\BillAuditService;

/**
 * 账单统计报表控制器
 */
#[Route('/admin/bill-report', name: 'admin_bill_report_')]
class BillReportController extends AbstractController
{
    public function __construct(
        private readonly AgentBillService $agentBillService,
        private readonly BillAuditService $billAuditService
    ) {}

    /**
     * 账单统计报表首页
     */
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('admin/bill_report/index.html.twig');
    }

    /**
     * 获取月度账单统计
     */
    #[Route('/monthly-stats/{billMonth}', name: 'monthly_stats')]
    public function monthlyStats(string $billMonth): JsonResponse
    {
        try {
            $statistics = $this->agentBillService->getBillStatistics($billMonth);

            $formattedStats = [];
            $totalBills = 0;
            $totalAmount = BigDecimal::zero();
            $totalCommission = BigDecimal::zero();

            foreach ($statistics as $stat) {
                $status = $stat['status'];
                $count = (int) $stat['bill_count'];
                $amount = $stat['total_amount'] ?? '0.00';
                $commission = $stat['total_commission'] ?? '0.00';

                $formattedStats[] = [
                    'status' => $status,
                    'status_label' => $status->getLabel(),
                    'count' => $count,
                    'amount' => $amount,
                    'commission' => $commission
                ];

                $totalBills += $count;
                $totalAmount = $totalAmount->plus(BigDecimal::of($amount));
                $totalCommission = $totalCommission->plus(BigDecimal::of($commission));
            }

            return $this->json([
                'success' => true,
                'data' => [
                    'bill_month' => $billMonth,
                    'total_bills' => $totalBills,
                    'total_amount' => $totalAmount->toScale(2)->__toString(),
                    'total_commission' => $totalCommission->toScale(2)->__toString(),
                    'status_breakdown' => $formattedStats
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取详细账单报表
     */
    #[Route('/detailed-report', name: 'detailed_report', methods: ['POST'])]
    public function detailedReport(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $startDate = new \DateTime($data['start_date'] ?? 'first day of last month');
            $endDate = new \DateTime($data['end_date'] ?? 'last day of last month');

            $report = $this->agentBillService->getDetailedBillReport($startDate, $endDate);

            return $this->json([
                'success' => true,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取审核统计数据
     */
    #[Route('/audit-stats', name: 'audit_stats', methods: ['POST'])]
    public function auditStats(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $startDate = new \DateTime($data['start_date'] ?? 'first day of last month');
            $endDate = new \DateTime($data['end_date'] ?? 'last day of last month');

            $statistics = $this->billAuditService->getAuditStatistics($startDate, $endDate);

            return $this->json([
                'success' => true,
                'data' => $statistics
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 导出账单报表
     */
    #[Route('/export', name: 'export', methods: ['POST'])]
    public function exportReport(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $startDate = new \DateTime($data['start_date'] ?? 'first day of last month');
            $endDate = new \DateTime($data['end_date'] ?? 'last day of last month');
            $format = $data['format'] ?? 'csv';

            $report = $this->agentBillService->getDetailedBillReport($startDate, $endDate);

            if ($format === 'csv') {
                return $this->exportToCsv($report, $startDate, $endDate);
            } elseif ($format === 'excel') {
                return $this->exportToExcel($report, $startDate, $endDate);
            }

            throw new \InvalidArgumentException('不支持的导出格式');

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 导出审核日志
     */
    #[Route('/export-audit-logs', name: 'export_audit_logs', methods: ['POST'])]
    public function exportAuditLogs(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $startDate = new \DateTime($data['start_date'] ?? 'first day of last month');
            $endDate = new \DateTime($data['end_date'] ?? 'last day of last month');

            $logs = $this->billAuditService->exportAuditLogs($startDate, $endDate);

            $filename = sprintf('audit_logs_%s_%s.csv', 
                $startDate->format('Y-m-d'), 
                $endDate->format('Y-m-d')
            );

            $response = new Response();
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

            $output = fopen('php://temp', 'w');

            // 写入BOM以支持中文
            fwrite($output, "\xEF\xBB\xBF");

            // 写入表头
            fputcsv($output, [
                'ID', '账单ID', '代理名称', '账单月份', '操作类型', 
                '变更前状态', '变更后状态', '备注', '操作人', 'IP地址', '操作时间'
            ]);

            // 写入数据
            foreach ($logs as $log) {
                fputcsv($output, [
                    $log['id'],
                    $log['bill_id'],
                    $log['agent_name'],
                    $log['bill_month'],
                    $log['action'],
                    $log['from_status'],
                    $log['to_status'],
                    $log['remarks'],
                    $log['operator_name'],
                    $log['ip_address'],
                    $log['create_time']
                ]);
            }

            rewind($output);
            $response->setContent(stream_get_contents($output));
            fclose($output);

            return $response;

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 导出为CSV格式
     */
    private function exportToCsv(array $report, \DateTimeInterface $startDate, \DateTimeInterface $endDate): Response
    {
        $filename = sprintf('bill_report_%s_%s.csv',
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );

        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        $output = fopen('php://temp', 'w');

        // 写入BOM以支持中文
        fwrite($output, "\xEF\xBB\xBF");

        // 写入汇总信息
        fputcsv($output, ['账单统计报表']);
        fputcsv($output, ['统计期间', $startDate->format('Y-m-d') . ' 至 ' . $endDate->format('Y-m-d')]);
        fputcsv($output, ['总账单数', $report['total_bills']]);
        fputcsv($output, ['总金额', $report['total_amount']]);
        fputcsv($output, ['总佣金', $report['total_commission']]);
        fputcsv($output, []);

        // 状态统计
        fputcsv($output, ['状态统计']);
        fputcsv($output, ['状态', '数量', '金额', '佣金']);
        foreach ($report['status_summary'] as $status => $data) {
            fputcsv($output, [$status, $data['count'], $data['amount'], $data['commission']]);
        }
        fputcsv($output, []);

        // 代理统计
        fputcsv($output, ['代理统计']);
        fputcsv($output, ['代理编号', '代理名称', '账单数', '金额', '佣金']);
        foreach ($report['agent_summary'] as $code => $data) {
            fputcsv($output, [$code, $data['name'], $data['count'], $data['amount'], $data['commission']]);
        }
        fputcsv($output, []);

        // 月度统计
        fputcsv($output, ['月度统计']);
        fputcsv($output, ['月份', '账单数', '金额', '佣金']);
        foreach ($report['monthly_summary'] as $month => $data) {
            fputcsv($output, [$month, $data['count'], $data['amount'], $data['commission']]);
        }

        rewind($output);
        $response->setContent(stream_get_contents($output));
        fclose($output);

        return $response;
    }

    /**
     * 导出为Excel格式（简化版，实际项目中建议使用PhpSpreadsheet）
     */
    private function exportToExcel(array $report, \DateTimeInterface $startDate, \DateTimeInterface $endDate): Response
    {
        // 这里简化为CSV格式，实际项目中应该使用PhpSpreadsheet生成真正的Excel文件
        return $this->exportToCsv($report, $startDate, $endDate);
    }
}
