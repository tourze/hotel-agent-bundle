<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Entity\OrderItem;
use Tourze\HotelAgentBundle\Enum\AuditStatusEnum;
use Tourze\HotelAgentBundle\Enum\OrderSourceEnum;
use Tourze\HotelAgentBundle\Enum\OrderStatusEnum;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Order::class)]
final class OrderTest extends AbstractEntityTestCase
{
    protected function createEntity(): Order
    {
        $order = new Order();
        $order->setCreatedBy('123');

        return $order;
    }

    public function testConstructInitializesCollectionsAndDefaults(): void
    {
        $order = new Order();
        $order->setCreatedBy('123');

        $this->assertCount(0, $order->getOrderItems());
        $this->assertSame([], $order->getChangeHistory());
        $this->assertSame(OrderStatusEnum::PENDING, $order->getStatus());
        $this->assertSame(OrderSourceEnum::MANUAL_INPUT, $order->getSource());
        $this->assertSame(AuditStatusEnum::APPROVED, $order->getAuditStatus());
        $this->assertFalse($order->isComplex());
        $this->assertSame('0.00', $order->getTotalAmount());
    }

    public function testToStringReturnsOrderNo(): void
    {
        $order = new Order();
        $order->setOrderNo('ORD20250101001');

        $result = (string) $order;

        $this->assertSame('ORD20250101001', $result);
    }

    public function testToStringWithEmptyOrderNo(): void
    {
        $order = new Order();
        $result = (string) $order;

        $this->assertSame('', $result);
    }

    public function testSetOrderNoWithValidNo(): void
    {
        $order = new Order();
        $order->setOrderNo('ORD20250101001');

        $this->assertSame('ORD20250101001', $order->getOrderNo());
    }

    public function testSetAgentWithValidAgent(): void
    {
        $agent = new Agent();
        $agent->setCode('AGT001');
        $agent->setCompanyName('测试公司');
        $agent->setCreatedBy('test-user');
        $order = new Order();
        $order->setAgent($agent);

        $this->assertSame($agent, $order->getAgent());
    }

    public function testSetAgentWithNull(): void
    {
        $order = new Order();
        $order->setAgent(null);

        $this->assertNull($order->getAgent());
    }

    public function testSetTotalAmountWithValidAmount(): void
    {
        $order = new Order();
        $order->setTotalAmount('1500.50');

        $this->assertSame('1500.50', $order->getTotalAmount());
    }

    public function testSetStatusWithValidStatus(): void
    {
        $order = new Order();
        $order->setStatus(OrderStatusEnum::CONFIRMED);

        $this->assertSame(OrderStatusEnum::CONFIRMED, $order->getStatus());
    }

    public function testSetSourceWithValidSource(): void
    {
        $order = new Order();
        $order->setSource(OrderSourceEnum::EXCEL_IMPORT);

        $this->assertSame(OrderSourceEnum::EXCEL_IMPORT, $order->getSource());
    }

    public function testSetImportFileWithValidFile(): void
    {
        $order = new Order();
        $file = '/uploads/import_20250101.xlsx';
        $order->setImportFile($file);

        $this->assertSame($file, $order->getImportFile());
    }

    public function testSetIsComplexWithTrue(): void
    {
        $order = new Order();
        $order->setIsComplex(true);

        $this->assertTrue($order->isComplex());
    }

    public function testSetIsComplexWithFalse(): void
    {
        $order = new Order();
        $order->setIsComplex(false);

        $this->assertFalse($order->isComplex());
    }

    public function testSetRemarkWithValidRemark(): void
    {
        $remark = '测试订单备注';
        $order = new Order();
        $order->setRemark($remark);

        $this->assertSame($remark, $order->getRemark());
    }

    public function testSetCancelReasonWithReason(): void
    {
        $order = new Order();
        $order->setCreatedBy('123');
        $reason = '客户取消';
        $order->setCancelReason($reason);

        $this->assertSame($reason, $order->getCancelReason());
    }

