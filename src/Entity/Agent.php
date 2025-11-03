<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\CreatedByAware;
use Tourze\HotelAgentBundle\Enum\AgentLevelEnum;
use Tourze\HotelAgentBundle\Enum\AgentStatusEnum;
use Tourze\HotelAgentBundle\Repository\AgentRepository;

#[ORM\Entity(repositoryClass: AgentRepository::class)]
#[ORM\Table(name: 'agent', options: ['comment' => '代理销售账户表'])]
class Agent implements \Stringable
{
    use TimestampableAware;
    use CreatedByAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => '主键ID'])]
    private int $id = 0;

    #[ORM\Column(type: Types::BIGINT, nullable: true, options: ['comment' => '关联的BizUser ID'])]
    #[Assert\Positive]
    private ?int $userId = null;

    #[ORM\Column(type: Types::STRING, length: 50, unique: true, nullable: true, options: ['comment' => '代理编号'])]
    #[Assert\Length(max: 50)]
    #[IndexColumn]
    private ?string $code = null;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '公司名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $companyName = null;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '联系人姓名'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private ?string $contactPerson = null;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '联系电话'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[Assert\Regex(pattern: '/^[0-9+\-\s()]+$/', message: '请输入有效的电话号码')]
    private ?string $phone = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '联系邮箱'])]
    #[Assert\Email]
    #[Assert\Length(max: 100)]
    private ?string $email = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '营业执照URL'])]
    #[Assert\Url]
    #[Assert\Length(max: 255)]
    private ?string $licenseUrl = null;

    #[ORM\Column(type: Types::STRING, length: 10, enumType: AgentLevelEnum::class, options: ['comment' => '代理等级'])]
    #[Assert\Choice(callback: [AgentLevelEnum::class, 'cases'])]
    private AgentLevelEnum $level = AgentLevelEnum::C;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['comment' => '佣金比例'])]
    #[Assert\Range(min: 0, max: 1)]
    #[Assert\Length(max: 4)]
    private string $commissionRate = '0.00';

    #[ORM\Column(type: Types::STRING, length: 20, enumType: AgentStatusEnum::class, options: ['comment' => '账户状态'])]
    #[Assert\Choice(callback: [AgentStatusEnum::class, 'cases'])]
    #[IndexColumn]
    private AgentStatusEnum $status = AgentStatusEnum::ACTIVE;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => '账户有效期'])]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    private ?\DateTimeImmutable $expiryDate = null;

    /**
     * @var Collection<int, AgentHotelMapping>
     */
    #[ORM\OneToMany(targetEntity: AgentHotelMapping::class, mappedBy: 'agent', fetch: 'EXTRA_LAZY')]
    private Collection $hotelMappings;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'agent', fetch: 'EXTRA_LAZY')]
    private Collection $orders;

    /**
     * @var Collection<int, AgentBill>
     */
    #[ORM\OneToMany(targetEntity: AgentBill::class, mappedBy: 'agent', fetch: 'EXTRA_LAZY')]
    private Collection $bills;

    public function __construct()
    {
        $this->hotelMappings = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->bills = new ArrayCollection();
    }

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->companyName, $this->code);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getCompanyName(): string
    {
        return $this->companyName ?? '';
    }

    public function setCompanyName(?string $companyName): void
    {
        $this->companyName = $companyName;
    }

    public function getContactPerson(): string
    {
        return $this->contactPerson ?? '';
    }

    public function setContactPerson(?string $contactPerson): void
    {
        $this->contactPerson = $contactPerson;
    }

    public function getPhone(): string
    {
        return $this->phone ?? '';
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getLicenseUrl(): ?string
    {
        return $this->licenseUrl;
    }

    public function setLicenseUrl(?string $licenseUrl): void
    {
        $this->licenseUrl = $licenseUrl;
    }

    public function getLevel(): AgentLevelEnum
    {
        return $this->level;
    }

    public function setLevel(AgentLevelEnum $level): void
    {
        $this->level = $level;
        $this->updateCommissionRateByLevel();
    }

    public function getCommissionRate(): string
    {
        return $this->commissionRate;
    }

    public function setCommissionRate(string $commissionRate): void
    {
        $this->commissionRate = $commissionRate;
    }

    public function getStatus(): AgentStatusEnum
    {
        return $this->status;
    }

    public function setStatus(AgentStatusEnum $status): void
    {
        $this->status = $status;
    }

    public function getExpiryDate(): ?\DateTimeImmutable
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(?\DateTimeImmutable $expiryDate): void
    {
        $this->expiryDate = $expiryDate;
    }

    /**
     * @return Collection<int, AgentHotelMapping>
     */
    public function getHotelMappings(): Collection
    {
        return $this->hotelMappings;
    }

    public function addHotelMapping(AgentHotelMapping $hotelMapping): self
    {
        if (!$this->hotelMappings->contains($hotelMapping)) {
            $this->hotelMappings->add($hotelMapping);
            $hotelMapping->setAgent($this);
        }

        return $this;
    }

    public function removeHotelMapping(AgentHotelMapping $hotelMapping): self
    {
        if ($this->hotelMappings->removeElement($hotelMapping)) {
            if ($hotelMapping->getAgent() === $this) {
                $hotelMapping->setAgent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): self
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setAgent($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): self
    {
        if ($this->orders->removeElement($order)) {
            if ($order->getAgent() === $this) {
                $order->setAgent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AgentBill>
     */
    public function getBills(): Collection
    {
        return $this->bills;
    }

    public function addBill(AgentBill $bill): self
    {
        if (!$this->bills->contains($bill)) {
            $this->bills->add($bill);
            $bill->setAgent($this);
        }

        return $this;
    }

    public function removeBill(AgentBill $bill): self
    {
        if ($this->bills->removeElement($bill)) {
            if ($bill->getAgent() === $this) {
                $bill->setAgent(null);
            }
        }

        return $this;
    }

    /**
     * 根据代理等级更新佣金比例
     */
    private function updateCommissionRateByLevel(): void
    {
        $this->commissionRate = match ($this->level) {
            AgentLevelEnum::A => '0.10',
            AgentLevelEnum::B => '0.08',
            AgentLevelEnum::C => '0.05',
        };
    }

    /**
     * 检查账户是否过期
     */
    public function isExpired(): bool
    {
        if (null === $this->expiryDate) {
            return false;
        }

        return $this->expiryDate < new \DateTimeImmutable();
    }

    /**
     * 检查账户是否可用
     */
    public function isActive(): bool
    {
        return AgentStatusEnum::ACTIVE === $this->status && !$this->isExpired();
    }
}
