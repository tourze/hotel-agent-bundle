<?php

namespace Tourze\HotelAgentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Tourze\DoctrineTimestampBundle\Traits\CreateTimeAware;
use Tourze\DoctrineUserBundle\Traits\CreatedByAware;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;
use Tourze\HotelAgentBundle\Repository\BillAuditLogRepository;

/**
 * 账单审核日志实体
 */
#[ORM\Entity(repositoryClass: BillAuditLogRepository::class)]
#[ORM\Table(name: 'bill_audit_log', options: ['comment' => '账单审核日志表'])]
#[ORM\Index(name: 'bill_audit_log_idx_bill', columns: ['agent_bill_id'])]
#[ORM\Index(name: 'bill_audit_log_idx_action', columns: ['action'])]
#[ORM\Index(name: 'bill_audit_log_idx_create_time', columns: ['create_time'])]
class BillAuditLog implements Stringable
{
    use CreateTimeAware;
    use CreatedByAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AgentBill::class)]
    #[ORM\JoinColumn(name: 'agent_bill_id', referencedColumnName: 'id', nullable: false)]
    private AgentBill $agentBill;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '操作类型'])]
    private string $action = '';

    #[ORM\Column(type: Types::STRING, length: 20, enumType: BillStatusEnum::class, nullable: true, options: ['comment' => '变更前状态'])]
    private ?BillStatusEnum $fromStatus = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: BillStatusEnum::class, nullable: true, options: ['comment' => '变更后状态'])]
    private ?BillStatusEnum $toStatus = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '审核备注'])]
    private ?string $remarks = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '变更数据详情'])]
    private ?array $changeDetails = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '操作人姓名'])]
    private ?string $operatorName = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true, options: ['comment' => 'IP地址'])]
    private ?string $ipAddress = null;


    public function __toString(): string
    {
        return sprintf('审核日志 %s (%s)', $this->action, $this->createTime?->format('Y-m-d H:i:s'));
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

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function getFromStatus(): ?BillStatusEnum
    {
        return $this->fromStatus;
    }

    public function setFromStatus(?BillStatusEnum $fromStatus): self
    {
        $this->fromStatus = $fromStatus;
        return $this;
    }

    public function getToStatus(): ?BillStatusEnum
    {
        return $this->toStatus;
    }

    public function setToStatus(?BillStatusEnum $toStatus): self
    {
        $this->toStatus = $toStatus;
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

    public function getChangeDetails(): ?array
    {
        return $this->changeDetails;
    }

    public function setChangeDetails(?array $changeDetails): self
    {
        $this->changeDetails = $changeDetails;
        return $this;
    }

    public function getOperatorName(): ?string
    {
        return $this->operatorName;
    }

    public function setOperatorName(?string $operatorName): self
    {
        $this->operatorName = $operatorName;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
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
        ?string $ipAddress = null
    ): self {
        $log = new self();
        $log->setAgentBill($agentBill)
            ->setAction('状态变更')
            ->setFromStatus($fromStatus)
            ->setToStatus($toStatus)
            ->setRemarks($remarks)
            ->setOperatorName($operatorName)
            ->setIpAddress($ipAddress);

        return $log;
    }

    /**
     * 创建重新计算日志
     */
    public static function createRecalculateLog(
        AgentBill $agentBill,
        array $oldData,
        array $newData,
        ?string $remarks = null,
        ?string $operatorName = null,
        ?string $ipAddress = null
    ): self {
        $log = new self();
        $log->setAgentBill($agentBill)
            ->setAction('重新计算')
            ->setChangeDetails([
                'old' => $oldData,
                'new' => $newData
            ])
            ->setRemarks($remarks)
            ->setOperatorName($operatorName)
            ->setIpAddress($ipAddress);

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
        ?string $ipAddress = null
    ): self {
        $log = new self();
        $log->setAgentBill($agentBill)
            ->setAction($action)
            ->setRemarks($remarks)
            ->setOperatorName($operatorName)
            ->setIpAddress($ipAddress);

        return $log;
    }
} 