<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Controller\Admin\BillReport;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\HotelAgentBundle\Exception\ExportException;
use Tourze\HotelAgentBundle\Service\AgentBillService;

/**
 * 导出账单报表
 */
final class ExportReportController extends AbstractController
{
    public function __construct(
        private readonly AgentBillService $agentBillService,
    ) {
    }

    #[Route(path: '/admin/bill-report/export', name: 'admin_bill_report_export', methods: ['POST'])]
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
            $format = is_string($data['format'] ?? null) ? $data['format'] : 'csv';

            $startDate = new \DateTime($startDateStr);
            $endDate = new \DateTime($endDateStr);

            $report = $this->agentBillService->getDetailedBillReport($startDate, $endDate);

            if ('csv' === $format) {
                return $this->exportToCsv($report, $startDate, $endDate);
            }

            if ('excel' === $format) {
                return $this->exportToExcel($report, $startDate, $endDate);
            }

            throw new ExportException('不支持的导出格式：' . $format);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 导出为CSV格式
     * @param array<string, mixed> $report
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
        if (false === $output) {
            throw new ExportException('无法创建临时文件');
        }

        // 写入BOM以支持中文
        fwrite($output, "\xEF\xBB\xBF");

        // 写入汇总信息
        fputcsv($output, ['账单统计报表']);
        fputcsv($output, ['统计期间', $startDate->format('Y-m-d') . ' 至 ' . $endDate->format('Y-m-d')]);

        $this->writeSummarySection($output, $report);
        $this->writeDetailSections($output, $report);

        rewind($output);
        $content = stream_get_contents($output);
        if (false === $content) {
            throw new ExportException('无法读取文件内容');
        }
        $response->setContent($content);
        fclose($output);

