<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Entity\Payment;
use Tourze\HotelAgentBundle\Enum\PaymentMethodEnum;
use Tourze\HotelAgentBundle\Enum\PaymentStatusEnum;

class PaymentFixtures extends Fixture implements DependentFixtureInterface
{
    public const PAYMENT_1_REFERENCE = 'payment-1';
    public const PAYMENT_2_REFERENCE = 'payment-2';
    public const PAYMENT_3_REFERENCE = 'payment-3';

    public function load(ObjectManager $manager): void
    {
        $agentBill = $this->getReference(AgentBillFixtures::AGENT_BILL_A_LEVEL_REFERENCE, AgentBill::class);

        $payment1 = new Payment();
        $payment1->setAgentBill($agentBill);
        $payment1->generatePaymentNo();
        $payment1->setAmount('5000.00');
        $payment1->setPaymentMethod(PaymentMethodEnum::BANK_TRANSFER);
        $payment1->setStatus(PaymentStatusEnum::SUCCESS);
        $payment1->setTransactionId('TXN202401010001');
        $payment1->setRemarks('银行转账支付');
        $payment1->setCreatedBy('1');
        $payment1->markAsSuccess('TXN202401010001');

        $manager->persist($payment1);

        $payment2 = new Payment();
        $payment2->setAgentBill($agentBill);
        $payment2->generatePaymentNo();
        $payment2->setAmount('3000.00');
        $payment2->setPaymentMethod(PaymentMethodEnum::ALIPAY);
        $payment2->setStatus(PaymentStatusEnum::PENDING);
        $payment2->setRemarks('支付宝支付');
        $payment2->setCreatedBy('1');

        $manager->persist($payment2);

        $payment3 = new Payment();
        $payment3->setAgentBill($agentBill);
        $payment3->generatePaymentNo();
        $payment3->setAmount('1000.00');
        $payment3->setPaymentMethod(PaymentMethodEnum::WECHAT);
        $payment3->setStatus(PaymentStatusEnum::FAILED);
        $payment3->setFailureReason('余额不足');
        $payment3->setRemarks('微信支付失败');
        $payment3->setCreatedBy('1');
        $payment3->markAsFailed('余额不足');

        $manager->persist($payment3);

        $manager->flush();

        $this->addReference(self::PAYMENT_1_REFERENCE, $payment1);
        $this->addReference(self::PAYMENT_2_REFERENCE, $payment2);
        $this->addReference(self::PAYMENT_3_REFERENCE, $payment3);
    }

    public function getDependencies(): array
    {
        return [
            AgentBillFixtures::class,
        ];
    }
}
