<?php

namespace Tourze\HotelAgentBundle\Service;

use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Entity\AgentHotelMapping;
use Tourze\HotelAgentBundle\Entity\BillAuditLog;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Entity\Payment;

/**
 * 房卡配送管理菜单服务
 */
class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private readonly LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (!$item->getChild('代理管理')) {
            $item->addChild('代理管理');
        }
        $subMenu = $item->getChild('代理管理');
        $subMenu->addChild('订单列表')
            ->setUri($this->linkGenerator->getCurdListPage(Order::class))
            ->setAttribute('icon', 'fas fa-shopping-cart');
        $subMenu->addChild('代理账户')
            ->setUri($this->linkGenerator->getCurdListPage(Agent::class))
            ->setAttribute('icon', 'fas fa-user-tie');
        $subMenu->addChild('酒店授权')
            ->setUri($this->linkGenerator->getCurdListPage(AgentHotelMapping::class))
            ->setAttribute('icon', 'fas fa-shield-alt');

        if (!$item->getChild('财务结算')) {
            $item->addChild('财务结算');
        }
        $subMenu = $item->getChild('财务结算');
        $subMenu->addChild('代理账单')
            ->setUri($this->linkGenerator->getCurdListPage(AgentBill::class))
            ->setAttribute('icon', 'fas fa-file-invoice-dollar');
        $subMenu->addChild('支付记录')
            ->setUri($this->linkGenerator->getCurdListPage(Payment::class))
            ->setAttribute('icon', 'fas fa-credit-card');
        $subMenu->addChild('审核日志')
            ->setUri($this->linkGenerator->getCurdListPage(BillAuditLog::class))
            ->setAttribute('icon', 'fas fa-history');
    }
}
