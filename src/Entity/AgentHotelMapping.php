<?php

namespace Tourze\HotelAgentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Attribute\CreatedByColumn;
use Tourze\HotelAgentBundle\Repository\AgentHotelMappingRepository;
use Tourze\HotelProfileBundle\Entity\Hotel;

#[ORM\Entity(repositoryClass: AgentHotelMappingRepository::class)]
#[ORM\Table(name: 'agent_hotel_mapping', options: ['comment' => '代理可见酒店映射表'])]
#[ORM\Index(columns: ['agent_id', 'hotel_id'], name: 'agent_hotel_mapping_idx_agent_hotel')]
class AgentHotelMapping implements Stringable
{
    use TimestampableAware;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'hotelMappings')]
    #[ORM\JoinColumn(name: 'agent_id', nullable: false)]
    private ?Agent $agent = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'hotel_id', nullable: false)]
    private ?Hotel $hotel = null;

    #[ORM\Column(type: Types::JSON, options: ['comment' => '可见房型ID数组'])]
    private array $roomTypeIds = [];#[CreatedByColumn]
    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $createdBy = null;

    public function __toString(): string
    {
        $agentName = $this->agent ? $this->agent->getCompanyName() : 'Unknown';
        $hotelName = $this->hotel ? $this->hotel->getName() : 'Unknown';
        return sprintf('%s - %s', $agentName, $hotelName);
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getHotel(): ?Hotel
    {
        return $this->hotel;
    }

    public function setHotel(?Hotel $hotel): self
    {
        $this->hotel = $hotel;
        return $this;
    }

    public function getRoomTypeIds(): array
    {
        return $this->roomTypeIds;
    }

    public function setRoomTypeIds(array $roomTypeIds): self
    {
        $this->roomTypeIds = $roomTypeIds;
        return $this;
    }

    public function addRoomTypeId(int $roomTypeId): self
    {
        if (!in_array($roomTypeId, $this->roomTypeIds)) {
            $this->roomTypeIds[] = $roomTypeId;
        }
        return $this;
    }

    public function removeRoomTypeId(int $roomTypeId): self
    {
        $this->roomTypeIds = array_filter($this->roomTypeIds, fn($id) => $id !== $roomTypeId);
        return $this;
    }

    public function hasRoomTypeId(int $roomTypeId): bool
    {
        return in_array($roomTypeId, $this->roomTypeIds);
    }public function getCreatedBy(): ?int
    {
        return $this->createdBy;
    }} 