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
use Tourze\DoctrineUserBundle\Attribute\CreatedByColumn;
use Tourze\HotelAgentBundle\Enum\AgentLevelEnum;
use Tourze\HotelAgentBundle\Enum\AgentStatusEnum;
use Tourze\HotelAgentBundle\Repository\AgentRepository;

#[ORM\Entity(repositoryClass: AgentRepository::class)]
#[ORM\Table(name: 'agent', options: ['comment' => '代理销售账户表'])]
#[ORM\Index(name: 'agent_idx_code', columns: ['code'])]
#[ORM\Index(name: 'agent_idx_status', columns: ['status'])]
class Agent implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true, options: ['comment' => '关联的BizUser ID'])]
    private ?int $userId = null;

    #[ORM\Column(type: Types::STRING, length: 50, unique: true, options: ['comment' => '代理编号'])]
    private string $code = '';

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '公司名称'])]
    #[Assert\NotBlank]
    private string $companyName = '';

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '联系人姓名'])]
    #[Assert\NotBlank]
    private string $contactPerson = '';

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '联系电话'])]
    #[Assert\NotBlank]
    private string $phone = '';

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '联系邮箱'])]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '营业执照URL'])]
    private ?string $licenseUrl = null;

    #[ORM\Column(type: Types::STRING, length: 10, enumType: AgentLevelEnum::class, options: ['comment' => '代理等级'])]
    private AgentLevelEnum $level = AgentLevelEnum::C;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['comment' => '佣金比例'])]
    #[Assert\Range(min: 0, max: 1)]
    private string $commissionRate = '0.00';

    #[ORM\Column(type: Types::STRING, length: 20, enumType: AgentStatusEnum::class, options: ['comment' => '账户状态'])]
    private AgentStatusEnum $status = AgentStatusEnum::ACTIVE;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true, options: ['comment' => '账户有效期'])]
    private ?\DateTimeInterface $expiryDate = null;

    #[CreateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createTime = null;

    #[UpdateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updateTime = null;

    #[CreatedByColumn]
    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $createdBy = null;

    #[ORM\OneToMany(targetEntity: AgentHotelMapping::class, mappedBy: 'agent', fetch: 'EXTRA_LAZY')]
    private Collection $hotelMappings;

    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'agent', fetch: 'EXTRA_LAZY')]
    private Collection $orders;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): self
    {
        $this->companyName = $companyName;
        return $this;
    }

    public function getContactPerson(): string
    {
        return $this->contactPerson;
    }

    public function setContactPerson(string $contactPerson): self
    {
        $this->contactPerson = $contactPerson;
        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getLicenseUrl(): ?string
    {
        return $this->licenseUrl;
    }

    public function setLicenseUrl(?string $licenseUrl): self
    {
        $this->licenseUrl = $licenseUrl;
        return $this;
    }

    public function getLevel(): AgentLevelEnum
    {
        return $this->level;
    }

    public function setLevel(AgentLevelEnum $level): self
    {
        $this->level = $level;
        $this->updateCommissionRateByLevel();
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

    public function getStatus(): AgentStatusEnum
    {
        return $this->status;
    }

    public function setStatus(AgentStatusEnum $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getExpiryDate(): ?\DateTimeInterface
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(?\DateTimeInterface $expiryDate): self
    {
        $this->expiryDate = $expiryDate;
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
        $this->commissionRate = match($this->level) {
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
        if ($this->expiryDate === null) {
            return false;
        }

        return $this->expiryDate < new \DateTime();
    }

    /**
     * 检查账户是否可用
     */
    public function isActive(): bool
    {
        return $this->status === AgentStatusEnum::ACTIVE && !$this->isExpired();
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