        return $response;
    }

    /**
     * 写入汇总信息
     * @param resource $output
     * @param array<string, mixed> $report
     */
    private function writeSummarySection($output, array $report): void
    {
        fputcsv($output, ['总账单数', $this->safeStringValue($report['total_bills'] ?? '0')]);
        fputcsv($output, ['总金额', $this->safeStringValue($report['total_amount'] ?? '0.00')]);
        fputcsv($output, ['总佣金', $this->safeStringValue($report['total_commission'] ?? '0.00')]);
        fputcsv($output, []);
    }

    /**
     * 写入详细统计信息
     * @param resource $output
     * @param array<string, mixed> $report
     */
    private function writeDetailSections($output, array $report): void
    {
        $sections = [
            'status_summary' => ['状态统计', ['状态', '数量', '金额', '佣金']],
            'agent_summary' => ['代理统计', ['代理编号', '代理名称', '账单数', '金额', '佣金']],
            'monthly_summary' => ['月度统计', ['月份', '账单数', '金额', '佣金']],
        ];

        foreach ($sections as $sectionKey => $sectionConfig) {
            $this->writeSectionData($output, $report[$sectionKey] ?? [], $sectionConfig[0], $sectionConfig[1]);
        }
    }

    /**
     * 写入分节数据
     * @param resource $output
     * @param mixed $sectionData
     * @param string $title
     * @param array<int, string> $headers
     */
    private function writeSectionData($output, $sectionData, string $title, array $headers): void
    {
        fputcsv($output, [$title]);
        fputcsv($output, $headers);

        if (!is_array($sectionData)) {
            fputcsv($output, []);

            return;
        }

        foreach ($sectionData as $key => $data) {
            if (!is_array($data)) {
                continue;
            }
            $row = [(string) $key];
            foreach (['name', 'count', 'amount', 'commission'] as $field) {
                if (isset($data[$field])) {
                    $row[] = $this->safeStringValue($data[$field]);
                }
            }
            fputcsv($output, $row);
        }
        fputcsv($output, []);
    }

    /**
     * 安全转换为字符串
     * @param mixed $value
     */
    private function safeStringValue($value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * 导出为Excel格式
     * @param array<string, mixed> $report
     */
    private function exportToExcel(array $report, \DateTimeInterface $startDate, \DateTimeInterface $endDate): Response
    {
        $filename = sprintf(
            'bill_report_%s_%s.xlsx',
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        // 设置标题
        $worksheet->setTitle('账单统计报表');

        $row = 1;

        // 写入标题
        $worksheet->setCellValue("A{$row}", '账单统计报表');
        ++$row;

        $worksheet->setCellValue("A{$row}", '统计期间');
        $worksheet->setCellValue("B{$row}", $startDate->format('Y-m-d') . ' 至 ' . $endDate->format('Y-m-d'));
        ++$row;

        // 写入汇总信息
        $row = $this->writeExcelSummarySection($worksheet, $report, $row);

        // 写入详细统计信息
        $row = $this->writeExcelDetailSections($worksheet, $report, $row);

        // 创建响应
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'excel_export_');
        if (false === $tempFile) {
            throw new ExportException('无法创建临时文件');
        }

        try {
            $writer->save($tempFile);
            $content = file_get_contents($tempFile);
            if (false === $content) {
                throw new ExportException('无法读取Excel文件');
            }

            $response = new Response($content);
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

            return $response;
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * 写入Excel汇总信息
     * @param array<string, mixed> $report
     */
    private function writeExcelSummarySection(Worksheet $worksheet, array $report, int $row): int
    {
        $worksheet->setCellValue("A{$row}", '总账单数');
        $worksheet->setCellValue("B{$row}", $this->safeStringValue($report['total_bills'] ?? '0'));
        ++$row;

        $worksheet->setCellValue("A{$row}", '总金额');
        $worksheet->setCellValue("B{$row}", $this->safeStringValue($report['total_amount'] ?? '0.00'));
        ++$row;

        $worksheet->setCellValue("A{$row}", '总佣金');
        $worksheet->setCellValue("B{$row}", $this->safeStringValue($report['total_commission'] ?? '0.00'));
        ++$row;

        ++$row; // 空行

        return $row;
    }

    /**
     * 写入Excel详细统计信息
     * @param array<string, mixed> $report
     */
    private function writeExcelDetailSections(Worksheet $worksheet, array $report, int $row): int
    {
        $sections = [
            'status_summary' => ['状态统计', ['状态', '数量', '金额', '佣金']],
            'agent_summary' => ['代理统计', ['代理编号', '代理名称', '账单数', '金额', '佣金']],
            'monthly_summary' => ['月度统计', ['月份', '账单数', '金额', '佣金']],
        ];

        foreach ($sections as $sectionKey => $sectionConfig) {
            $row = $this->writeExcelSectionData(
                $worksheet,
                $report[$sectionKey] ?? [],
                $sectionConfig[0],
                $sectionConfig[1],
                $row
            );
        }

        return $row;
    }

    /**
     * 写入Excel分节数据
     * @param mixed $sectionData
     * @param array<int, string> $headers
     */
    private function writeExcelSectionData(Worksheet $worksheet, $sectionData, string $title, array $headers, int $row): int
    {
        // 写入标题
        $worksheet->setCellValue("A{$row}", $title);
        ++$row;

        // 写入表头
        $col = 'A';
        foreach ($headers as $header) {
            $worksheet->setCellValue("{$col}{$row}", $header);
            ++$col;
        }
        ++$row;

        // 写入数据
        if (!is_array($sectionData)) {
            ++$row; // 空行

            return $row;
        }

        foreach ($sectionData as $key => $data) {
            if (!is_array($data)) {
                continue;
            }

            $col = 'A';
            $worksheet->setCellValue("{$col}{$row}", (string) $key);
            ++$col;

            foreach (['name', 'count', 'amount', 'commission'] as $field) {
                if (isset($data[$field])) {
                    $worksheet->setCellValue("{$col}{$row}", $this->safeStringValue($data[$field]));
                    ++$col;
                }
            }
            ++$row;
        }

        ++$row; // 空行

        return $row;
    }
}
