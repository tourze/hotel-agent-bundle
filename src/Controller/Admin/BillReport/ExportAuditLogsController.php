<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Controller\Admin\BillReport;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\HotelAgentBundle\Exception\ExportException;
use Tourze\HotelAgentBundle\Service\BillAuditService;

/**
 * 导出审核日志
 */
final class ExportAuditLogsController extends AbstractController
{
    public function __construct(
        private readonly BillAuditService $billAuditService,
    ) {
    }

    #[Route(path: '/admin/bill-report/export-audit-logs', name: 'admin_bill_report_export_audit_logs', methods: ['POST'])]
    public function __invoke(Request $request): Response
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
            if (false === $output) {
                throw new ExportException('无法创建临时文件');
            }

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
                '操作时间',
            ]);

            // 写入数据
            $fields = ['id', 'bill_id', 'agent_name', 'bill_month', 'action', 'from_status', 'to_status', 'remarks', 'operator_name', 'ip_address', 'create_time'];

            foreach ($logs as $log) {
                $csvRow = array_map(fn ($field) => $this->safeStringValue($log[$field] ?? ''), $fields);
                fputcsv($output, $csvRow);
            }

            rewind($output);
            $content = stream_get_contents($output);
            if (false === $content) {
                throw new ExportException('无法读取文件内容');
            }
            $response->setContent($content);
            fclose($output);

            return $response;
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 安全转换为字符串
     * @param mixed $value
     */
    private function safeStringValue($value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
