<?php

namespace Tourze\HotelAgentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineUserBundle\Attribute\CreatedByColumn;
use Tourze\HotelAgentBundle\Enum\PaymentMethodEnum;
use Tourze\HotelAgentBundle\Enum\PaymentStatusEnum;
use Tourze\HotelAgentBundle\Repository\PaymentRepository;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Table(name: 'hotel_agent_payment', options: ['comment' => '支付记录表'])]
#[ORM\Index(name: 'payment_idx_bill', columns: ['agent_bill_id'])]
#[ORM\Index(name: 'payment_idx_status', columns: ['status'])]
#[ORM\Index(name: 'payment_idx_method', columns: ['payment_method'])]
class Payment implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AgentBill::class)]
    #[ORM\JoinColumn(name: 'agent_bill_id', referencedColumnName: 'id', nullable: false)]
    private AgentBill $agentBill;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '支付单号'])]
    private string $paymentNo = '';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, options: ['comment' => '支付金额'])]
    private string $amount = '0.00';

    #[ORM\Column(type: Types::STRING, length: 20, enumType: PaymentMethodEnum::class, options: ['comment' => '支付方式'])]
    private PaymentMethodEnum $paymentMethod = PaymentMethodEnum::BANK_TRANSFER;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: PaymentStatusEnum::class, options: ['comment' => '支付状态'])]
    private PaymentStatusEnum $status = PaymentStatusEnum::PENDING;

    #[ORM\Column(type: Types::STRING, length: 200, nullable: true, options: ['comment' => '第三方交易号'])]
    private ?string $transactionId = null;

    #[ORM\Column(type: Types::STRING, length: 200, nullable: true, options: ['comment' => '支付凭证URL'])]
    private ?string $paymentProofUrl = null;

    #[ORM\Column(type: Types::STRING, length: 200, nullable: true, options: ['comment' => '电子签章URL'])]
    private ?string $digitalSignatureUrl = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '支付时间'])]
    private ?\DateTimeInterface $paymentTime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '确认时间'])]
    private ?\DateTimeInterface $confirmTime = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '备注'])]
    private ?string $remarks = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '失败原因'])]
    private ?string $failureReason = null;

    #[CreateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createTime = null;

    #[UpdateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updateTime = null;

    #[CreatedByColumn]
    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $createdBy = null;

    public function __toString(): string
    {
        return sprintf('支付 %s (￥%s)', $this->paymentNo, $this->amount);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAgentBill(): AgentBill
    {
        return $this->agentBill;
    }

    public function setAgentBill(AgentBill $agentBill): self
    {
        $this->agentBill = $agentBill;
        return $this;
    }

    public function getPaymentNo(): string
    {
        return $this->paymentNo;
    }

    public function setPaymentNo(string $paymentNo): self
    {
        $this->paymentNo = $paymentNo;
        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getPaymentMethod(): PaymentMethodEnum
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(PaymentMethodEnum $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getStatus(): PaymentStatusEnum
    {
        return $this->status;
    }

    public function setStatus(PaymentStatusEnum $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(?string $transactionId): self
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    public function getPaymentProofUrl(): ?string
    {
        return $this->paymentProofUrl;
    }

    public function setPaymentProofUrl(?string $paymentProofUrl): self
    {
        $this->paymentProofUrl = $paymentProofUrl;
        return $this;
    }

    public function getDigitalSignatureUrl(): ?string
    {
        return $this->digitalSignatureUrl;
    }

    public function setDigitalSignatureUrl(?string $digitalSignatureUrl): self
    {
        $this->digitalSignatureUrl = $digitalSignatureUrl;
        return $this;
    }

    public function getPaymentTime(): ?\DateTimeInterface
    {
        return $this->paymentTime;
    }

    public function setPaymentTime(?\DateTimeInterface $paymentTime): self
    {
        $this->paymentTime = $paymentTime;
        return $this;
    }

    public function getConfirmTime(): ?\DateTimeInterface
    {
        return $this->confirmTime;
    }

    public function setConfirmTime(?\DateTimeInterface $confirmTime): self
    {
        $this->confirmTime = $confirmTime;
        return $this;
    }

    public function getRemarks(): ?string
    {
        return $this->remarks;
    }

    public function setRemarks(?string $remarks): self
    {
        $this->remarks = $remarks;
        return $this;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function setFailureReason(?string $failureReason): self
    {
        $this->failureReason = $failureReason;
        return $this;
    }

    public function getCreateTime(): ?\DateTimeInterface
    {
        return $this->createTime;
    }

    public function getUpdateTime(): ?\DateTimeInterface
    {
        return $this->updateTime;
    }

    public function getCreatedBy(): ?int
    {
        return $this->createdBy;
    }

    /**
     * 标记支付成功
     */
    public function markAsSuccess(?string $transactionId = null): self
    {
        $this->status = PaymentStatusEnum::SUCCESS;
        $this->paymentTime = new \DateTime();
        if ($transactionId) {
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
        if ($this->status === PaymentStatusEnum::SUCCESS) {
            $this->confirmTime = new \DateTime();
        }
        return $this;
    }

    /**
     * 生成支付单号
     */
    public function generatePaymentNo(): self
    {
        if (empty($this->paymentNo)) {
            $this->paymentNo = 'PAY' . date('YmdHis') . rand(1000, 9999);
        }
        return $this;
    }

    public function setCreateTime(?\DateTimeInterface $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function setUpdateTime(?\DateTimeInterface $updateTime): void
    {
        $this->updateTime = $updateTime;
    }
} 