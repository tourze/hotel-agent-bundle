<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Entity;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\CreatedByAware;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;
use Tourze\HotelAgentBundle\Enum\SettlementTypeEnum;
use Tourze\HotelAgentBundle\Repository\AgentBillRepository;

#[ORM\Entity(repositoryClass: AgentBillRepository::class)]
#[ORM\Table(name: 'agent_bill', options: ['comment' => '代理账单表'])]
#[ORM\Index(name: 'agent_bill_idx_agent_month', columns: ['agent_id', 'bill_month'])]
class AgentBill implements \Stringable
{
    use TimestampableAware;
    use CreatedByAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => '主键ID'])]
    private int $id = 0;

    #[ORM\ManyToOne(targetEntity: Agent::class, inversedBy: 'bills')]
    #[ORM\JoinColumn(name: 'agent_id', referencedColumnName: 'id', nullable: true)]
    private ?Agent $agent = null;

    #[ORM\Column(type: Types::STRING, length: 7, options: ['comment' => '账单月份，格式：yyyy-MM'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 7)]
    #[Assert\Regex(pattern: '/^\d{4}-\d{2}$/', message: '账单月份格式应为 yyyy-MM')]
    private string $billMonth = '';

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '账单中的订单数'])]
    #[Assert\PositiveOrZero]
    private int $orderCount = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, options: ['comment' => '订单总金额'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 15)]
    #[Assert\Regex(pattern: '/^\d+\.\d{2}$/', message: '金额格式不正确')]
    private string $totalAmount = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, options: ['comment' => '佣金总额'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 15)]
    #[Assert\Regex(pattern: '/^\d+\.\d{2}$/', message: '佣金金额格式不正确')]
    private string $commissionAmount = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['comment' => '结算时的佣金比例'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 6)]
    #[Assert\Range(min: 0, max: 1)]
    private string $commissionRate = '0.00';

    #[ORM\Column(type: Types::STRING, length: 20, enumType: SettlementTypeEnum::class, options: ['comment' => '结算类型'])]
    #[Assert\Choice(callback: [SettlementTypeEnum::class, 'cases'])]
    private SettlementTypeEnum $settlementType = SettlementTypeEnum::MONTHLY;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: BillStatusEnum::class, options: ['comment' => '账单状态'])]
    #[Assert\Choice(callback: [BillStatusEnum::class, 'cases'])]
    #[IndexColumn]
    private BillStatusEnum $status = BillStatusEnum::PENDING;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '确认时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    private ?\DateTimeImmutable $confirmTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '支付时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    private ?\DateTimeImmutable $payTime = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '支付凭证号'])]
    #[Assert\Length(max: 100)]
    private ?string $paymentReference = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '备注'])]
    #[Assert\Length(max: 65535)]
    private ?string $remarks = null;

    public function __toString(): string
    {
        $agentCode = null !== $this->agent ? $this->agent->getCode() : 'N/A';

        return sprintf('账单 %s (%s)', $this->billMonth, $agentCode);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAgent(): ?Agent
    {
        return $this->agent;
    }

    public function setAgent(?Agent $agent): void
    {
        $this->agent = $agent;
    }

    public function getBillMonth(): string
    {
        return $this->billMonth;
    }

    public function setBillMonth(string $billMonth): void
    {
        $this->billMonth = $billMonth;
    }

    public function getOrderCount(): int
    {
        return $this->orderCount;
    }

    public function setOrderCount(int $orderCount): void
    {
        $this->orderCount = $orderCount;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): void
    {
        $this->totalAmount = $totalAmount;
    }

    public function getCommissionAmount(): string
    {
        return $this->commissionAmount;
    }

    public function setCommissionAmount(string $commissionAmount): void
    {
        $this->commissionAmount = $commissionAmount;
    }

    public function getCommissionRate(): string
    {
        return $this->commissionRate;
    }

    public function setCommissionRate(string $commissionRate): void
    {
        $this->commissionRate = $commissionRate;
    }

    public function getSettlementType(): SettlementTypeEnum
    {
        return $this->settlementType;
    }

    public function setSettlementType(SettlementTypeEnum $settlementType): void
    {
        $this->settlementType = $settlementType;
    }

    public function getStatus(): BillStatusEnum
    {
        return $this->status;
    }

    public function setStatus(BillStatusEnum $status): void
    {
        $this->status = $status;
    }

    public function getConfirmTime(): ?\DateTimeImmutable
    {
        return $this->confirmTime;
    }

    public function setConfirmTime(?\DateTimeImmutable $confirmTime): void
    {
        $this->confirmTime = $confirmTime;
    }

    public function getPayTime(): ?\DateTimeImmutable
    {
        return $this->payTime;
    }

    public function setPayTime(?\DateTimeImmutable $payTime): void
    {
        $this->payTime = $payTime;
    }

    public function getPaymentReference(): ?string
    {
        return $this->paymentReference;
    }

    public function setPaymentReference(?string $paymentReference): void
    {
        $this->paymentReference = $paymentReference;
    }

    public function getRemarks(): ?string
    {
        return $this->remarks;
    }

    public function setRemarks(?string $remarks): void
    {
        $this->remarks = $remarks;
    }

    /**
     * 确认账单
     */
    public function confirm(): self
    {
        if (BillStatusEnum::PENDING === $this->status) {
            $this->status = BillStatusEnum::CONFIRMED;
            $this->confirmTime = new \DateTimeImmutable();
        }

        return $this;
    }

    /**
     * 标记为已支付
     */
    public function markAsPaid(?string $paymentReference = null): self
    {
        if (BillStatusEnum::CONFIRMED === $this->status) {
            $this->status = BillStatusEnum::PAID;
            $this->payTime = new \DateTimeImmutable();
            if (null !== $paymentReference) {
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
        $commissionRate = BigDecimal::of($this->commissionRate);
        $totalAmount = BigDecimal::of($this->totalAmount);
        $commissionAmount = $totalAmount->multipliedBy($commissionRate)->toScale(2, RoundingMode::HALF_UP);
        $this->commissionAmount = $commissionAmount->__toString();

        return $this;
    }
}
