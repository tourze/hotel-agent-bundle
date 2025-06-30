<?php

namespace Tourze\HotelAgentBundle\Service;

use Brick\Math\BigDecimal;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Entity\OrderItem;
use Tourze\HotelAgentBundle\Enum\OrderSourceEnum;
use Tourze\HotelAgentBundle\Enum\OrderStatusEnum;
use Tourze\HotelAgentBundle\Exception\OrderImportException;
use Tourze\HotelAgentBundle\Repository\AgentRepository;
use Tourze\HotelProfileBundle\Repository\HotelRepository;
use Tourze\HotelProfileBundle\Repository\RoomTypeRepository;

/**
 * 订单导入服务
 */
class OrderImportService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly AgentRepository $agentRepository,
        private readonly HotelRepository $hotelRepository,
        private readonly RoomTypeRepository $roomTypeRepository,
        private readonly string $uploadsDirectory = '/tmp'
    ) {}

    /**
     * 从Excel文件导入订单
     */
    public function importFromExcel(UploadedFile $file, int $operatorId): array
    {
        // 验证文件类型
        if (!in_array($file->getClientOriginalExtension(), ['xlsx', 'xls', 'csv'], true)) {
            throw new OrderImportException('只支持 Excel 或 CSV 格式文件');
        }

        // 移动文件到临时目录
        $fileName = uniqid('order_import_') . '.' . $file->getClientOriginalExtension();
        $filePath = $this->uploadsDirectory . '/' . $fileName;
        $file->move($this->uploadsDirectory, $fileName);

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        try {
            // 读取Excel文件
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();

            $this->entityManager->beginTransaction();

            // 从第2行开始读取数据（第1行为标题）
            for ($row = 2; $row <= $highestRow; $row++) {
                try {
                    $rowData = $this->parseRowData($worksheet, $row);

                    if ($this->isEmptyRow($rowData)) {
                        continue; // 跳过空行
                    }

                    $order = $this->createOrderFromRowData($rowData, $operatorId);
                    $this->entityManager->persist($order);
                    $successCount++;
                } catch (\Throwable $e) {
                    $errorCount++;
                    $errors[] = "第 {$row} 行导入失败: " . $e->getMessage();
                    $this->logger->warning('订单导入失败', [
                        'row' => $row,
                        'error' => $e->getMessage(),
                        'file' => $fileName,
                    ]);
                }
            }

            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info('订单批量导入完成', [
                'file' => $fileName,
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'operator_id' => $operatorId,
            ]);
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $this->logger->error('订单导入失败', [
                'file' => $fileName,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            // 清理临时文件
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        return [
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'errors' => $errors,
        ];
    }

    /**
     * 解析行数据
     */
    private function parseRowData($worksheet, int $row): array
    {
        return [
            'agent_code' => trim((string) ($worksheet->getCell("A{$row}")->getValue() ?? '')),
            'hotel_name' => trim((string) ($worksheet->getCell("B{$row}")->getValue() ?? '')),
            'room_type_name' => trim((string) ($worksheet->getCell("C{$row}")->getValue() ?? '')),
            'check_in_date' => $worksheet->getCell("D{$row}")->getValue(),
            'check_out_date' => $worksheet->getCell("E{$row}")->getValue(),
            'room_count' => (int) ($worksheet->getCell("F{$row}")->getValue() ?? 0),
            'unit_price' => (float) ($worksheet->getCell("G{$row}")->getValue() ?? 0),
            'remark' => trim((string) ($worksheet->getCell("H{$row}")->getValue() ?? '')),
        ];
    }

    /**
     * 检查是否为空行
     */
    private function isEmptyRow(array $rowData): bool
    {
        $requiredFields = ['agent_code', 'hotel_name', 'room_type_name', 'check_in_date', 'check_out_date'];

        foreach ($requiredFields as $field) {
            if (!empty($rowData[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 从行数据创建订单
     */
    private function createOrderFromRowData(array $rowData, int $operatorId): Order
    {
        // 验证必填字段
        $this->validateRowData($rowData);

        // 查找代理
        $agent = $this->agentRepository
            ->findOneBy(['code' => $rowData['agent_code']]);

        if (null === $agent) {
            throw new OrderImportException("代理编号 '{$rowData['agent_code']}' 不存在");
        }

        // 查找酒店
        $hotel = $this->hotelRepository
            ->findOneBy(['name' => $rowData['hotel_name']]);

        if (null === $hotel) {
            throw new OrderImportException("酒店 '{$rowData['hotel_name']}' 不存在");
        }

        // 查找房型
        $roomType = $this->roomTypeRepository
            ->findOneBy(['hotel' => $hotel, 'name' => $rowData['room_type_name']]);

        if (null === $roomType) {
            throw new OrderImportException("房型 '{$rowData['room_type_name']}' 在酒店 '{$rowData['hotel_name']}' 中不存在");
        }

        // 处理日期
        $checkInDate = $this->parseDate($rowData['check_in_date'], '入住日期');
        $checkOutDate = $this->parseDate($rowData['check_out_date'], '退房日期');

        if ($checkInDate >= $checkOutDate) {
            throw new OrderImportException('入住日期必须早于退房日期');
        }

        // 创建订单
        $order = new Order();
        $order->setOrderNo($this->generateOrderNo());
        $order->setAgent($agent);
        $order->setStatus(OrderStatusEnum::PENDING);
        $order->setSource(OrderSourceEnum::EXCEL_IMPORT);
        $order->setRemark($rowData['remark'] ?? null);
        $order->setCreatedBy($operatorId);

        // 创建订单项
        $orderItem = new OrderItem();
        $orderItem->setOrder($order);
        $orderItem->setHotel($hotel);
        $orderItem->setRoomType($roomType);
        $orderItem->setCheckInDate($checkInDate);
        $orderItem->setCheckOutDate($checkOutDate);
        $orderItem->setUnitPrice((string) $rowData['unit_price']);

        // 计算金额 - 使用 Brick\Math 进行精确计算
        $nights = $checkInDate->diff($checkOutDate)->days;
        $unitPrice = BigDecimal::of($rowData['unit_price']);
        $roomCount = BigDecimal::of($rowData['room_count']);
        $nightsDecimal = BigDecimal::of($nights);

        $amount = $unitPrice->multipliedBy($roomCount)->multipliedBy($nightsDecimal)->toScale(2);
        $orderItem->setAmount($amount->__toString());

        $order->addOrderItem($orderItem);
        $order->recalculateTotalAmount();

        return $order;
    }

    /**
     * 验证行数据
     */
    private function validateRowData(array $rowData): void
    {
        $requiredFields = [
            'agent_code' => '代理编号',
            'hotel_name' => '酒店名称',
            'room_type_name' => '房型名称',
            'check_in_date' => '入住日期',
            'check_out_date' => '退房日期',
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($rowData[$field])) {
                throw new OrderImportException("{$label}不能为空");
            }
        }

        if ($rowData['room_count'] <= 0) {
            throw new OrderImportException('房间数量必须大于0');
        }

        if ($rowData['unit_price'] <= 0) {
            throw new OrderImportException('单价必须大于0');
        }
    }

    /**
     * 解析日期
     */
    private function parseDate($value, string $fieldName): \DateTimeImmutable
    {
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof \DateTime) {
            return \DateTimeImmutable::createFromMutable($value);
        }

        if (is_numeric($value)) {
            // Excel 日期序列号
            $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
            return \DateTimeImmutable::createFromMutable($date);
        }

        if (is_string($value)) {
            try {
                return new \DateTimeImmutable($value);
            } catch (\Throwable $e) {
                throw new OrderImportException("{$fieldName}格式不正确: {$value}");
            }
        }

        throw new OrderImportException("{$fieldName}格式不正确");
    }

    /**
     * 生成订单编号
     */
    private function generateOrderNo(): string
    {
        $prefix = 'ORD';
        $datePart = date('Ymd');

        // 查询当天最大订单号
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('MAX(o.orderNo)')
            ->from(Order::class, 'o')
            ->where('o.orderNo LIKE :prefix')
            ->setParameter('prefix', $prefix . $datePart . '%');

        $maxOrderNo = $qb->getQuery()->getSingleScalarResult();

        if ($maxOrderNo) {
            $sequence = (int) substr($maxOrderNo, -4) + 1;
        } else {
            $sequence = 1;
        }

        return $prefix . $datePart . sprintf('%04d', $sequence);
    }

    /**
     * 获取导入模板下载链接
     */
    public function getTemplateDownloadUrl(): string
    {
        // 这里可以返回Excel模板的下载链接
        return '/admin/order/template.xlsx';
    }

    /**
     * 生成导入模板
     */
    public function generateTemplate(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        // 设置标题
        $headers = [
            'A1' => '代理编号',
            'B1' => '酒店名称',
            'C1' => '房型名称',
            'D1' => '入住日期',
            'E1' => '退房日期',
            'F1' => '房间数量',
            'G1' => '单价',
            'H1' => '备注',
        ];

        foreach ($headers as $cell => $value) {
            $worksheet->setCellValue($cell, $value);
        }

        // 设置样例数据
        $worksheet->setCellValue('A2', 'AG001');
        $worksheet->setCellValue('B2', '北京国际酒店');
        $worksheet->setCellValue('C2', '标准大床房');
        $worksheet->setCellValue('D2', '2024-01-01');
        $worksheet->setCellValue('E2', '2024-01-02');
        $worksheet->setCellValue('F2', 1);
        $worksheet->setCellValue('G2', 300.00);
        $worksheet->setCellValue('H2', '测试订单');

        return $spreadsheet;
    }
}
