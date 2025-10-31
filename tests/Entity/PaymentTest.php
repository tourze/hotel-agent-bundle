<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Entity\Payment;
use Tourze\HotelAgentBundle\Enum\PaymentMethodEnum;
use Tourze\HotelAgentBundle\Enum\PaymentStatusEnum;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Payment::class)]
final class PaymentTest extends AbstractEntityTestCase
{
    public function testPaymentInitialization(): void
    {
        $payment = new Payment();

        // 验证初始值
        $this->assertSame(0, $payment->getId());
        $this->assertSame('0.00', $payment->getAmount());
        $this->assertSame(PaymentMethodEnum::BANK_TRANSFER, $payment->getPaymentMethod());
        $this->assertSame(PaymentStatusEnum::PENDING, $payment->getStatus());
        $this->assertSame('', $payment->getPaymentNo());
    }

    public function testPaymentSettersAndGetters(): void
    {
        $payment = new Payment();

        // 测试金额
        $payment->setAmount('1000.50');
        $this->assertSame('1000.50', $payment->getAmount());

        // 测试支付方式
        $payment->setPaymentMethod(PaymentMethodEnum::CREDIT_CARD);
        $this->assertSame(PaymentMethodEnum::CREDIT_CARD, $payment->getPaymentMethod());

        // 测试支付状态
        $payment->setStatus(PaymentStatusEnum::SUCCESS);
        $this->assertSame(PaymentStatusEnum::SUCCESS, $payment->getStatus());

        // 测试交易编号
        $payment->setTransactionId('TXN123456');
        $this->assertSame('TXN123456', $payment->getTransactionId());

        // 测试备注
        $payment->setRemarks('Test payment');
        $this->assertSame('Test payment', $payment->getRemarks());

        // 测试支付单号
        $payment->setPaymentNo('PAY20240101001');
        $this->assertSame('PAY20240101001', $payment->getPaymentNo());
    }

    public function testMarkAsSuccess(): void
    {
        $payment = new Payment();
        $payment->markAsSuccess('TXN123456');

        $this->assertSame(PaymentStatusEnum::SUCCESS, $payment->getStatus());
        $this->assertSame('TXN123456', $payment->getTransactionId());
        $this->assertNotNull($payment->getPaymentTime());
    }

    public function testMarkAsFailed(): void
    {
        $payment = new Payment();
        $payment->markAsFailed('Invalid card number');

        $this->assertSame(PaymentStatusEnum::FAILED, $payment->getStatus());
        $this->assertSame('Invalid card number', $payment->getFailureReason());
    }

    public function testGeneratePaymentNo(): void
    {
        $payment = new Payment();
        $payment->generatePaymentNo();

        $this->assertStringStartsWith('PAY', $payment->getPaymentNo());
        $this->assertGreaterThan(10, strlen($payment->getPaymentNo()));
    }

    public static function propertiesProvider(): iterable
    {
        $agent = new Agent();
        $agent->setCode('TEST001');
        $agent->setCompanyName('测试代理公司');
        $agent->setCreatedBy('test-user');

        $bill = new AgentBill();
        $bill->setAgent($agent);
        $bill->setBillMonth('2024-01');

        yield 'pending_payment' => ['agentBill', $bill];
        yield 'successful_payment' => ['paymentNo', 'PAY20250101002'];
        yield 'failed_payment' => ['amount', '800.00'];
        yield 'confirmed_payment' => ['status', PaymentStatusEnum::SUCCESS];
    }

    protected function createEntity(): object
    {
        return new Payment();
    }
}
