<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Service\AgentCodeGenerator;

/**
 * 代理编号自动生成订阅器
 *
 * 在保存代理实体时，如果没有编号则自动生成
 */
#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Agent::class)]
class AgentCodeSubscriber
{
    public function __construct(
        private readonly AgentCodeGenerator $codeGenerator,
    ) {
    }

    /**
     * 在实体持久化前执行
     */
    public function prePersist(Agent $agent, PrePersistEventArgs $event): void
    {
        // 如果代理编号为空，则自动生成
        if ('' === $agent->getCode()) {
            $code = $this->codeGenerator->generateCode();
            $agent->setCode($code);
        }
    }
}
