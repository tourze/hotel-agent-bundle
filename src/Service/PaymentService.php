<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Service;

use Brick\Math\BigDecimal;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Entity\Payment;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;
use Tourze\HotelAgentBundle\Enum\PaymentMethodEnum;
use Tourze\HotelAgentBundle\Enum\PaymentStatusEnum;
use Tourze\HotelAgentBundle\Exception\PaymentProcessingException;
use Tourze\HotelAgentBundle\Repository\PaymentRepository;

/**
 * 支付管理服务
 */
#[WithMonologChannel(channel: 'hotel_agent')]
readonly class PaymentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PaymentRepository $paymentRepository,
        private LoggerInterface $logger,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    /**
     * 创建支付记录
     */
    public function createPayment(
        AgentBill $agentBill,
        string $amount,
        PaymentMethodEnum $paymentMethod,
        ?string $remarks = null,
    ): Payment {
        // 验证账单状态
        if (BillStatusEnum::CONFIRMED !== $agentBill->getStatus()) {
            throw new PaymentProcessingException('只有已确认的账单才能创建支付记录');
        }

        // 验证金额
        if (BigDecimal::of($amount)->compareTo(BigDecimal::zero()) <= 0) {
            throw new PaymentProcessingException('支付金额必须大于0');
        }

        if (BigDecimal::of($amount)->compareTo(BigDecimal::of($agentBill->getCommissionAmount())) > 0) {
            throw new PaymentProcessingException('支付金额不能超过应付佣金');
        }

        $payment = new Payment();
        $payment->setAgentBill($agentBill);
        $payment->setAmount($amount);
        $payment->setPaymentMethod($paymentMethod);
        $payment->setStatus(PaymentStatusEnum::PENDING);
        $payment->setRemarks($remarks);
        $payment->generatePaymentNo();

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        $this->logger->info('创建支付记录', [
            'paymentId' => $payment->getId(),
            'billId' => $agentBill->getId(),
            'amount' => $amount,
            'paymentMethod' => $paymentMethod->value,
        ]);

        return $payment;
    }

    /**
     * 处理支付成功
     */
    public function processPaymentSuccess(
        Payment $payment,
        ?string $transactionId = null,
        ?string $paymentProofUrl = null,
    ): bool {
        if (PaymentStatusEnum::PENDING !== $payment->getStatus()) {
            $this->logger->warning('支付状态不正确', [
                'paymentId' => $payment->getId(),
                'currentStatus' => $payment->getStatus()->value,
            ]);

            return false;
        }

        $payment->markAsSuccess($transactionId);

        if (null !== $paymentProofUrl) {
            $payment->setPaymentProofUrl($paymentProofUrl);
        }

        // 检查是否完全支付
        if ($this->isFullyPaid($payment->getAgentBill())) {
            $payment->getAgentBill()->markAsPaid($transactionId);
        }

        $this->entityManager->flush();

        $this->logger->info('支付处理成功', [
            'paymentId' => $payment->getId(),
            'transactionId' => $transactionId,
        ]);

        return true;
    }

    /**
     * 处理支付失败
     */
    public function processPaymentFailure(Payment $payment, string $failureReason): bool
    {
        if (PaymentStatusEnum::PENDING !== $payment->getStatus()) {
            return false;
        }

        $payment->markAsFailed($failureReason);
        $this->entityManager->flush();

        $this->logger->info('支付处理失败', [
            'paymentId' => $payment->getId(),
            'failureReason' => $failureReason,
        ]);

        return true;
    }

    /**
     * 确认支付
     */
    public function confirmPayment(Payment $payment): bool
    {
        if (PaymentStatusEnum::SUCCESS !== $payment->getStatus()) {
            $this->logger->warning('只有成功的支付才能确认', [
                'paymentId' => $payment->getId(),
                'currentStatus' => $payment->getStatus()->value,
            ]);

            return false;
        }

        $payment->confirm();
        $this->entityManager->flush();

        $this->logger->info('支付已确认', ['paymentId' => $payment->getId()]);

        return true;
    }

    /**
     * 检查账单是否完全支付
     */
    private function isFullyPaid(AgentBill $agentBill): bool
    {
        /** @var Payment[] $successfulPayments */
        $successfulPayments = $this->paymentRepository->createQueryBuilder('p')
            ->andWhere('p.agentBill = :agentBill')
            ->andWhere('p.status = :status')
            ->setParameter('agentBill', $agentBill)
            ->setParameter('status', PaymentStatusEnum::SUCCESS)
            ->getQuery()
            ->getResult()
        ;

        $totalPaid = BigDecimal::zero();
        foreach ($successfulPayments as $payment) {
            $totalPaid = $totalPaid->plus(BigDecimal::of($payment->getAmount()));
        }

        return $totalPaid->compareTo(BigDecimal::of($agentBill->getCommissionAmount())) >= 0;
    }

    /**
     * 获取支付统计数据
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPaymentStatistics(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->paymentRepository->getPaymentStatistics($startDate, $endDate);
    }

    /**
     * 获取待处理的支付记录
     *
     * @return Payment[]
     */
    public function getPendingPayments(): array
    {
        return $this->paymentRepository->findPendingPayments();
    }

    /**
     * 批量处理支付
     *
     * @param int[] $paymentIds
     * @return array<int, array<string, mixed>>
     */
    public function batchProcessPayments(array $paymentIds, PaymentStatusEnum $targetStatus, ?string $remarks = null): array
    {
        $results = [];

        foreach ($paymentIds as $paymentId) {
            $payment = $this->paymentRepository->find($paymentId);
            if (null === $payment) {
                $results[$paymentId] = ['success' => false, 'message' => '支付记录不存在'];
                continue;
            }

            try {
                switch ($targetStatus) {
                    case PaymentStatusEnum::SUCCESS:
                        $success = $this->processPaymentSuccess($payment);
                        break;
                    case PaymentStatusEnum::FAILED:
                        $success = $this->processPaymentFailure($payment, $remarks ?? '批量处理');
                        break;
                    default:
                        $success = false;
                        break;
                }

                $results[$paymentId] = [
                    'success' => $success,
                    'message' => $success ? '处理成功' : '处理失败',
                ];
            } catch (\Throwable $e) {
                $results[$paymentId] = ['success' => false, 'message' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * 根据支付方式获取支付配置
     *
     * @return array<string, mixed>
     */
    public function getPaymentConfig(PaymentMethodEnum $paymentMethod): array
    {
        $config = $this->parameterBag->get('payment_config');

        if (!is_array($config)) {
            /** @var array<string, mixed> */
            return [];
        }

        $methodConfig = $config[$paymentMethod->value] ?? [];

        /** @var array<string, mixed> */
        return is_array($methodConfig) ? $methodConfig : [];
    }

    /**
     * 验证支付参数
     *
     * @param array<string, mixed> $params
     */
    public function validatePaymentParams(PaymentMethodEnum $paymentMethod, array $params): bool
    {
        $config = $this->getPaymentConfig($paymentMethod);

        // 检查必需参数
        $requiredParams = $config['required_params'] ?? [];
        if (!is_array($requiredParams)) {
            return true;
        }

        foreach ($requiredParams as $param) {
            if (!is_string($param)) {
                continue;
            }
            if (!isset($params[$param]) || '' === $params[$param]) {
                return false;
            }
        }

        return true;
    }

    /**
     * 生成支付报表
     *
     * @return array<string, mixed>
     */
    public function generatePaymentReport(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $qb = $this->paymentRepository->createQueryBuilder('p')
            ->leftJoin('p.agentBill', 'ab')
            ->leftJoin('ab.agent', 'a')
            ->andWhere('p.createTime BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('p.createTime', 'DESC')
        ;

        /** @var Payment[] $payments */
        $payments = $qb->getQuery()->getResult();

        $report = [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'total_count' => 0,
                'total_amount' => '0.00',
                'success_count' => 0,
                'success_amount' => '0.00',
                'failed_count' => 0,
                'pending_count' => 0,
            ],
            'by_method' => [],
            'by_agent' => [],
            'payments' => [],
        ];

        foreach ($payments as $payment) {
            ++$report['summary']['total_count'];
            $report['summary']['total_amount'] = BigDecimal::of($report['summary']['total_amount'])
                ->plus(BigDecimal::of($payment->getAmount()))->toScale(2)->__toString()
            ;

            switch ($payment->getStatus()) {
                case PaymentStatusEnum::SUCCESS:
                    $report['summary']['success_count']++;
                    $report['summary']['success_amount'] = BigDecimal::of($report['summary']['success_amount'])
                        ->plus(BigDecimal::of($payment->getAmount()))->toScale(2)->__toString()
                    ;
                    break;
                case PaymentStatusEnum::FAILED:
                    $report['summary']['failed_count']++;
                    break;
                case PaymentStatusEnum::PENDING:
                    $report['summary']['pending_count']++;
                    break;
            }

            // 按支付方式统计
            $method = $payment->getPaymentMethod()->value;
            if (!isset($report['by_method'][$method])) {
                $report['by_method'][$method] = ['count' => 0, 'amount' => '0.00'];
            }
            ++$report['by_method'][$method]['count'];
            $report['by_method'][$method]['amount'] = BigDecimal::of($report['by_method'][$method]['amount'])
                ->plus(BigDecimal::of($payment->getAmount()))->toScale(2)->__toString()
            ;

            // 按代理统计
            $agent = $payment->getAgentBill()->getAgent();
            if (null !== $agent) {
                $agentId = $agent->getId();
                if (!isset($report['by_agent'][$agentId])) {
                    $report['by_agent'][$agentId] = [
                        'agent_name' => $agent->getCompanyName(),
                        'count' => 0,
                        'amount' => '0.00',
                    ];
                }
                ++$report['by_agent'][$agentId]['count'];
                $report['by_agent'][$agentId]['amount'] = BigDecimal::of($report['by_agent'][$agentId]['amount'])
                    ->plus(BigDecimal::of($payment->getAmount()))->toScale(2)->__toString()
                ;
            }

            $report['payments'][] = [
                'id' => $payment->getId(),
                'payment_no' => $payment->getPaymentNo(),
                'amount' => $payment->getAmount(),
                'method' => $payment->getPaymentMethod()->getLabel(),
                'status' => $payment->getStatus()->getLabel(),
                'agent' => $payment->getAgentBill()->getAgent()?->getCompanyName() ?? 'Unknown',
                'create_time' => $payment->getCreateTime()?->format('Y-m-d H:i:s') ?? 'Unknown',
            ];
        }

        return $report;
    }
}
