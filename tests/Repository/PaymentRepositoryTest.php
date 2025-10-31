<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Entity\Payment;
use Tourze\HotelAgentBundle\Enum\AgentLevelEnum;
use Tourze\HotelAgentBundle\Enum\AgentStatusEnum;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;
use Tourze\HotelAgentBundle\Enum\PaymentMethodEnum;
use Tourze\HotelAgentBundle\Enum\PaymentStatusEnum;
use Tourze\HotelAgentBundle\Enum\SettlementTypeEnum;
use Tourze\HotelAgentBundle\Repository\PaymentRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(PaymentRepository::class)]
#[RunTestsInSeparateProcesses]
final class PaymentRepositoryTest extends AbstractRepositoryTestCase
{
    private PaymentRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(PaymentRepository::class);
    }

    private function createTestAgent(string $code = 'TEST'): Agent
    {
        /** @var int $counter */
        static $counter = 0;
        ++$counter;

        $agent = new Agent();
        $agent->setCode($code . str_pad((string) $counter, 3, '0', STR_PAD_LEFT));
        $agent->setCompanyName('Test Company ' . $counter);
        $agent->setContactPerson('Test Contact ' . $counter);
        $agent->setPhone('1380013' . str_pad((string) $counter, 4, '0', STR_PAD_LEFT));
        $agent->setEmail('test' . $counter . '@example.com');
        $agent->setCommissionRate('10.00');
        $agent->setStatus(AgentStatusEnum::ACTIVE);
        $agent->setLevel(AgentLevelEnum::C);

        return $agent;
    }

    private function createTestAgentBill(Agent $agent, string $billMonth = '2024-01'): AgentBill
    {
        $agentBill = new AgentBill();
        $agentBill->setAgent($agent);
        $agentBill->setBillMonth($billMonth);
        $agentBill->setOrderCount(5);
        $agentBill->setTotalAmount('1000.00');
        $agentBill->setCommissionRate('10.00');
        $agentBill->setCommissionAmount('100.00');
        $agentBill->setSettlementType(SettlementTypeEnum::MONTHLY);
        $agentBill->setStatus(BillStatusEnum::CONFIRMED);

        return $agentBill;
    }

    private function createTestPayment(AgentBill $agentBill, string $paymentNo, string $amount = '50.00'): Payment
    {
        $payment = new Payment();
        $payment->setAgentBill($agentBill);
        $payment->setPaymentNo($paymentNo);
        $payment->setAmount($amount);
        $payment->setPaymentMethod(PaymentMethodEnum::BANK_TRANSFER);
        $payment->setStatus(PaymentStatusEnum::PENDING);

        return $payment;
    }

    public function testFindByAgentBill(): void
    {
        $agent = $this->createTestAgent();
        self::getEntityManager()->persist($agent);

        $agentBill = $this->createTestAgentBill($agent);
        self::getEntityManager()->persist($agentBill);
        self::getEntityManager()->flush();

        $payment1 = $this->createTestPayment($agentBill, 'PAY001', '500.00');
        $payment1->setPaymentMethod(PaymentMethodEnum::BANK_TRANSFER);
        $payment1->setStatus(PaymentStatusEnum::PENDING);
        self::getEntityManager()->persist($payment1);

        $payment2 = $this->createTestPayment($agentBill, 'PAY002', '500.00');
        $payment2->setPaymentMethod(PaymentMethodEnum::ALIPAY);
        $payment2->setStatus(PaymentStatusEnum::SUCCESS);
        self::getEntityManager()->persist($payment2);

        self::getEntityManager()->flush();

        $results = $this->repository->findByAgentBill($agentBill);
        $this->assertCount(2, $results);
        $this->assertInstanceOf(Payment::class, $results[0]);
        $this->assertInstanceOf(Payment::class, $results[1]);
    }

    public function testFindByStatus(): void
    {
        $agent = $this->createTestAgent();
        self::getEntityManager()->persist($agent);

        $agentBill = $this->createTestAgentBill($agent, '2024-02');
        self::getEntityManager()->persist($agentBill);
        self::getEntityManager()->flush();

        $payment1 = $this->createTestPayment($agentBill, 'PAY003', '1000.00');
        $payment1->setPaymentMethod(PaymentMethodEnum::BANK_TRANSFER);
        $payment1->setStatus(PaymentStatusEnum::PENDING);
        self::getEntityManager()->persist($payment1);

        $payment2 = $this->createTestPayment($agentBill, 'PAY004', '1000.00');
        $payment2->setPaymentMethod(PaymentMethodEnum::WECHAT);
        $payment2->setStatus(PaymentStatusEnum::SUCCESS);
        self::getEntityManager()->persist($payment2);

        self::getEntityManager()->flush();

        $pendingPayments = $this->repository->findByStatus(PaymentStatusEnum::PENDING);
        $this->assertGreaterThanOrEqual(1, count($pendingPayments));

        $completedPayments = $this->repository->findByStatus(PaymentStatusEnum::SUCCESS);
        $this->assertGreaterThanOrEqual(1, count($completedPayments));
    }

    public function testFindByPaymentNo(): void
    {
        $agent = $this->createTestAgent();
        self::getEntityManager()->persist($agent);

        $agentBill = $this->createTestAgentBill($agent, '2024-03');
        self::getEntityManager()->persist($agentBill);
        self::getEntityManager()->flush();

        $payment = $this->createTestPayment($agentBill, 'PAY005', '1500.00');
        $payment->setPaymentMethod(PaymentMethodEnum::CASH);
        $payment->setStatus(PaymentStatusEnum::PENDING);
        self::getEntityManager()->persist($payment);

        self::getEntityManager()->flush();

        $result = $this->repository->findByPaymentNo('PAY005');
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertSame($payment->getId(), $result->getId());

        $notFound = $this->repository->findByPaymentNo('NONEXISTENT');
        $this->assertNull($notFound);
    }

    public function testGetPaymentStatistics(): void
    {
        $agent = $this->createTestAgent();
        self::getEntityManager()->persist($agent);

        $agentBill = $this->createTestAgentBill($agent, '2024-04');
        self::getEntityManager()->persist($agentBill);
        self::getEntityManager()->flush();

        $today = new \DateTimeImmutable();

        $payment1 = $this->createTestPayment($agentBill, 'PAY006', '1000.00');
        $payment1->setPaymentMethod(PaymentMethodEnum::BANK_TRANSFER);
        $payment1->setStatus(PaymentStatusEnum::SUCCESS);
        $payment1->setCreateTime($today);
        self::getEntityManager()->persist($payment1);

        $payment2 = $this->createTestPayment($agentBill, 'PAY007', '2000.00');
        $payment2->setPaymentMethod(PaymentMethodEnum::BANK_TRANSFER);
        $payment2->setStatus(PaymentStatusEnum::SUCCESS);
        $payment2->setCreateTime($today);
        self::getEntityManager()->persist($payment2);

        self::getEntityManager()->flush();

        $startDate = new \DateTimeImmutable('-1 day');
        $endDate = new \DateTimeImmutable('+1 day');
        $results = $this->repository->getPaymentStatistics($startDate, $endDate);

        $this->assertIsArray($results);
        $this->assertGreaterThan(0, count($results));

        $foundBankTransfer = false;
        foreach ($results as $result) {
            if (PaymentMethodEnum::BANK_TRANSFER === $result['paymentMethod'] && PaymentStatusEnum::SUCCESS === $result['status']) {
                $foundBankTransfer = true;
                $this->assertGreaterThanOrEqual(2, $result['payment_count']);
                $this->assertGreaterThanOrEqual(3000.00, $result['total_amount']);
                break;
            }
        }
        $this->assertTrue($foundBankTransfer);
    }

    public function testFindPendingPayments(): void
    {
        $agent = $this->createTestAgent();
        self::getEntityManager()->persist($agent);

        $agentBill = $this->createTestAgentBill($agent, '2024-05');
        self::getEntityManager()->persist($agentBill);
        self::getEntityManager()->flush();

        $payment = $this->createTestPayment($agentBill, 'PAY008', '1200.00');
        $payment->setPaymentMethod(PaymentMethodEnum::ALIPAY);
        $payment->setStatus(PaymentStatusEnum::PENDING);
        self::getEntityManager()->persist($payment);

        self::getEntityManager()->flush();

        $results = $this->repository->findPendingPayments();
        $this->assertGreaterThanOrEqual(1, count($results));

        $found = false;
        foreach ($results as $result) {
            if ($result->getId() === $payment->getId()) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testGetAgentPaymentHistory(): void
    {
        $agent = $this->createTestAgent();
        self::getEntityManager()->persist($agent);

        $agentBill1 = $this->createTestAgentBill($agent, '2024-06');
        self::getEntityManager()->persist($agentBill1);

        $agentBill2 = $this->createTestAgentBill($agent, '2024-07');
        self::getEntityManager()->persist($agentBill2);

        self::getEntityManager()->flush();

        for ($i = 1; $i <= 15; ++$i) {
            $payment = $this->createTestPayment(
                $i <= 10 ? $agentBill1 : $agentBill2,
                'PAY_HIST_' . $i,
                sprintf('%.2f', 100.00 + $i)
            );
            $payment->setPaymentMethod(PaymentMethodEnum::BANK_TRANSFER);
            $payment->setStatus(PaymentStatusEnum::SUCCESS);
            self::getEntityManager()->persist($payment);
        }

        self::getEntityManager()->flush();

        $agentId = $agent->getId();
        $this->assertNotNull($agentId);
        $results = $this->repository->getAgentPaymentHistory($agentId, 5);
        $this->assertCount(5, $results);
        $this->assertInstanceOf(Payment::class, $results[0]);
    }

    public function testFindByAgentBillWithEmptyResult(): void
    {
        $agent = $this->createTestAgent();
        self::getEntityManager()->persist($agent);

        $agentBill = $this->createTestAgentBill($agent, '2024-08');
        $agentBill->setStatus(BillStatusEnum::PENDING);
        self::getEntityManager()->persist($agentBill);

        self::getEntityManager()->flush();

        $results = $this->repository->findByAgentBill($agentBill);
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testGetAgentPaymentHistoryWithEmptyResult(): void
    {
        $results = $this->repository->getAgentPaymentHistory(99999, 10);
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testFindWithExistingId(): void
    {
        $agent = $this->createTestAgent();
        self::getEntityManager()->persist($agent);

        $agentBill = $this->createTestAgentBill($agent);
        self::getEntityManager()->persist($agentBill);
        self::getEntityManager()->flush();

        $payment = $this->createTestPayment($agentBill, 'PAY_FIND_ID');
        self::getEntityManager()->persist($payment);
        self::getEntityManager()->flush();

        $result = $this->repository->find($payment->getId());
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertSame($payment->getId(), $result->getId());
        $this->assertSame('PAY_FIND_ID', $result->getPaymentNo());
    }

    public function testFindWithNonExistentId(): void
    {
        $result = $this->repository->find(99999);
        $this->assertNull($result);
    }

    public function testFindOneByWithNonExistentCriteria(): void
    {
        $result = $this->repository->findOneBy(['paymentNo' => 'NONEXISTENT']);
        $this->assertNull($result);

        $statusResult = $this->repository->findOneBy(['status' => PaymentStatusEnum::CANCELLED]);
        $this->assertNull($statusResult);
    }

    public function testFindByWithOrderByClause(): void
    {
        $agent = $this->createTestAgent();
        self::getEntityManager()->persist($agent);

        $agentBill = $this->createTestAgentBill($agent);
        self::getEntityManager()->persist($agentBill);
        self::getEntityManager()->flush();

        $payment1 = $this->createTestPayment($agentBill, 'PAY_ORDER_1', '100.00');
        $payment1->setPaymentMethod(PaymentMethodEnum::BANK_TRANSFER);
        self::getEntityManager()->persist($payment1);

        $payment2 = $this->createTestPayment($agentBill, 'PAY_ORDER_2', '200.00');
        $payment2->setPaymentMethod(PaymentMethodEnum::BANK_TRANSFER);
        self::getEntityManager()->persist($payment2);

        self::getEntityManager()->flush();

        $resultsAsc = $this->repository->findBy(['paymentMethod' => PaymentMethodEnum::BANK_TRANSFER], ['amount' => 'ASC']);
        $this->assertIsArray($resultsAsc);
        $this->assertGreaterThanOrEqual(2, count($resultsAsc));

        $resultsDesc = $this->repository->findBy(['paymentMethod' => PaymentMethodEnum::BANK_TRANSFER], ['amount' => 'DESC']);
        $this->assertIsArray($resultsDesc);
        $this->assertGreaterThanOrEqual(2, count($resultsDesc));
    }

    public function testFindByWithLimitAndOffset(): void
    {
        $agent = $this->createTestAgent();
        self::getEntityManager()->persist($agent);

        $agentBill = $this->createTestAgentBill($agent);
        self::getEntityManager()->persist($agentBill);
        self::getEntityManager()->flush();

        for ($i = 1; $i <= 5; ++$i) {
            $payment = $this->createTestPayment($agentBill, 'PAY_LIMIT_' . $i, sprintf('%.2f', $i * 100));
            $payment->setPaymentMethod(PaymentMethodEnum::ALIPAY);
            self::getEntityManager()->persist($payment);
        }
        self::getEntityManager()->flush();

        $results = $this->repository->findBy(['paymentMethod' => PaymentMethodEnum::ALIPAY], ['amount' => 'ASC'], 2, 1);
        $this->assertIsArray($results);
        $this->assertLessThanOrEqual(2, count($results));
    }

    public function testSaveMethodShouldPersistEntity(): void
    {
        $agent = $this->createTestAgent();
        self::getEntityManager()->persist($agent);

        $agentBill = $this->createTestAgentBill($agent);
        self::getEntityManager()->persist($agentBill);
        self::getEntityManager()->flush();

        $payment = $this->createTestPayment($agentBill, 'PAY_SAVE', '300.00');
        $payment->setPaymentMethod(PaymentMethodEnum::WECHAT);
        $payment->setStatus(PaymentStatusEnum::SUCCESS);

        $this->repository->save($payment);

        $savedPayment = $this->repository->findByPaymentNo('PAY_SAVE');
        $this->assertInstanceOf(Payment::class, $savedPayment);
        $this->assertSame($agentBill->getId(), $savedPayment->getAgentBill()->getId());
        $this->assertSame('300.00', $savedPayment->getAmount());
        $this->assertSame(PaymentMethodEnum::WECHAT, $savedPayment->getPaymentMethod());
    }

    public function testSaveMethodWithoutFlush(): void
    {
        $agent = $this->createTestAgent();
        self::getEntityManager()->persist($agent);

        $agentBill = $this->createTestAgentBill($agent);
        self::getEntityManager()->persist($agentBill);
        self::getEntityManager()->flush();

        $payment = $this->createTestPayment($agentBill, 'PAY_SAVE_NO_FLUSH', '400.00');
        $payment->setPaymentMethod(PaymentMethodEnum::CASH);

        $this->repository->save($payment, false);
        self::getEntityManager()->flush();

        $savedPayment = $this->repository->findByPaymentNo('PAY_SAVE_NO_FLUSH');
        $this->assertInstanceOf(Payment::class, $savedPayment);
        $this->assertSame('400.00', $savedPayment->getAmount());
    }

    public function testRemoveMethodShouldDeleteEntity(): void
    {
        $agent = $this->createTestAgent();
        self::getEntityManager()->persist($agent);

        $agentBill = $this->createTestAgentBill($agent);
        self::getEntityManager()->persist($agentBill);
        self::getEntityManager()->flush();

        $payment = $this->createTestPayment($agentBill, 'PAY_REMOVE');
        self::getEntityManager()->persist($payment);
        self::getEntityManager()->flush();

        $savedPayment = $this->repository->findByPaymentNo('PAY_REMOVE');
        $this->assertInstanceOf(Payment::class, $savedPayment);

        $this->repository->remove($savedPayment);

        $deletedPayment = $this->repository->findByPaymentNo('PAY_REMOVE');
        $this->assertNull($deletedPayment);
    }

    public function testRemoveMethodWithoutFlush(): void
    {
        $agent = $this->createTestAgent();
        self::getEntityManager()->persist($agent);

        $agentBill = $this->createTestAgentBill($agent);
        self::getEntityManager()->persist($agentBill);
        self::getEntityManager()->flush();

        $payment = $this->createTestPayment($agentBill, 'PAY_REMOVE_NO_FLUSH');
        self::getEntityManager()->persist($payment);
        self::getEntityManager()->flush();

        $savedPayment = $this->repository->findByPaymentNo('PAY_REMOVE_NO_FLUSH');
        $this->assertInstanceOf(Payment::class, $savedPayment);

        $this->repository->remove($savedPayment, false);
        self::getEntityManager()->flush();

        $deletedPayment = $this->repository->findByPaymentNo('PAY_REMOVE_NO_FLUSH');
        $this->assertNull($deletedPayment);
    }

    public function testCountWithCriteria(): void
    {
        $agent = $this->createTestAgent();
        self::getEntityManager()->persist($agent);

        $agentBill = $this->createTestAgentBill($agent);
        self::getEntityManager()->persist($agentBill);
        self::getEntityManager()->flush();

        $initialCount = $this->repository->count(['agentBill' => $agentBill]);

        $payment1 = $this->createTestPayment($agentBill, 'PAY_COUNT_1');
        $payment1->setStatus(PaymentStatusEnum::SUCCESS);
        self::getEntityManager()->persist($payment1);

        $payment2 = $this->createTestPayment($agentBill, 'PAY_COUNT_2');
        $payment2->setStatus(PaymentStatusEnum::SUCCESS);
        self::getEntityManager()->persist($payment2);

        self::getEntityManager()->flush();

        $finalCount = $this->repository->count(['agentBill' => $agentBill]);
        $this->assertSame($initialCount + 2, $finalCount);

        $successCount = $this->repository->count(['status' => PaymentStatusEnum::SUCCESS]);
        $this->assertGreaterThanOrEqual(2, $successCount);
    }

    public function testFindByNullableFields(): void
    {
        $agent = $this->createTestAgent();
        self::getEntityManager()->persist($agent);

        $agentBill = $this->createTestAgentBill($agent);
        self::getEntityManager()->persist($agentBill);
        self::getEntityManager()->flush();

        $payment = $this->createTestPayment($agentBill, 'PAY_NULL_FIELDS');
        $payment->setTransactionId(null);
        $payment->setPaymentProofUrl(null);
        $payment->setRemarks(null);
        $payment->setFailureReason(null);
        self::getEntityManager()->persist($payment);
        self::getEntityManager()->flush();

        $results = $this->repository->findBy(['agentBill' => $agentBill]);
        $this->assertGreaterThanOrEqual(1, count($results));

        $resultWithNullFields = false;
        foreach ($results as $result) {
            if (null === $result->getTransactionId() && null === $result->getPaymentProofUrl()) {
                $resultWithNullFields = true;
                break;
            }
        }
        $this->assertTrue($resultWithNullFields);
    }

    public function testFindOneByAssociationAgentBillShouldReturnMatchingEntity(): void
    {
        $agent = $this->createTestAgent();
        self::getEntityManager()->persist($agent);

        $agentBill = $this->createTestAgentBill($agent, '2024-10');
        self::getEntityManager()->persist($agentBill);
        self::getEntityManager()->flush();

        $payment = $this->createTestPayment($agentBill, 'PAY_FIND_ONE_BY_BILL', '300.00');
        self::getEntityManager()->persist($payment);
        self::getEntityManager()->flush();

        $result = $this->repository->findOneBy(['agentBill' => $agentBill]);
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertSame($agentBill->getId(), $result->getAgentBill()->getId());
        $this->assertSame('PAY_FIND_ONE_BY_BILL', $result->getPaymentNo());
    }

    public function testCountByAssociationAgentBillShouldReturnCorrectNumber(): void
    {
        $agent = $this->createTestAgent();
        self::getEntityManager()->persist($agent);

        $agentBill = $this->createTestAgentBill($agent, '2024-21');
        self::getEntityManager()->persist($agentBill);
        self::getEntityManager()->flush();

        $initialCount = $this->repository->count(['agentBill' => $agentBill]);

        for ($i = 1; $i <= 4; ++$i) {
            $payment = $this->createTestPayment($agentBill, 'PAY_COUNT_BILL_' . $i, sprintf('%.2f', $i * 25));
            self::getEntityManager()->persist($payment);
        }
        self::getEntityManager()->flush();

        $count = $this->repository->count(['agentBill' => $agentBill]);
        $this->assertSame($initialCount + 4, $count);
    }

    /**
     * @return ServiceEntityRepository<Payment>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): object
    {
        // 创建必需的 Agent 实体
        $agent = $this->createTestAgent();
        self::getEntityManager()->persist($agent);

        // 创建必需的 AgentBill 实体
        $agentBill = $this->createTestAgentBill($agent, '2024-' . sprintf('%02d', rand(1, 12)));
        self::getEntityManager()->persist($agentBill);
        self::getEntityManager()->flush();

        // 创建 Payment 实体
        $payment = $this->createTestPayment($agentBill, 'PAY' . uniqid(), sprintf('%.2f', rand(50, 500)));
        $payment->setPaymentMethod(PaymentMethodEnum::BANK_TRANSFER);
        $payment->setStatus(PaymentStatusEnum::PENDING);

        return $payment;
    }
}
