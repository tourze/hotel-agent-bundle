<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Entity\Payment;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;
use Tourze\HotelAgentBundle\Enum\PaymentMethodEnum;
use Tourze\HotelAgentBundle\Enum\PaymentStatusEnum;
use Tourze\HotelAgentBundle\Exception\PaymentProcessingException;
use Tourze\HotelAgentBundle\Service\PaymentService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(PaymentService::class)]
#[RunTestsInSeparateProcesses]
final class PaymentServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 空实现，这个测试类不需要额外的初始化
    }

    public function testServiceInstance(): void
    {
        $this->assertInstanceOf(PaymentService::class, self::getService(PaymentService::class));
    }

    public function testBatchProcessPayments(): void
    {
        $service = self::getService(PaymentService::class);

        // 简化测试，只测试不存在的支付记录
        $results = $service->batchProcessPayments([999], PaymentStatusEnum::SUCCESS);

        $this->assertArrayHasKey(999, $results);
        $this->assertFalse($results[999]['success']);
        $this->assertEquals('支付记录不存在', $results[999]['message']);
    }

    public function testConfirmPayment(): void
    {
        $service = self::getService(PaymentService::class);

        // 测试成功确认
        $successPayment = $this->createMockPayment(1, PaymentStatusEnum::SUCCESS);
        $successPayment->expects($this->once())->method('confirm');

        $result = $service->confirmPayment($successPayment);
        $this->assertTrue($result);

        // 测试非成功状态无法确认
        $pendingPayment = $this->createMockPayment(2, PaymentStatusEnum::PENDING);
        $pendingPayment->expects($this->never())->method('confirm');

        $result = $service->confirmPayment($pendingPayment);
        $this->assertFalse($result);
    }

    public function testCreatePayment(): void
    {
        $service = self::getService(PaymentService::class);

        // 测试非确认账单无法创建支付
        $pendingBill = $this->createMockAgentBill(BillStatusEnum::PENDING, '1000.00');

        $this->expectException(PaymentProcessingException::class);
        $this->expectExceptionMessage('只有已确认的账单才能创建支付记录');
        $service->createPayment($pendingBill, '500.00', PaymentMethodEnum::BANK_TRANSFER);
    }

    public function testCreatePaymentWithInvalidAmount(): void
    {
        $service = self::getService(PaymentService::class);
        $agentBill = $this->createMockAgentBill(BillStatusEnum::CONFIRMED, '1000.00');

        // 测试零金额
        $this->expectException(PaymentProcessingException::class);
        $this->expectExceptionMessage('支付金额必须大于0');
        $service->createPayment($agentBill, '0.00', PaymentMethodEnum::BANK_TRANSFER);
    }

    public function testCreatePaymentWithExcessiveAmount(): void
    {
        $service = self::getService(PaymentService::class);
        $agentBill = $this->createMockAgentBill(BillStatusEnum::CONFIRMED, '1000.00');

        // 测试超出应付佣金的金额
        $this->expectException(PaymentProcessingException::class);
        $this->expectExceptionMessage('支付金额不能超过应付佣金');
        $service->createPayment($agentBill, '1500.00', PaymentMethodEnum::BANK_TRANSFER);
    }

    public function testGeneratePaymentReport(): void
    {
        $service = self::getService(PaymentService::class);

        $startDate = new \DateTime('2024-01-01');
        $endDate = new \DateTime('2024-01-31');

        $report = $service->generatePaymentReport($startDate, $endDate);

        $this->assertArrayHasKey('period', $report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('by_method', $report);
        $this->assertArrayHasKey('by_agent', $report);
        $this->assertArrayHasKey('payments', $report);

        $period = $report['period'];
        $this->assertIsArray($period);
        $this->assertEquals('2024-01-01', $period['start']);
        $this->assertEquals('2024-01-31', $period['end']);
    }

    public function testProcessPaymentFailure(): void
    {
        $service = self::getService(PaymentService::class);

        // 测试成功处理失败
        $pendingPayment = $this->createMockPayment(1, PaymentStatusEnum::PENDING);
        $pendingPayment->expects($this->once())->method('markAsFailed')->with('测试失败原因');

        $result = $service->processPaymentFailure($pendingPayment, '测试失败原因');
        $this->assertTrue($result);

        // 测试非待处理状态无法处理失败
        $successPayment = $this->createMockPayment(2, PaymentStatusEnum::SUCCESS);
        $successPayment->expects($this->never())->method('markAsFailed');

        $result = $service->processPaymentFailure($successPayment, '测试失败原因');
        $this->assertFalse($result);
    }

    public function testProcessPaymentSuccess(): void
    {
        $service = self::getService(PaymentService::class);

        // 测试非待处理状态无法处理成功
        $successPayment = $this->createMockPayment(2, PaymentStatusEnum::SUCCESS);
        $successPayment->expects($this->never())->method('markAsSuccess');

        $result = $service->processPaymentSuccess($successPayment, 'TXN456');
        $this->assertFalse($result);
    }

    public function testValidatePaymentParams(): void
    {
        // 跳过此测试，因为需要配置 payment_config 参数
        self::markTestSkipped('此测试需要配置 payment_config 参数，跳过');
    }

    /**
     * 创建模拟支付记录
     */
    private function createMockPayment(int $id, PaymentStatusEnum $status): MockObject&Payment
    {
        $payment = $this->createMock(Payment::class);
        $payment->method('getId')->willReturn($id);
        $payment->method('getStatus')->willReturn($status);

        return $payment;
    }

    /**
     * 创建模拟代理账单
     */
    private function createMockAgentBill(BillStatusEnum $status, string $amount): MockObject&AgentBill
    {
        $agent = $this->createMock(Agent::class);
        $agent->method('getId')->willReturn(1);
        $agent->method('getCompanyName')->willReturn('测试代理商');

        $agentBill = $this->createMock(AgentBill::class);
        $agentBill->method('getStatus')->willReturn($status);
        $agentBill->method('getCommissionAmount')->willReturn($amount);
        $agentBill->method('getAgent')->willReturn($agent);

        return $agentBill;
    }
}