    public function testSetCancelTimeWithTime(): void
    {
        $order = new Order();
        $order->setCreatedBy('123');
        $time = new \DateTimeImmutable();
        $order->setCancelTime($time);

        $this->assertSame($time, $order->getCancelTime());
    }

    public function testSetCancelledByWithUserId(): void
    {
        $order = new Order();
        $order->setCreatedBy('123');
        $order->setCancelledBy(123);

        $this->assertSame(123, $order->getCancelledBy());
    }

    public function testSetChangeHistoryWithValidHistory(): void
    {
        $order = new Order();
        $order->setCreatedBy('123');
        $history = [
            ['type' => 'create', 'timestamp' => '2025-01-01 10:00:00'],
            ['type' => 'update', 'timestamp' => '2025-01-01 11:00:00'],
        ];
        $order->setChangeHistory($history);

        $this->assertSame($history, $order->getChangeHistory());
    }

    public function testAddChangeRecordAddsNewRecord(): void
    {
        $order = new Order();
        $order->setCreatedBy('123');
        $order->addChangeRecord('status_change', ['from' => 'pending', 'to' => 'confirmed'], 123);

        $history = $order->getChangeHistory();
        $this->assertIsArray($history);
        $this->assertCount(1, $history);
        $this->assertArrayHasKey(0, $history);

        $record = $history[0];
        $this->assertSame('status_change', $record['type']);
        $this->assertSame(['from' => 'pending', 'to' => 'confirmed'], $record['changes']);
        $this->assertSame(123, $record['operatorId']);
        $this->assertArrayHasKey('timestamp', $record);
    }

    public function testAddChangeRecordWithoutOperator(): void
    {
        $order = new Order();
        $order->setCreatedBy('123');
        $order->addChangeRecord('test_change', ['key' => 'value']);

        $history = $order->getChangeHistory();
        $this->assertIsArray($history);
        $this->assertArrayHasKey(0, $history);
        $record = $history[0];
        $this->assertNull($record['operatorId']);
    }

    public function testSetAuditStatusWithValidStatus(): void
    {
        $order = new Order();
        $order->setCreatedBy('123');
        $order->setAuditStatus(AuditStatusEnum::RISK_REVIEW);

        $this->assertSame(AuditStatusEnum::RISK_REVIEW, $order->getAuditStatus());
    }

    public function testSetAuditRemarkWithRemark(): void
    {
        $order = new Order();
        $order->setCreatedBy('123');
        $remark = '需要人工审核';
        $order->setAuditRemark($remark);

        $this->assertSame($remark, $order->getAuditRemark());
    }

    public function testSetCreatedByWithUserId(): void
    {
        $order = new Order();
        $order->setCreatedBy('456');

        $this->assertSame('456', $order->getCreatedBy());
    }

    public function testAddOrderItemAddsNewItem(): void
    {
        $order = new Order();
        $order->setCreatedBy('123');
        $orderItem = new OrderItem();
        $orderItem->setAmount('100.00');

        $result = $order->addOrderItem($orderItem);

        $this->assertSame($order, $result);
        $this->assertTrue($order->getOrderItems()->contains($orderItem));
        $this->assertSame($order, $orderItem->getOrder());
        $this->assertSame('100.00', $order->getTotalAmount());
    }

    public function testAddOrderItemDoesNotAddDuplicate(): void
    {
        $order = new Order();
        $order->setCreatedBy('123');
        $orderItem = new OrderItem();
        $orderItem->setAmount('100.00');
        $order->addOrderItem($orderItem);

        $order->addOrderItem($orderItem);

        $this->assertCount(1, $order->getOrderItems());
    }

