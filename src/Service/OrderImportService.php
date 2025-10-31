<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Entity\OrderItem;
use Tourze\HotelAgentBundle\Enum\OrderSourceEnum;
use Tourze\HotelAgentBundle\Enum\OrderStatusEnum;
use Tourze\HotelAgentBundle\Exception\OrderImportException;
use Tourze\HotelAgentBundle\Repository\AgentRepository;
use Tourze\HotelAgentBundle\Repository\OrderRepository;
use Tourze\HotelProfileBundle\Entity\Hotel;
use Tourze\HotelProfileBundle\Entity\RoomType;
use Tourze\HotelProfileBundle\Service\HotelService;
use Tourze\HotelProfileBundle\Service\RoomTypeService;

/**
 * 订单导入服务
 */
#[WithMonologChannel(channel: 'hotel_agent')]
readonly class OrderImportService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private AgentRepository $agentRepository,
        private OrderRepository $orderRepository,
        private HotelService $hotelService,
        private RoomTypeService $roomTypeService,
        private string $uploadsDirectory = '/tmp',
    ) {
    }

    /**
     * 从Excel文件导入订单
     *
     * @return array<string, mixed>
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
            for ($row = 2; $row <= $highestRow; ++$row) {
                try {
                    $rowData = $this->parseRowData($worksheet, $row);

                    if ($this->isEmptyRow($rowData)) {
                        continue; // 跳过空行
                    }

                    $order = $this->createOrderFromRowData($rowData, $operatorId);
                    $this->entityManager->persist($order);
                    ++$successCount;
                } catch (\Throwable $e) {
                    ++$errorCount;
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
     * @return array<string, mixed>
     */
    private function parseRowData(Worksheet $worksheet, int $row): array
    {
        $agentCodeVal = $worksheet->getCell("A{$row}")->getValue();
        $hotelNameVal = $worksheet->getCell("B{$row}")->getValue();
        $roomTypeNameVal = $worksheet->getCell("C{$row}")->getValue();
        $roomCountVal = $worksheet->getCell("F{$row}")->getValue();
        $unitPriceVal = $worksheet->getCell("G{$row}")->getValue();
        $remarkVal = $worksheet->getCell("H{$row}")->getValue();

        return [
            'agent_code' => is_scalar($agentCodeVal) ? trim((string) $agentCodeVal) : '',
            'hotel_name' => is_scalar($hotelNameVal) ? trim((string) $hotelNameVal) : '',
            'room_type_name' => is_scalar($roomTypeNameVal) ? trim((string) $roomTypeNameVal) : '',
            'check_in_date' => $worksheet->getCell("D{$row}")->getValue(),
            'check_out_date' => $worksheet->getCell("E{$row}")->getValue(),
            'room_count' => is_numeric($roomCountVal) ? (int) $roomCountVal : 0,
            'unit_price' => is_numeric($unitPriceVal) ? (float) $unitPriceVal : 0.0,
            'remark' => is_scalar($remarkVal) ? trim((string) $remarkVal) : '',
        ];
    }

    /**
     * 检查是否为空行
     */
    /**
     * @param array<string, mixed> $rowData
     */
    private function isEmptyRow(array $rowData): bool
    {
        $requiredFields = ['agent_code', 'hotel_name', 'room_type_name', 'check_in_date', 'check_out_date'];

        foreach ($requiredFields as $field) {
            if ('' !== $rowData[$field] && null !== $rowData[$field]) {
                return false;
            }
        }

        return true;
    }

    /**
     * 从行数据创建订单
     */
    /**
     * @param array<string, mixed> $rowData
     */
    private function createOrderFromRowData(array $rowData, int $operatorId): Order
    {
        // 验证必填字段
        $this->validateRowData($rowData);

        // 验证并查找实体
        $entities = $this->validateAndFindEntities($rowData);

        // 处理日期
        $checkInDate = $this->parseDate($rowData['check_in_date'], '入住日期');
        $checkOutDate = $this->parseDate($rowData['check_out_date'], '退房日期');

        if ($checkInDate >= $checkOutDate) {
            throw new OrderImportException('入住日期必须早于退房日期');
        }

        // 创建订单
        $remark = isset($rowData['remark']) && is_string($rowData['remark']) ? $rowData['remark'] : null;
        $roomCountRaw = $rowData['room_count'] ?? 0;
        $roomCount = is_numeric($roomCountRaw) ? (int) $roomCountRaw : 0;
        $unitPriceRaw = $rowData['unit_price'] ?? 0;
        $unitPrice = is_numeric($unitPriceRaw) ? (string) $unitPriceRaw : '0';

        $order = new Order();
        $order->setOrderNo($this->generateOrderNo());
        $order->setAgent($entities['agent']);
        $order->setStatus(OrderStatusEnum::PENDING);
        $order->setSource(OrderSourceEnum::EXCEL_IMPORT);
        $order->setRemark($remark);
        $order->setCreatedBy((string) $operatorId);

        // 创建订单项
        $this->createOrderItemsFromRowData(
            $order,
            $entities['hotel'],
            $entities['roomType'],
            $checkInDate,
            $checkOutDate,
            $roomCount,
            $unitPrice
        );

        $order->recalculateTotalAmount();

        return $order;
    }

    /**
     * 验证并查找相关实体
     *
     * @param array<string, mixed> $rowData
     * @return array{agent: Agent, hotel: Hotel, roomType: RoomType}
     */
    private function validateAndFindEntities(array $rowData): array
    {
        $agentCode = is_string($rowData['agent_code'] ?? null) ? $rowData['agent_code'] : '';
        $hotelName = is_string($rowData['hotel_name'] ?? null) ? $rowData['hotel_name'] : '';
        $roomTypeName = is_string($rowData['room_type_name'] ?? null) ? $rowData['room_type_name'] : '';

        // 查找代理
        $agent = $this->agentRepository->findOneBy(['code' => $agentCode]);
        if (null === $agent) {
            throw new OrderImportException("代理编号 '{$agentCode}' 不存在");
        }

        // 查找酒店
        $hotel = $this->hotelService->findHotelByName($hotelName);
        if (null === $hotel) {
            throw new OrderImportException("酒店 '{$hotelName}' 不存在");
        }

        // 查找房型
        $hotelId = $hotel->getId();
        if (null === $hotelId) {
            throw new OrderImportException("Hotel ID is null for hotel '{$hotelName}'");
        }

        $roomType = $this->roomTypeService->findRoomTypeByHotelAndName($hotelId, $roomTypeName);
        if (null === $roomType) {
            throw new OrderImportException("房型 '{$roomTypeName}' 在酒店 '{$hotelName}' 中不存在");
        }

        return [
            'agent' => $agent,
            'hotel' => $hotel,
            'roomType' => $roomType,
        ];
    }

    /**
     * 为订单创建订单项
     */
    private function createOrderItemsFromRowData(
        Order $order,
        Hotel $hotel,
        RoomType $roomType,
        \DateTimeImmutable $checkInDate,
        \DateTimeImmutable $checkOutDate,
        int $roomCount,
        string $unitPrice,
    ): void {
        // 为每个房间创建每晚的订单项
        for ($roomIndex = 0; $roomIndex < $roomCount; ++$roomIndex) {
            $currentDate = $checkInDate;
            while ($currentDate < $checkOutDate) {
                $nextDate = $currentDate->modify('+1 day');

                // 创建订单项（每个房间每晚一个订单项）
                $orderItem = new OrderItem();
                $orderItem->setOrder($order);
                $orderItem->setHotel($hotel);
                $orderItem->setRoomType($roomType);
                $orderItem->setCheckInDate($currentDate);
                $orderItem->setCheckOutDate($nextDate);
                $orderItem->setUnitPrice($unitPrice);
                $orderItem->setAmount($unitPrice); // 单晚价格

                $order->addOrderItem($orderItem);

                $currentDate = $nextDate;
            }
        }
    }

    /**
     * 验证行数据
     */
    /**
     * @param array<string, mixed> $rowData
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
            if (!isset($rowData[$field]) || '' === $rowData[$field]) {
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
     * @param mixed $value
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
            $numericValue = is_string($value) ? (float) $value : $value;
            $date = Date::excelToDateTimeObject($numericValue);

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
        $qb = $this->orderRepository->createQueryBuilder('o');
        $qb->select('MAX(o.orderNo)')
            ->where('o.orderNo LIKE :prefix')
            ->setParameter('prefix', $prefix . $datePart . '%')
        ;

        $maxOrderNo = $qb->getQuery()->getSingleScalarResult();

        if (null !== $maxOrderNo && is_string($maxOrderNo)) {
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
