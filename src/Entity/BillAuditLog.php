<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\CreateTimeAware;
use Tourze\DoctrineUserBundle\Traits\CreatedByAware;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;
use Tourze\HotelAgentBundle\Repository\BillAuditLogRepository;

/**
 * 账单审核日志实体
 */
#[ORM\Entity(repositoryClass: BillAuditLogRepository::class)]
#[ORM\Table(name: 'bill_audit_log', options: ['comment' => '账单审核日志表'])]
class BillAuditLog implements \Stringable
{
    use CreateTimeAware;
    use CreatedByAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => '主键ID'])]
    private int $id = 0;

    #[ORM\ManyToOne(targetEntity: AgentBill::class)]
    #[ORM\JoinColumn(name: 'agent_bill_id', referencedColumnName: 'id', nullable: false)]
    private AgentBill $agentBill;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '操作类型'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[IndexColumn]
    private string $action = '';

    #[ORM\Column(type: Types::STRING, length: 20, enumType: BillStatusEnum::class, nullable: true, options: ['comment' => '变更前状态'])]
    #[Assert\Choice(callback: [BillStatusEnum::class, 'cases'])]
    private ?BillStatusEnum $fromStatus = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: BillStatusEnum::class, nullable: true, options: ['comment' => '变更后状态'])]
    #[Assert\Choice(callback: [BillStatusEnum::class, 'cases'])]
    private ?BillStatusEnum $toStatus = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '审核备注'])]
    #[Assert\Length(max: 65535)]
    private ?string $remarks = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '变更数据详情'])]
    #[Assert\Type(type: 'array')]
    private ?array $changeDetails = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '操作人姓名'])]
    #[Assert\Length(max: 50)]
    private ?string $operatorName = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true, options: ['comment' => 'IP地址'])]
    #[Assert\Ip]
    #[Assert\Length(max: 20)]
    private ?string $ipAddress = null;

    public function __toString(): string
    {
        return sprintf('审核日志 %s (%s)', $this->action, $this->createTime?->format('Y-m-d H:i:s'));
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

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function getFromStatus(): ?BillStatusEnum
    {
        return $this->fromStatus;
    }

    public function setFromStatus(?BillStatusEnum $fromStatus): void
    {
        $this->fromStatus = $fromStatus;
    }

    public function getToStatus(): ?BillStatusEnum
    {
        return $this->toStatus;
    }

    public function setToStatus(?BillStatusEnum $toStatus): void
    {
        $this->toStatus = $toStatus;
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
     * @return array<string, mixed>|null
     */
    public function getChangeDetails(): ?array
    {
        return $this->changeDetails;
    }

    /**
     * @param array<string, mixed>|null $changeDetails
     */
    public function setChangeDetails(?array $changeDetails): void
    {
        $this->changeDetails = $changeDetails;
    }

    public function getOperatorName(): ?string
    {
        return $this->operatorName;
    }

    public function setOperatorName(?string $operatorName): void
    {
        $this->operatorName = $operatorName;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): void
    {
        $this->ipAddress = $ipAddress;
    }

    /**
     * 创建状态变更日志
     */
    public static function createStatusChangeLog(
        AgentBill $agentBill,
        ?BillStatusEnum $fromStatus,
        BillStatusEnum $toStatus,
        ?string $remarks = null,
        ?string $operatorName = null,
        ?string $ipAddress = null,
    ): self {
        $log = new self();
        $log->setAgentBill($agentBill);
        $log->setAction('状态变更');
        $log->setFromStatus($fromStatus);
        $log->setToStatus($toStatus);
        $log->setRemarks($remarks);
        $log->setOperatorName($operatorName);
        $log->setIpAddress($ipAddress);

        return $log;
    }

    /**
     * 创建重新计算日志
     */
    /**
     * @param array<string, mixed> $oldData
     * @param array<string, mixed> $newData
     */
    public static function createRecalculateLog(
        AgentBill $agentBill,
        array $oldData,
        array $newData,
        ?string $remarks = null,
        ?string $operatorName = null,
        ?string $ipAddress = null,
    ): self {
        $log = new self();
        $log->setAgentBill($agentBill);
        $log->setAction('重新计算');
        $log->setChangeDetails([
            'old' => $oldData,
            'new' => $newData,
        ]);
        $log->setRemarks($remarks);
        $log->setOperatorName($operatorName);
        $log->setIpAddress($ipAddress);

        return $log;
    }

    /**
     * 创建审核日志
     */
    public static function createAuditLog(
        AgentBill $agentBill,
        string $action,
        ?string $remarks = null,
        ?string $operatorName = null,
        ?string $ipAddress = null,
    ): self {
        $log = new self();
        $log->setAgentBill($agentBill);
        $log->setAction($action);
        $log->setRemarks($remarks);
        $log->setOperatorName($operatorName);
        $log->setIpAddress($ipAddress);

        return $log;
    }
}