    public function testRemoveOrderItemRemovesExistingItem(): void
    {
        $order = new Order();
        $order->setCreatedBy('123');
        $orderItem = new OrderItem();
        $orderItem->setAmount('100.00');
        $order->addOrderItem($orderItem);

        $result = $order->removeOrderItem($orderItem);

        $this->assertSame($order, $result);
        $this->assertFalse($order->getOrderItems()->contains($orderItem));
        $this->assertNull($orderItem->getOrder());
        $this->assertSame('0.00', $order->getTotalAmount());
    }

    public function testRecalculateTotalAmountSumsItemAmounts(): void
    {
        $order = new Order();
        $order->setCreatedBy('123');
        $item1 = new OrderItem();
        $item1->setAmount('100.00');
        $item2 = new OrderItem();
        $item2->setAmount('200.50');

        $order->addOrderItem($item1);
        $order->addOrderItem($item2);

        $this->assertSame('300.50', $order->getTotalAmount());
    }

    public function testCancelUpdatesStatusAndRecordsChange(): void
    {
        $order = new Order();
        $order->setCreatedBy('123');
        $reason = '客户要求取消';
        $cancelledBy = 123;

        $result = $order->cancel($reason, $cancelledBy);

        $this->assertSame($order, $result);
        $this->assertSame(OrderStatusEnum::CANCELED, $order->getStatus());
        $this->assertSame($reason, $order->getCancelReason());
        $this->assertInstanceOf(\DateTimeInterface::class, $order->getCancelTime());
        $this->assertSame($cancelledBy, $order->getCancelledBy());

        $history = $order->getChangeHistory();
        $this->assertIsArray($history);
        $this->assertCount(1, $history);
        $this->assertArrayHasKey(0, $history);
        $this->assertSame('cancel', $history[0]['type']);
    }

    public function testConfirmUpdatesStatusAndRecordsChange(): void
    {
        $order = new Order();
        $order->setCreatedBy('123');
        $operatorId = 456;

        $result = $order->confirm($operatorId);

        $this->assertSame($order, $result);
        $this->assertSame(OrderStatusEnum::CONFIRMED, $order->getStatus());

        $history = $order->getChangeHistory();
        $this->assertIsArray($history);
        $this->assertCount(1, $history);
        $this->assertArrayHasKey(0, $history);
        $this->assertSame('confirm', $history[0]['type']);
        $this->assertSame($operatorId, $history[0]['operatorId']);
    }

    public function testCloseUpdatesStatusAndRecordsChange(): void
    {
        $order = new Order();
        $order->setCreatedBy('123');
        $reason = '超时未支付';
        $operatorId = 789;

        $result = $order->close($reason, $operatorId);

        $this->assertSame($order, $result);
        $this->assertSame(OrderStatusEnum::CLOSED, $order->getStatus());

        $history = $order->getChangeHistory();
        $this->assertIsArray($history);
        $this->assertCount(1, $history);
        $this->assertArrayHasKey(0, $history);
        $this->assertSame('close', $history[0]['type']);
        $this->assertSame(['reason' => $reason, 'from' => 'active', 'to' => 'closed'], $history[0]['changes']);
    }

    public function testSetCreateTimeSetsTime(): void
    {
        $time = new \DateTimeImmutable();
        $order = new Order();
        $order->setCreateTime($time);

        $this->assertSame($time, $order->getCreateTime());
    }

    public function testSetUpdateTimeSetsTime(): void
    {
        $time = new \DateTimeImmutable();
        $order = new Order();
        $order->setUpdateTime($time);

        $this->assertSame($time, $order->getUpdateTime());
    }

    public static function propertiesProvider(): iterable
    {
        $agent = new Agent();
        $agent->setCode('TEST001');
        $agent->setCompanyName('测试代理公司');
        $agent->setCreatedBy('test-user');

        yield 'pending_order' => ['orderNo', 'ORD20250101001'];
        yield 'complex_order' => ['agent', $agent];
        yield 'canceled_order' => ['status', OrderStatusEnum::CANCELED];
    }
}
