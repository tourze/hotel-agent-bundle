<?php

namespace Tourze\HotelAgentBundle\Controller\Admin\BillReport;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\HotelAgentBundle\Service\AgentBillService;

/**
 * 导出账单报表
 */
class ExportReportController extends AbstractController
{
    public function __construct(
        private readonly AgentBillService $agentBillService
    ) {}

    #[Route('/admin/bill-report/export', name: 'admin_bill_report_export', methods: ['POST'])]
    public function __invoke(Request $request): Response
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
        } catch (\Throwable $e) {
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
        $filename = sprintf(
            'bill_report_%s_%s.csv',
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
