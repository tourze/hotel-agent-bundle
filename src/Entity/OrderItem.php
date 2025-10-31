<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Entity;

use Brick\Math\BigDecimal;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
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
#[ORM\Index(name: 'order_item_idx_hotel_roomtype', columns: ['hotel_id', 'room_type_id'])]
#[ORM\Index(name: 'order_item_idx_date_range', columns: ['check_in_date', 'check_out_date'])]
class OrderItem implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => '主键ID'])]
    private int $id = 0;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'order_id', nullable: false)]
    #[Assert\NotNull]
    private ?Order $order = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'hotel_id', nullable: false)]
    #[Assert\NotNull]
    private ?Hotel $hotel = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'room_type_id', nullable: false)]
    #[Assert\NotNull]
    private ?RoomType $roomType = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: ['comment' => '入住日期'])]
    #[Assert\NotNull]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    private ?\DateTimeImmutable $checkInDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: ['comment' => '退房日期'])]
    #[Assert\NotNull]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    #[Assert\GreaterThan(propertyPath: 'checkInDate')]
    private ?\DateTimeImmutable $checkOutDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '销售单价'])]
    #[Assert\PositiveOrZero]
    #[Assert\Length(max: 10)]
    private string $unitPrice = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '采购成本价'])]
    #[Assert\PositiveOrZero]
    #[Assert\Length(max: 10)]
    private string $costPrice = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '小计金额'])]
    #[Assert\PositiveOrZero]
    #[Assert\Length(max: 10)]
    private string $amount = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '利润金额'])]
    #[Assert\Type(type: 'numeric')]
    #[Assert\Length(max: 10)]
    private string $profit = '0.00';

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'contract_id', nullable: true)]
    private ?HotelContract $contract = null;

    #[ORM\ManyToOne(cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'daily_inventory_id', nullable: true)]
    private ?DailyInventory $dailyInventory = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: OrderItemStatusEnum::class, options: ['comment' => '状态'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [OrderItemStatusEnum::class, 'cases'])]
    #[IndexColumn]
    private OrderItemStatusEnum $status = OrderItemStatusEnum::PENDING;

    /**
     * @var array<array<string, mixed>>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '合同切换历史'])]
    #[Assert\Type(type: 'array')]
    private ?array $contractChangeHistory = [];

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '合同切换原因'])]
    #[Assert\Length(max: 65535)]
    private ?string $contractChangeReason = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true, options: ['comment' => '最后修改人ID'])]
    #[Assert\Positive]
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

        if (isset($this->checkInDate, $this->checkOutDate)) {
            $dateRange = sprintf(
                '%s - %s',
                $this->checkInDate->format('Y-m-d'),
                $this->checkOutDate->format('Y-m-d')
            );
        }

        return sprintf('%s, %s, %s', $hotelName, $roomTypeName, $dateRange);
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * 检查实体是否已经被持久化（有有效ID）
     */
    public function isPersisted(): bool
    {
        // 防御性检查：处理Doctrine懒加载可能导致的未初始化场景
        return isset($this->id) && $this->id > 0;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): void
    {
        $this->order = $order;
    }

    public function getHotel(): ?Hotel
    {
        return $this->hotel;
    }

    public function setHotel(?Hotel $hotel): void
    {
        $this->hotel = $hotel;
    }

    public function getRoomType(): ?RoomType
    {
        return $this->roomType;
    }

    public function setRoomType(?RoomType $roomType): void
    {
        $this->roomType = $roomType;
    }

    public function getCheckInDate(): ?\DateTimeImmutable
    {
        return $this->checkInDate;
    }

    public function setCheckInDate(?\DateTimeImmutable $checkInDate): void
    {
        $this->checkInDate = $checkInDate;
        $this->calculateAmount();
    }

    public function getCheckOutDate(): ?\DateTimeImmutable
    {
        return $this->checkOutDate;
    }

    public function setCheckOutDate(?\DateTimeImmutable $checkOutDate): void
    {
        $this->checkOutDate = $checkOutDate;
        $this->calculateAmount();
    }

    public function getUnitPrice(): string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(string $unitPrice): void
    {
        $this->unitPrice = $unitPrice;
        $this->calculateAmount();
    }

    public function getCostPrice(): string
    {
        return $this->costPrice;
    }

    public function setCostPrice(string $costPrice): void
    {
        $this->costPrice = $costPrice;
        $this->calculateProfit();
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): void
    {
        $this->amount = $amount;
        $this->calculateProfit();
    }

    public function getProfit(): string
    {
        return $this->profit;
    }

    public function setProfit(string $profit): void
    {
        $this->profit = $profit;
    }

    public function getContract(): ?HotelContract
    {
        return $this->contract;
    }

    public function setContract(?HotelContract $contract): void
    {
        $this->contract = $contract;
    }

    public function getDailyInventory(): ?DailyInventory
    {
        return $this->dailyInventory;
    }

    public function setDailyInventory(?DailyInventory $dailyInventory): void
    {
        $this->dailyInventory = $dailyInventory;
    }

    public function getStatus(): OrderItemStatusEnum
    {
        return $this->status;
    }

    public function setStatus(OrderItemStatusEnum $status): void
    {
        $this->status = $status;
    }

    /**
     * @return array<array<string, mixed>>|null
     */
    public function getContractChangeHistory(): ?array
    {
        return $this->contractChangeHistory;
    }

    /**
     * @param array<array<string, mixed>>|null $contractChangeHistory
     */
    public function setContractChangeHistory(?array $contractChangeHistory): void
    {
        $this->contractChangeHistory = $contractChangeHistory;
    }

    public function getContractChangeReason(): ?string
    {
        return $this->contractChangeReason;
    }

    public function setContractChangeReason(?string $contractChangeReason): void
    {
        $this->contractChangeReason = $contractChangeReason;
    }

    public function getLastModifiedBy(): ?int
    {
        return $this->lastModifiedBy;
    }

    public function setLastModifiedBy(?int $lastModifiedBy): void
    {
        $this->lastModifiedBy = $lastModifiedBy;
    }

    /**
     * 记录合同切换
     */
    public function changeContract(HotelContract $newContract, string $reason, int $operatorId): self
    {
        $oldContract = $this->contract;
        $oldContractId = null !== $oldContract ? $oldContract->getId() : null;
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
        if (null === $this->checkInDate || null === $this->checkOutDate) {
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
        if (null === $this->checkInDate || null === $this->checkOutDate) {
            return 0;
        }

        // 计算日期差异（以天为单位）
        $interval = $this->checkInDate->diff($this->checkOutDate);

        return false !== $interval->days ? $interval->days : 0;
    }

    /**
     * 获取入住天数（公共方法，用于EasyAdmin显示）
     */
    public function getNights(): int
    {
        return $this->calculateNights();
    }

    /**
     * 计算利润
     */
    private function calculateProfit(): void
    {
        // amount 和 costPrice 都是 string 类型且有默认值，不需要检查
        // 直接计算
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
    }
}
