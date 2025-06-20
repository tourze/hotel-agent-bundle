<?php

namespace Tourze\HotelAgentBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Entity\OrderItem;
use Tourze\HotelAgentBundle\Enum\AuditStatusEnum;
use Tourze\HotelAgentBundle\Enum\OrderSourceEnum;
use Tourze\HotelAgentBundle\Enum\OrderStatusEnum;

class OrderTest extends TestCase
{
    private Order $order;
    private Agent $agent;

    protected function setUp(): void
    {
        $this->order = new Order();
        $this->agent = new Agent();
        $this->agent->setCode('AGT001')->setCompanyName('测试公司');
    }

    public function test_construct_initializes_collections_and_defaults(): void
    {
        $order = new Order();

        $this->assertCount(0, $order->getOrderItems());
        $this->assertSame([], $order->getChangeHistory());
        $this->assertSame(OrderStatusEnum::PENDING, $order->getStatus());
        $this->assertSame(OrderSourceEnum::MANUAL_INPUT, $order->getSource());
        $this->assertSame(AuditStatusEnum::APPROVED, $order->getAuditStatus());
        $this->assertFalse($order->isComplex());
        $this->assertSame('0.00', $order->getTotalAmount());
    }

    public function test_toString_returns_order_no(): void
    {
        $this->order->setOrderNo('ORD20250101001');

        $result = (string)$this->order;

        $this->assertSame('ORD20250101001', $result);
    }

    public function test_toString_with_empty_order_no(): void
    {
        $result = (string)$this->order;

        $this->assertSame('', $result);
    }

    public function test_setOrderNo_with_valid_no(): void
    {
        $this->order->setOrderNo('ORD20250101001');

        $this->assertSame('ORD20250101001', $this->order->getOrderNo());
    }

    public function test_setAgent_with_valid_agent(): void
    {
        $this->order->setAgent($this->agent);

        $this->assertSame($this->agent, $this->order->getAgent());
    }

    public function test_setAgent_with_null(): void
    {
        $this->order->setAgent(null);

        $this->assertNull($this->order->getAgent());
    }

    public function test_setTotalAmount_with_valid_amount(): void
    {
        $this->order->setTotalAmount('1500.50');

        $this->assertSame('1500.50', $this->order->getTotalAmount());
    }

    public function test_setStatus_with_valid_status(): void
    {
        $this->order->setStatus(OrderStatusEnum::CONFIRMED);

        $this->assertSame(OrderStatusEnum::CONFIRMED, $this->order->getStatus());
    }

    public function test_setSource_with_valid_source(): void
    {
        $this->order->setSource(OrderSourceEnum::EXCEL_IMPORT);

        $this->assertSame(OrderSourceEnum::EXCEL_IMPORT, $this->order->getSource());
    }

    public function test_setImportFile_with_valid_file(): void
    {
        $file = '/uploads/import_20250101.xlsx';
        $this->order->setImportFile($file);

        $this->assertSame($file, $this->order->getImportFile());
    }

    public function test_setIsComplex_with_true(): void
    {
        $this->order->setIsComplex(true);

        $this->assertTrue($this->order->isComplex());
    }

    public function test_setIsComplex_with_false(): void
    {
        $this->order->setIsComplex(false);

        $this->assertFalse($this->order->isComplex());
    }

    public function test_setRemark_with_valid_remark(): void
    {
        $remark = '测试订单备注';
        $this->order->setRemark($remark);

        $this->assertSame($remark, $this->order->getRemark());
    }

    public function test_setCancelReason_with_reason(): void
    {
        $reason = '客户取消';
        $this->order->setCancelReason($reason);

        $this->assertSame($reason, $this->order->getCancelReason());
    }

    public function test_setCancelTime_with_time(): void
    {
        $time = new \DateTime();
        $this->order->setCancelTime($time);

        $this->assertSame($time, $this->order->getCancelTime());
    }

    public function test_setCancelledBy_with_user_id(): void
    {
        $this->order->setCancelledBy(123);

        $this->assertSame(123, $this->order->getCancelledBy());
    }

    public function test_setChangeHistory_with_valid_history(): void
    {
        $history = [
            ['type' => 'create', 'timestamp' => '2025-01-01 10:00:00'],
            ['type' => 'update', 'timestamp' => '2025-01-01 11:00:00'],
        ];
        $this->order->setChangeHistory($history);

        $this->assertSame($history, $this->order->getChangeHistory());
    }

    public function test_addChangeRecord_adds_new_record(): void
    {
        $this->order->addChangeRecord('status_change', ['from' => 'pending', 'to' => 'confirmed'], 123);

        $history = $this->order->getChangeHistory();
        $this->assertCount(1, $history);

        $record = $history[0];
        $this->assertSame('status_change', $record['type']);
        $this->assertSame(['from' => 'pending', 'to' => 'confirmed'], $record['changes']);
        $this->assertSame(123, $record['operatorId']);
        $this->assertArrayHasKey('timestamp', $record);
    }

    public function test_addChangeRecord_without_operator(): void
    {
        $this->order->addChangeRecord('test_change', ['key' => 'value']);

        $history = $this->order->getChangeHistory();
        $record = $history[0];
        $this->assertNull($record['operatorId']);
    }

