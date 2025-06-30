<?php

namespace Tourze\HotelAgentBundle\Controller\Admin\BillReport;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\HotelAgentBundle\Service\BillAuditService;

/**
 * 导出审核日志
 */
class ExportAuditLogsController extends AbstractController
{
    public function __construct(
        private readonly BillAuditService $billAuditService
    ) {}

    #[Route(path: '/admin/bill-report/export-audit-logs', name: 'admin_bill_report_export_audit_logs', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $startDate = new \DateTime($data['start_date'] ?? 'first day of last month');
            $endDate = new \DateTime($data['end_date'] ?? 'last day of last month');

            $logs = $this->billAuditService->exportAuditLogs($startDate, $endDate);

            $filename = sprintf(
                'audit_logs_%s_%s.csv',
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
                'ID',
                '账单ID',
                '代理名称',
                '账单月份',
                '操作类型',
                '变更前状态',
                '变更后状态',
                '备注',
                '操作人',
                'IP地址',
                '操作时间'
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
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
