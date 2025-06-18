<?php

namespace Tourze\HotelAgentBundle\Entity;

use Brick\Math\BigDecimal;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\HotelAgentBundle\Enum\OrderItemStatusEnum;
use Tourze\HotelAgentBundle\Repository\OrderItemRepository;
use Tourze\HotelContractBundle\Entity\DailyInventory;
use Tourze\HotelContractBundle\Entity\HotelContract;
use Tourze\HotelProfileBundle\Entity\Hotel;
use Tourze\HotelProfileBundle\Entity\RoomType;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
#[ORM\Table(name: 'order_item', options: ['comment' => '订单明细表'])]
#[ORM\UniqueConstraint(name: 'order_item_unique_daily_inventory', columns: ['daily_inventory_id'])]
#[ORM\Index(name: 'order_item_idx_order_id', columns: ['order_id'])]
#[ORM\Index(name: 'order_item_idx_hotel_roomtype', columns: ['hotel_id', 'room_type_id'])]
#[ORM\Index(name: 'order_item_idx_date_range', columns: ['check_in_date', 'check_out_date'])]
#[ORM\Index(name: 'order_item_idx_status', columns: ['status'])]
#[ORM\Index(name: 'order_item_idx_daily_inventory', columns: ['daily_inventory_id'])]
#[ORM\Index(name: 'order_item_idx_contract', columns: ['contract_id'])]
class OrderItem implements Stringable
{
    use TimestampableAware;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'order_id', nullable: false)]
    private ?Order $order = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'hotel_id', nullable: false)]
    private ?Hotel $hotel = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'room_type_id', nullable: false)]
    private ?RoomType $roomType = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, options: ['comment' => '入住日期'])]
    private ?\DateTimeInterface $checkInDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, options: ['comment' => '退房日期'])]
    private ?\DateTimeInterface $checkOutDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '销售单价'])]
    #[Assert\PositiveOrZero]
    private string $unitPrice = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '采购成本价'])]
    #[Assert\PositiveOrZero]
    private string $costPrice = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '小计金额'])]
    #[Assert\PositiveOrZero]
    private string $amount = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '利润金额'])]
    private string $profit = '0.00';

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'contract_id', nullable: true)]
    private ?HotelContract $contract = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'daily_inventory_id', nullable: true)]
    private ?DailyInventory $dailyInventory = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: OrderItemStatusEnum::class, options: ['comment' => '状态'])]
    private OrderItemStatusEnum $status = OrderItemStatusEnum::PENDING;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '合同切换历史'])]
    private ?array $contractChangeHistory = [];

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '合同切换原因'])]
    private ?string $contractChangeReason = null;#[ORM\Column(type: Types::BIGINT, nullable: true, options: ['comment' => '最后修改人ID'])]
    private ?int $lastModifiedBy = null;

    public function __construct()
    {
        $this->contractChangeHistory = [];
    }

    public function __toString(): string
    {
        $hotelName = isset($this->hotel) ? $this->hotel->getName() : 'Unknown';
        $roomTypeName = isset($this->roomType) ? $this->roomType->getName() : 'Unknown';
        $dateRange = '';

        if (isset($this->checkInDate) && isset($this->checkOutDate)) {
            $dateRange = sprintf(
                '%s - %s',
                $this->checkInDate->format('Y-m-d'),
                $this->checkOutDate->format('Y-m-d')
            );
        }

        return sprintf('%s, %s, %s', $hotelName, $roomTypeName, $dateRange);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function getHotel(): ?Hotel
    {
        return $this->hotel;
    }

    public function setHotel(?Hotel $hotel): self
    {
        $this->hotel = $hotel;
        return $this;
    }

    public function getRoomType(): ?RoomType
    {
        return $this->roomType;
    }

    public function setRoomType(?RoomType $roomType): self
    {
        $this->roomType = $roomType;
        return $this;
    }

    public function getCheckInDate(): ?\DateTimeInterface
    {
        return $this->checkInDate;
    }

    public function setCheckInDate(?\DateTimeInterface $checkInDate): self
    {
        $this->checkInDate = $checkInDate;
        $this->calculateAmount();
        return $this;
    }

    public function getCheckOutDate(): ?\DateTimeInterface
    {
        return $this->checkOutDate;
    }

    public function setCheckOutDate(?\DateTimeInterface $checkOutDate): self
    {
        $this->checkOutDate = $checkOutDate;
        $this->calculateAmount();
        return $this;
    }

    public function getUnitPrice(): string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(string $unitPrice): self
    {
        $this->unitPrice = $unitPrice;
        $this->calculateAmount();
        return $this;
    }

    public function getCostPrice(): string
    {
        return $this->costPrice;
    }

    public function setCostPrice(string $costPrice): self
    {
        $this->costPrice = $costPrice;
        $this->calculateProfit();
        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;
        $this->calculateProfit();
        return $this;
    }

    public function getProfit(): string
    {
        return $this->profit;
    }

    public function setProfit(string $profit): self
    {
        $this->profit = $profit;
        return $this;
    }

    public function getContract(): ?HotelContract
    {
        return $this->contract;
    }

    public function setContract(?HotelContract $contract): self
    {
        $this->contract = $contract;
        return $this;
    }

    public function getDailyInventory(): ?DailyInventory
    {
        return $this->dailyInventory;
    }

    public function setDailyInventory(?DailyInventory $dailyInventory): self
    {
        $this->dailyInventory = $dailyInventory;
        return $this;
    }

    public function getStatus(): OrderItemStatusEnum
    {
        return $this->status;
    }

    public function setStatus(OrderItemStatusEnum $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getContractChangeHistory(): ?array
    {
        return $this->contractChangeHistory;
    }

    public function setContractChangeHistory(?array $contractChangeHistory): self
    {
        $this->contractChangeHistory = $contractChangeHistory;
        return $this;
    }

    public function getContractChangeReason(): ?string
    {
        return $this->contractChangeReason;
    }

    public function setContractChangeReason(?string $contractChangeReason): self
    {
        $this->contractChangeReason = $contractChangeReason;
        return $this;
    }public function getLastModifiedBy(): ?int
    {
        return $this->lastModifiedBy;
    }

    public function setLastModifiedBy(?int $lastModifiedBy): self
    {
        $this->lastModifiedBy = $lastModifiedBy;
        return $this;
    }

    /**
     * 记录合同切换
     */
    public function changeContract(HotelContract $newContract, string $reason, int $operatorId): self
    {
        $oldContract = $this->contract;
        $oldContractId = $oldContract ? $oldContract->getId() : null;
        $newContractId = $newContract->getId();

        $changeRecord = [
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            'oldContractId' => $oldContractId,
            'newContractId' => $newContractId,
            'reason' => $reason,
            'operatorId' => $operatorId,
        ];

        $this->contractChangeHistory[] = $changeRecord;
        $this->contractChangeReason = $reason;
        $this->contract = $newContract;
        $this->lastModifiedBy = $operatorId;

        return $this;
    }

    /**
     * 计算订单项总金额
     */
    private function calculateAmount(): void
    {
        if (!$this->checkInDate || !$this->checkOutDate || !$this->unitPrice) {
            return;
        }

        $nights = $this->calculateNights();
        $unitPrice = BigDecimal::of($this->unitPrice);

        $this->amount = $unitPrice->multipliedBy(BigDecimal::of($nights))->toScale(2)->__toString();
        $this->calculateProfit();
    }

    /**
     * 计算入住天数
     */
    private function calculateNights(): int
    {
        if (!$this->checkInDate || !$this->checkOutDate) {
            return 0;
        }

        // 计算日期差异（以天为单位）
        $interval = $this->checkInDate->diff($this->checkOutDate);
        return $interval->days;
    }

    /**
     * 计算利润
     */
    private function calculateProfit(): void
    {
        if (!$this->amount || !$this->costPrice) {
            return;
        }

        $nights = $this->calculateNights();
        $totalCost = BigDecimal::of($this->costPrice)->multipliedBy(BigDecimal::of($nights))->toScale(2);
        $this->profit = BigDecimal::of($this->amount)->minus($totalCost)->toScale(2)->__toString();
    }

    /**
     * 取消订单项
     */
    public function cancel(): self
    {
        $this->status = OrderItemStatusEnum::CANCELED;
        return $this;
    }

    /**
     * 确认订单项
     */
    public function confirm(): self
    {
        $this->status = OrderItemStatusEnum::CONFIRMED;
        return $this;
    }

    /**
     * 标记为已完成
     */
    public function complete(): self
    {
        $this->status = OrderItemStatusEnum::COMPLETED;
        return $this;
    }}
