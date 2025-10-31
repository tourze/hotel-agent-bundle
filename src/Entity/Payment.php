<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\CreatedByAware;
use Tourze\HotelAgentBundle\Enum\PaymentMethodEnum;
use Tourze\HotelAgentBundle\Enum\PaymentStatusEnum;
use Tourze\HotelAgentBundle\Repository\PaymentRepository;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Table(name: 'hotel_agent_payment', options: ['comment' => '支付记录表'])]
class Payment implements \Stringable
{
    use TimestampableAware;
    use CreatedByAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => '主键ID'])]
    private int $id = 0;

    #[ORM\ManyToOne(targetEntity: AgentBill::class)]
    #[ORM\JoinColumn(name: 'agent_bill_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull]
    private AgentBill $agentBill;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '支付单号'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private string $paymentNo = '';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, options: ['comment' => '支付金额'])]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    #[Assert\Length(max: 12)]
    private string $amount = '0.00';

    #[ORM\Column(type: Types::STRING, length: 20, enumType: PaymentMethodEnum::class, options: ['comment' => '支付方式'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [PaymentMethodEnum::class, 'cases'])]
    #[IndexColumn]
    private PaymentMethodEnum $paymentMethod = PaymentMethodEnum::BANK_TRANSFER;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: PaymentStatusEnum::class, options: ['comment' => '支付状态'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [PaymentStatusEnum::class, 'cases'])]
    #[IndexColumn]
    private PaymentStatusEnum $status = PaymentStatusEnum::PENDING;

    #[ORM\Column(type: Types::STRING, length: 200, nullable: true, options: ['comment' => '第三方交易号'])]
    #[Assert\Length(max: 200)]
    private ?string $transactionId = null;

    #[ORM\Column(type: Types::STRING, length: 200, nullable: true, options: ['comment' => '支付凭证URL'])]
    #[Assert\Url]
    #[Assert\Length(max: 200)]
    private ?string $paymentProofUrl = null;

    #[ORM\Column(type: Types::STRING, length: 200, nullable: true, options: ['comment' => '电子签章URL'])]
    #[Assert\Url]
    #[Assert\Length(max: 200)]
    private ?string $digitalSignatureUrl = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '支付时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    private ?\DateTimeImmutable $paymentTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '确认时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    private ?\DateTimeImmutable $confirmTime = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '备注'])]
    #[Assert\Length(max: 65535)]
    private ?string $remarks = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '失败原因'])]
    #[Assert\Length(max: 65535)]
    private ?string $failureReason = null;

    public function __toString(): string
    {
        return sprintf('支付 %s (￥%s)', $this->paymentNo, $this->amount);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAgentBill(): AgentBill
    {
        return $this->agentBill;
    }

    public function setAgentBill(AgentBill $agentBill): void
    {
        $this->agentBill = $agentBill;
    }

    public function getPaymentNo(): string
    {
        return $this->paymentNo;
    }

    public function setPaymentNo(string $paymentNo): void
    {
        $this->paymentNo = $paymentNo;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): void
    {
        $this->amount = $amount;
    }

    public function getPaymentMethod(): PaymentMethodEnum
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(PaymentMethodEnum $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getStatus(): PaymentStatusEnum
    {
        return $this->status;
    }

    public function setStatus(PaymentStatusEnum $status): void
    {
        $this->status = $status;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(?string $transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    public function getPaymentProofUrl(): ?string
    {
        return $this->paymentProofUrl;
    }

    public function setPaymentProofUrl(?string $paymentProofUrl): void
    {
        $this->paymentProofUrl = $paymentProofUrl;
    }

    public function getDigitalSignatureUrl(): ?string
    {
        return $this->digitalSignatureUrl;
    }

    public function setDigitalSignatureUrl(?string $digitalSignatureUrl): void
    {
        $this->digitalSignatureUrl = $digitalSignatureUrl;
    }

    public function getPaymentTime(): ?\DateTimeImmutable
    {
        return $this->paymentTime;
    }

    public function setPaymentTime(?\DateTimeImmutable $paymentTime): void
    {
        $this->paymentTime = $paymentTime;
    }

    public function getConfirmTime(): ?\DateTimeImmutable
    {
        return $this->confirmTime;
    }

    public function setConfirmTime(?\DateTimeImmutable $confirmTime): void
    {
        $this->confirmTime = $confirmTime;
    }

    public function getRemarks(): ?string
    {
        return $this->remarks;
    }

    public function setRemarks(?string $remarks): void
    {
        $this->remarks = $remarks;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function setFailureReason(?string $failureReason): void
    {
        $this->failureReason = $failureReason;
    }

    /**
     * 标记支付成功
     */
    public function markAsSuccess(?string $transactionId = null): self
    {
        $this->status = PaymentStatusEnum::SUCCESS;
        $this->paymentTime = new \DateTimeImmutable();
        if (null !== $transactionId) {
            $this->transactionId = $transactionId;
        }

        return $this;
    }

    /**
     * 标记支付失败
     */
    public function markAsFailed(string $failureReason): self
    {
        $this->status = PaymentStatusEnum::FAILED;
        $this->failureReason = $failureReason;

        return $this;
    }

    /**
     * 确认支付
     */
    public function confirm(): self
    {
        if (PaymentStatusEnum::SUCCESS === $this->status) {
            $this->confirmTime = new \DateTimeImmutable();
        }

        return $this;
    }

    /**
     * 生成支付单号
     */
    public function generatePaymentNo(): self
    {
        if ('' === $this->paymentNo) {
            $this->paymentNo = 'PAY' . date('YmdHis') . rand(1000, 9999);
        }

        return $this;
    }
}
