<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\CreatedByAware;
use Tourze\HotelAgentBundle\Repository\AgentHotelMappingRepository;
use Tourze\HotelProfileBundle\Entity\Hotel;

#[ORM\Entity(repositoryClass: AgentHotelMappingRepository::class)]
#[ORM\Table(name: 'agent_hotel_mapping', options: ['comment' => '代理可见酒店映射表'])]
#[ORM\Index(columns: ['agent_id', 'hotel_id'], name: 'agent_hotel_mapping_idx_agent_hotel')]
class AgentHotelMapping implements \Stringable
{
    use TimestampableAware;
    use CreatedByAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => '主键ID'])]
    private int $id = 0;

    #[ORM\ManyToOne(inversedBy: 'hotelMappings')]
    #[ORM\JoinColumn(name: 'agent_id', nullable: false)]
    private ?Agent $agent = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'hotel_id', nullable: false)]
    private ?Hotel $hotel = null;

    /**
     * @var array<int>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '可见房型ID数组'])]
    #[Assert\All(constraints: [
        new Assert\Type(type: 'integer'),
        new Assert\Positive(),
    ])]
    private array $roomTypeIds = [];

    public function __toString(): string
    {
        $agentName = null !== $this->agent ? $this->agent->getCompanyName() : 'Unknown';
        $hotelName = null !== $this->hotel ? $this->hotel->getName() : 'Unknown';

        return sprintf('%s - %s', $agentName, $hotelName);
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

    public function getHotel(): ?Hotel
    {
        return $this->hotel;
    }

    public function setHotel(?Hotel $hotel): void
    {
        $this->hotel = $hotel;
    }

    /**
     * @return array<int>
     */
    public function getRoomTypeIds(): array
    {
        return $this->roomTypeIds;
    }

    /**
     * @param array<int> $roomTypeIds
     */
    public function setRoomTypeIds(array $roomTypeIds): void
    {
        $this->roomTypeIds = $roomTypeIds;
    }

    public function addRoomTypeId(int $roomTypeId): self
    {
        if (!in_array($roomTypeId, $this->roomTypeIds, true)) {
            $this->roomTypeIds[] = $roomTypeId;
        }

        return $this;
    }

    public function removeRoomTypeId(int $roomTypeId): self
    {
        $this->roomTypeIds = array_filter($this->roomTypeIds, fn ($id) => $id !== $roomTypeId);

        return $this;
    }

    public function hasRoomTypeId(int $roomTypeId): bool
    {
        return in_array($roomTypeId, $this->roomTypeIds, true);
    }

    /**
     * 获取可见房型数的描述
     */
    public function getRoomTypeCount(): string
    {
        return [] === $this->roomTypeIds ? '全部房型' : count($this->roomTypeIds) . ' 个房型';
    }
}