    public function test_setAuditStatus_with_valid_status(): void
    {
        $this->order->setAuditStatus(AuditStatusEnum::RISK_REVIEW);

        $this->assertSame(AuditStatusEnum::RISK_REVIEW, $this->order->getAuditStatus());
    }

    public function test_setAuditRemark_with_remark(): void
    {
        $remark = '需要人工审核';
        $this->order->setAuditRemark($remark);

        $this->assertSame($remark, $this->order->getAuditRemark());
    }

    public function test_setCreatedBy_with_user_id(): void
    {
        $this->order->setCreatedBy(456);

        $this->assertSame(456, $this->order->getCreatedBy());
    }

    public function test_addOrderItem_adds_new_item(): void
    {
        $orderItem = new OrderItem();
        $orderItem->setAmount('100.00');

        $result = $this->order->addOrderItem($orderItem);

        $this->assertSame($this->order, $result);
        $this->assertTrue($this->order->getOrderItems()->contains($orderItem));
        $this->assertSame($this->order, $orderItem->getOrder());
        $this->assertSame('100.00', $this->order->getTotalAmount());
    }

    public function test_addOrderItem_does_not_add_duplicate(): void
    {
        $orderItem = new OrderItem();
        $this->order->addOrderItem($orderItem);

        $this->order->addOrderItem($orderItem);

        $this->assertCount(1, $this->order->getOrderItems());
    }

    public function test_removeOrderItem_removes_existing_item(): void
    {
        $orderItem = new OrderItem();
        $orderItem->setAmount('100.00');
        $this->order->addOrderItem($orderItem);

        $result = $this->order->removeOrderItem($orderItem);

        $this->assertSame($this->order, $result);
        $this->assertFalse($this->order->getOrderItems()->contains($orderItem));
        $this->assertNull($orderItem->getOrder());
        $this->assertSame('0.00', $this->order->getTotalAmount());
    }

    public function test_recalculateTotalAmount_sums_item_amounts(): void
    {
        $item1 = new OrderItem();
        $item1->setAmount('100.00');
        $item2 = new OrderItem();
        $item2->setAmount('200.50');

        $this->order->addOrderItem($item1);
        $this->order->addOrderItem($item2);

        $this->assertSame('300.50', $this->order->getTotalAmount());
    }

    public function test_cancel_updates_status_and_records_change(): void
    {
        $reason = '客户要求取消';
        $cancelledBy = 123;

        $result = $this->order->cancel($reason, $cancelledBy);

        $this->assertSame($this->order, $result);
        $this->assertSame(OrderStatusEnum::CANCELED, $this->order->getStatus());
        $this->assertSame($reason, $this->order->getCancelReason());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->order->getCancelTime());
        $this->assertSame($cancelledBy, $this->order->getCancelledBy());

        $history = $this->order->getChangeHistory();
        $this->assertCount(1, $history);
        $this->assertSame('cancel', $history[0]['type']);
    }

    public function test_confirm_updates_status_and_records_change(): void
    {
        $operatorId = 456;

        $result = $this->order->confirm($operatorId);

        $this->assertSame($this->order, $result);
        $this->assertSame(OrderStatusEnum::CONFIRMED, $this->order->getStatus());

        $history = $this->order->getChangeHistory();
        $this->assertCount(1, $history);
        $this->assertSame('confirm', $history[0]['type']);
        $this->assertSame($operatorId, $history[0]['operatorId']);
    }

    public function test_close_updates_status_and_records_change(): void
    {
        $reason = '超时未支付';
        $operatorId = 789;

        $result = $this->order->close($reason, $operatorId);

        $this->assertSame($this->order, $result);
        $this->assertSame(OrderStatusEnum::CLOSED, $this->order->getStatus());

        $history = $this->order->getChangeHistory();
        $this->assertCount(1, $history);
        $this->assertSame('close', $history[0]['type']);
        $this->assertSame(['reason' => $reason, 'from' => 'active', 'to' => 'closed'], $history[0]['changes']);
    }

    public function test_setCreateTime_sets_time(): void
    {
        $time = new \DateTimeImmutable();
        $this->order->setCreateTime($time);

        $this->assertSame($time, $this->order->getCreateTime());
    }

    public function test_setUpdateTime_sets_time(): void
    {
        $time = new \DateTimeImmutable();
        $this->order->setUpdateTime($time);

        $this->assertSame($time, $this->order->getUpdateTime());
    }

    public function test_default_values(): void
    {
        $order = new Order();

        $this->assertSame('', $order->getOrderNo());
        $this->assertNull($order->getAgent());
        $this->assertSame('0.00', $order->getTotalAmount());
        $this->assertSame(OrderStatusEnum::PENDING, $order->getStatus());
        $this->assertSame(OrderSourceEnum::MANUAL_INPUT, $order->getSource());
        $this->assertNull($order->getImportFile());
        $this->assertFalse($order->isComplex());
        $this->assertNull($order->getRemark());
        $this->assertNull($order->getCancelReason());
        $this->assertNull($order->getCancelTime());
        $this->assertNull($order->getCancelledBy());
        $this->assertSame([], $order->getChangeHistory());
        $this->assertSame(AuditStatusEnum::APPROVED, $order->getAuditStatus());
        $this->assertNull($order->getAuditRemark());
        $this->assertNull($order->getCreateTime());
        $this->assertNull($order->getUpdateTime());
        $this->assertNull($order->getCreatedBy());
    }
}
