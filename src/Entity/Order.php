<?php

namespace Tourze\HotelAgentBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\HotelAgentBundle\Enum\AuditStatusEnum;
use Tourze\HotelAgentBundle\Enum\OrderSourceEnum;
use Tourze\HotelAgentBundle\Enum\OrderStatusEnum;
use Tourze\HotelAgentBundle\Repository\OrderRepository;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'hotel_booking_order', options: ['comment' => '订单表'])]
#[ORM\Index(name: 'order_idx_order_no', columns: ['order_no'])]
#[ORM\Index(name: 'order_idx_agent_id', columns: ['agent_id'])]
#[ORM\Index(name: 'order_idx_status', columns: ['status'])]
#[ORM\Index(name: 'order_idx_audit_status', columns: ['audit_status'])]
class Order implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 50, unique: true, options: ['comment' => '订单编号'])]
    private string $orderNo = '';

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(name: 'agent_id', nullable: false)]
    private ?Agent $agent = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '订单总金额', 'default' => 0])]
    #[Assert\PositiveOrZero]
    private string $totalAmount = '0.00';

    #[ORM\Column(type: Types::STRING, length: 20, enumType: OrderStatusEnum::class, options: ['comment' => '订单状态'])]
    private OrderStatusEnum $status = OrderStatusEnum::PENDING;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: OrderSourceEnum::class, options: ['comment' => '订单来源'])]
    private OrderSourceEnum $source = OrderSourceEnum::MANUAL_INPUT;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '导入文件URL(仅Excel导入)'])]
    private ?string $importFile = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否为复合订单', 'default' => false])]
    private bool $isComplex = false;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '订单备注'])]
    private ?string $remark = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '取消原因'])]
    private ?string $cancelReason = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '取消时间'])]
    private ?\DateTimeInterface $cancelTime = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true, options: ['comment' => '取消人ID'])]
    private ?int $cancelledBy = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '变更历史记录'])]
    private ?array $changeHistory = [];

    #[ORM\Column(type: Types::STRING, length: 20, enumType: AuditStatusEnum::class, options: ['comment' => '风控审核状态', 'default' => 'approved'])]
    private AuditStatusEnum $auditStatus = AuditStatusEnum::APPROVED;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '审核备注'])]
    private ?string $auditRemark = null;

    #[CreateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createTime = null;

    #[UpdateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updateTime = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true, options: ['comment' => '创建人ID'])]
    private ?int $createdBy = null;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderNo(): string
    {
        return $this->orderNo;
    }

    public function setOrderNo(string $orderNo): self
    {
        $this->orderNo = $orderNo;
        return $this;
    }

    public function getAgent(): ?Agent
    {
        return $this->agent;
    }

    public function setAgent(?Agent $agent): self
    {
        $this->agent = $agent;
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

    public function getStatus(): OrderStatusEnum
    {
        return $this->status;
    }

    public function setStatus(OrderStatusEnum $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getSource(): OrderSourceEnum
    {
        return $this->source;
    }

    public function setSource(OrderSourceEnum $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getImportFile(): ?string
    {
        return $this->importFile;
    }

    public function setImportFile(?string $importFile): self
    {
        $this->importFile = $importFile;
        return $this;
    }

    public function isComplex(): bool
    {
        return $this->isComplex;
    }

    public function setIsComplex(bool $isComplex): self
    {
        $this->isComplex = $isComplex;
        return $this;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): self
    {
        $this->remark = $remark;
        return $this;
    }

    public function getCancelReason(): ?string
    {
        return $this->cancelReason;
    }

    public function setCancelReason(?string $cancelReason): self
    {
        $this->cancelReason = $cancelReason;
        return $this;
    }

    public function getCancelTime(): ?\DateTimeInterface
    {
        return $this->cancelTime;
    }

    public function setCancelTime(?\DateTimeInterface $cancelTime): self
    {
        $this->cancelTime = $cancelTime;
        return $this;
    }

    public function getCancelledBy(): ?int
    {
        return $this->cancelledBy;
    }

    public function setCancelledBy(?int $cancelledBy): self
    {
        $this->cancelledBy = $cancelledBy;
        return $this;
    }

    public function getChangeHistory(): ?array
    {
        return $this->changeHistory;
    }

    public function setChangeHistory(?array $changeHistory): self
    {
        $this->changeHistory = $changeHistory;
        return $this;
    }

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

    public function setAuditStatus(AuditStatusEnum $auditStatus): self
    {
        $this->auditStatus = $auditStatus;
        return $this;
    }

    public function getAuditRemark(): ?string
    {
        return $this->auditRemark;
    }

    public function setAuditRemark(?string $auditRemark): self
    {
        $this->auditRemark = $auditRemark;
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

    public function setCreatedBy(?int $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
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
            $totalAmount = bcadd($totalAmount, $orderItem->getAmount(), 2);
        }
        
        $this->totalAmount = $totalAmount;
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
            
            if ($hotelId && !in_array($hotelId, $hotelIds)) {
                $hotelIds[] = $hotelId;
            }
            
            if ($roomTypeId && !in_array($roomTypeId, $roomTypeIds)) {
                $roomTypeIds[] = $roomTypeId;
            }
        }

        // 只有当有订单项时才可能是复合订单
        $this->isComplex = !empty($hotelIds) && (count($hotelIds) > 1 || count($roomTypeIds) > 1);
    }

    /**
     * 取消订单
     */
    public function cancel(string $reason, int $cancelledBy): self
    {
        $this->status = OrderStatusEnum::CANCELED;
        $this->cancelReason = $reason;
        $this->cancelTime = new \DateTime();
        $this->cancelledBy = $cancelledBy;
        
        $this->addChangeRecord('cancel', [
            'reason' => $reason,
            'from' => 'active',
            'to' => 'canceled'
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
            'to' => OrderStatusEnum::CONFIRMED->value
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
            'to' => 'closed'
        ], $operatorId);
        
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
