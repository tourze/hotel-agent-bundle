<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Entity;

use Brick\Math\BigDecimal;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\CreatedByAware;
use Tourze\HotelAgentBundle\Enum\AuditStatusEnum;
use Tourze\HotelAgentBundle\Enum\OrderSourceEnum;
use Tourze\HotelAgentBundle\Enum\OrderStatusEnum;
use Tourze\HotelAgentBundle\Repository\OrderRepository;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'hotel_booking_order', options: ['comment' => '订单表'])]
class Order implements \Stringable
{
    use TimestampableAware;
    use CreatedByAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => '主键ID'])]
    private int $id = 0;

    #[ORM\Column(type: Types::STRING, length: 50, unique: true, options: ['comment' => '订单编号'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[IndexColumn]
    private string $orderNo = '';

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(name: 'agent_id', nullable: false)]
    #[Assert\NotNull]
    private ?Agent $agent = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '订单总金额', 'default' => 0])]
    #[Assert\PositiveOrZero]
    #[Assert\Length(max: 13)]
    private string $totalAmount = '0.00';

    #[ORM\Column(type: Types::STRING, length: 20, enumType: OrderStatusEnum::class, options: ['comment' => '订单状态'])]
    #[Assert\Choice(callback: [OrderStatusEnum::class, 'cases'])]
    #[IndexColumn]
    private OrderStatusEnum $status = OrderStatusEnum::PENDING;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: OrderSourceEnum::class, options: ['comment' => '订单来源'])]
    #[Assert\Choice(callback: [OrderSourceEnum::class, 'cases'])]
    private OrderSourceEnum $source = OrderSourceEnum::MANUAL_INPUT;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '导入文件URL(仅Excel导入)'])]
    #[Assert\Url]
    #[Assert\Length(max: 255)]
    private ?string $importFile = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否为复合订单', 'default' => false])]
    #[Assert\Type(type: 'bool')]
    private bool $isComplex = false;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '订单备注'])]
    #[Assert\Length(max: 65535)]
    private ?string $remark = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '取消原因'])]
    #[Assert\Length(max: 65535)]
    private ?string $cancelReason = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '取消时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    private ?\DateTimeImmutable $cancelTime = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true, options: ['comment' => '取消人ID'])]
    #[Assert\Positive]
    private ?int $cancelledBy = null;

    /**
     * @var array<array<string, mixed>>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '变更历史记录'])]
    #[Assert\Type(type: 'array')]
    private ?array $changeHistory = [];

    #[ORM\Column(type: Types::STRING, length: 20, enumType: AuditStatusEnum::class, options: ['comment' => '风控审核状态', 'default' => 'approved'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [AuditStatusEnum::class, 'cases'])]
    #[IndexColumn]
    private AuditStatusEnum $auditStatus = AuditStatusEnum::APPROVED;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '审核备注'])]
    #[Assert\Length(max: 65535)]
    private ?string $auditRemark = null;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $orderItems;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
        $this->changeHistory = [];
    }

    public function __toString(): string
    {
        return $this->orderNo;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOrderNo(): string
    {
        return $this->orderNo;
    }

    public function setOrderNo(string $orderNo): void
    {
        $this->orderNo = $orderNo;
    }

    public function getAgent(): ?Agent
    {
        return $this->agent;
    }

    public function setAgent(?Agent $agent): void
    {
        $this->agent = $agent;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): void
    {
        $this->totalAmount = $totalAmount;
    }

    public function getStatus(): OrderStatusEnum
    {
        return $this->status;
    }

    public function setStatus(OrderStatusEnum $status): void
    {
        $this->status = $status;
    }

    public function getSource(): OrderSourceEnum
    {
        return $this->source;
    }

    public function setSource(OrderSourceEnum $source): void
    {
        $this->source = $source;
    }

    public function getImportFile(): ?string
    {
        return $this->importFile;
    }

    public function setImportFile(?string $importFile): void
    {
        $this->importFile = $importFile;
    }

    public function isComplex(): bool
    {
        return $this->isComplex;
    }

    public function setIsComplex(bool $isComplex): void
    {
        $this->isComplex = $isComplex;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }

    public function getCancelReason(): ?string
    {
        return $this->cancelReason;
    }

    public function setCancelReason(?string $cancelReason): void
    {
        $this->cancelReason = $cancelReason;
    }

    public function getCancelTime(): ?\DateTimeImmutable
    {
        return $this->cancelTime;
    }

    public function setCancelTime(?\DateTimeImmutable $cancelTime): void
    {
        $this->cancelTime = $cancelTime;
    }

    public function getCancelledBy(): ?int
    {
        return $this->cancelledBy;
    }

    public function setCancelledBy(?int $cancelledBy): void
    {
        $this->cancelledBy = $cancelledBy;
    }

    /**
     * @return array<array<string, mixed>>|null
     */
    public function getChangeHistory(): ?array
    {
        return $this->changeHistory;
    }

    /**
     * @param array<array<string, mixed>>|null $changeHistory
     */
    public function setChangeHistory(?array $changeHistory): void
    {
        $this->changeHistory = $changeHistory;
    }

    /**
     * @param array<string, mixed> $changes
     */
    public function addChangeRecord(string $changeType, array $changes, ?int $operatorId = null): self
    {
        $record = [
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            'type' => $changeType,
            'changes' => $changes,
            'operatorId' => $operatorId,
        ];

        $this->changeHistory[] = $record;

        return $this;
    }

    public function getAuditStatus(): AuditStatusEnum
    {
        return $this->auditStatus;
    }

    public function setAuditStatus(AuditStatusEnum $auditStatus): void
    {
        $this->auditStatus = $auditStatus;
    }

    public function getAuditRemark(): ?string
    {
        return $this->auditRemark;
    }

    public function setAuditRemark(?string $auditRemark): void
    {
        $this->auditRemark = $auditRemark;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): self
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setOrder($this);

            // 更新订单总金额
            $this->recalculateTotalAmount();

            // 检查是否为复合订单
            $this->checkIfComplex();
        }

        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): self
    {
        if ($this->orderItems->removeElement($orderItem)) {
            if ($orderItem->getOrder() === $this) {
                $orderItem->setOrder(null);
            }

            // 更新订单总金额
            $this->recalculateTotalAmount();

            // 检查是否为复合订单
            $this->checkIfComplex();
        }

        return $this;
    }

    /**
     * 重新计算订单总金额
     */
    public function recalculateTotalAmount(): void
    {
        $totalAmount = '0.00';

        foreach ($this->orderItems as $orderItem) {
            $totalAmount = BigDecimal::of($totalAmount)->plus($orderItem->getAmount())->toScale(2);
        }

        $this->totalAmount = (string) $totalAmount;
    }

    /**
     * 检查是否为复合订单（多个酒店或多个房型）
     */
    private function checkIfComplex(): void
    {
        if ($this->orderItems->count() <= 1) {
            $this->isComplex = false;

            return;
        }

        $hotelIds = [];
        $roomTypeIds = [];

        foreach ($this->orderItems as $orderItem) {
            $hotelId = $orderItem->getHotel()?->getId();
            $roomTypeId = $orderItem->getRoomType()?->getId();

            if (null !== $hotelId && !in_array($hotelId, $hotelIds, true)) {
                $hotelIds[] = $hotelId;
            }

            if (null !== $roomTypeId && !in_array($roomTypeId, $roomTypeIds, true)) {
                $roomTypeIds[] = $roomTypeId;
            }
        }

        // 只有当有订单项时才可能是复合订单
        $this->isComplex = [] !== $hotelIds && (count($hotelIds) > 1 || count($roomTypeIds) > 1);
    }

    /**
     * 取消订单
     */
    public function cancel(string $reason, int $cancelledBy): self
    {
        $this->status = OrderStatusEnum::CANCELED;
        $this->cancelReason = $reason;
        $this->cancelTime = new \DateTimeImmutable();
        $this->cancelledBy = $cancelledBy;

        $this->addChangeRecord('cancel', [
            'reason' => $reason,
            'from' => 'active',
            'to' => 'canceled',
        ], $cancelledBy);

        return $this;
    }

    /**
     * 确认订单
     */
    public function confirm(int $operatorId): self
    {
        $this->status = OrderStatusEnum::CONFIRMED;

        $this->addChangeRecord('confirm', [
            'from' => $this->status->value,
            'to' => OrderStatusEnum::CONFIRMED->value,
        ], $operatorId);

        return $this;
    }

    /**
     * 关闭订单
     */
    public function close(string $reason, int $operatorId): self
    {
        $this->status = OrderStatusEnum::CLOSED;

        $this->addChangeRecord('close', [
            'reason' => $reason,
            'from' => 'active',
            'to' => 'closed',
        ], $operatorId);

        return $this;
    }
}
