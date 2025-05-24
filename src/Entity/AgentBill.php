<?php

namespace Tourze\HotelAgentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineUserBundle\Attribute\CreatedByColumn;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;
use Tourze\HotelAgentBundle\Enum\SettlementTypeEnum;
use Tourze\HotelAgentBundle\Repository\AgentBillRepository;

#[ORM\Entity(repositoryClass: AgentBillRepository::class)]
#[ORM\Table(name: 'agent_bill', options: ['comment' => '代理账单表'])]
#[ORM\Index(name: 'agent_bill_idx_agent_month', columns: ['agent_id', 'bill_month'])]
#[ORM\Index(name: 'agent_bill_idx_status', columns: ['status'])]
class AgentBill implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Agent::class, inversedBy: 'bills')]
    #[ORM\JoinColumn(name: 'agent_id', referencedColumnName: 'id', nullable: false)]
    private Agent $agent;

    #[ORM\Column(type: Types::STRING, length: 7, options: ['comment' => '账单月份，格式：yyyy-MM'])]
    private string $billMonth = '';

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '账单中的订单数'])]
    private int $orderCount = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, options: ['comment' => '订单总金额'])]
    private string $totalAmount = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, options: ['comment' => '佣金总额'])]
    private string $commissionAmount = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['comment' => '结算时的佣金比例'])]
    private string $commissionRate = '0.00';

    #[ORM\Column(type: Types::STRING, length: 20, enumType: SettlementTypeEnum::class, options: ['comment' => '结算类型'])]
    private SettlementTypeEnum $settlementType = SettlementTypeEnum::MONTHLY;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: BillStatusEnum::class, options: ['comment' => '账单状态'])]
    private BillStatusEnum $status = BillStatusEnum::PENDING;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '确认时间'])]
    private ?\DateTimeInterface $confirmTime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '支付时间'])]
    private ?\DateTimeInterface $payTime = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '支付凭证号'])]
    private ?string $paymentReference = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '备注'])]
    private ?string $remarks = null;

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
        $agentCode = $this->agent->getCode();
        return sprintf('账单 %s (%s)', $this->billMonth, $agentCode);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAgent(): Agent
    {
        return $this->agent;
    }

    public function setAgent(Agent $agent): self
    {
        $this->agent = $agent;
        return $this;
    }

    public function getBillMonth(): string
    {
        return $this->billMonth;
    }

    public function setBillMonth(string $billMonth): self
    {
        $this->billMonth = $billMonth;
        return $this;
    }

    public function getOrderCount(): int
    {
        return $this->orderCount;
    }

    public function setOrderCount(int $orderCount): self
    {
        $this->orderCount = $orderCount;
        return $this;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): self
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    public function getCommissionAmount(): string
    {
        return $this->commissionAmount;
    }

    public function setCommissionAmount(string $commissionAmount): self
    {
        $this->commissionAmount = $commissionAmount;
        return $this;
    }

    public function getCommissionRate(): string
    {
        return $this->commissionRate;
    }

    public function setCommissionRate(string $commissionRate): self
    {
        $this->commissionRate = $commissionRate;
        return $this;
    }

    public function getSettlementType(): SettlementTypeEnum
    {
        return $this->settlementType;
    }

    public function setSettlementType(SettlementTypeEnum $settlementType): self
    {
        $this->settlementType = $settlementType;
        return $this;
    }

    public function getStatus(): BillStatusEnum
    {
        return $this->status;
    }

    public function setStatus(BillStatusEnum $status): self
    {
        $this->status = $status;
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

    public function getPayTime(): ?\DateTimeInterface
    {
        return $this->payTime;
    }

    public function setPayTime(?\DateTimeInterface $payTime): self
    {
        $this->payTime = $payTime;
        return $this;
    }

    public function getPaymentReference(): ?string
    {
        return $this->paymentReference;
    }

    public function setPaymentReference(?string $paymentReference): self
    {
        $this->paymentReference = $paymentReference;
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
     * 确认账单
     */
    public function confirm(): self
    {
        if ($this->status === BillStatusEnum::PENDING) {
            $this->status = BillStatusEnum::CONFIRMED;
            $this->confirmTime = new \DateTime();
        }
        return $this;
    }

    /**
     * 标记为已支付
     */
    public function markAsPaid(?string $paymentReference = null): self
    {
        if ($this->status === BillStatusEnum::CONFIRMED) {
            $this->status = BillStatusEnum::PAID;
            $this->payTime = new \DateTime();
            if ($paymentReference) {
                $this->paymentReference = $paymentReference;
            }
        }
        return $this;
    }

    /**
     * 计算佣金金额
     */
    public function calculateCommission(): self
    {
        $commissionRate = (float)$this->commissionRate;
        $totalAmount = (float)$this->totalAmount;
        $commissionAmount = round($totalAmount * $commissionRate, 2);
        $this->commissionAmount = (string)$commissionAmount;
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
