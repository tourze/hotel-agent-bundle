<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Service;

use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\HotelAgentBundle\Service\AdminMenu;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    protected function onSetUp(): void
    {
        // 无需额外设置
    }

    private function createAdminMenu(): AdminMenu
    {
        return self::getService(AdminMenu::class);
    }

    public function testInvokeCreatesAgentManagementMenu(): void
    {
        $menuFactory = new MenuFactory();
        $rootMenu = new MenuItem('root', $menuFactory);

        ($this->createAdminMenu())($rootMenu);

        $agentManagementMenu = $rootMenu->getChild('代理管理');
        $this->assertNotNull($agentManagementMenu);

        $orderListItem = $agentManagementMenu->getChild('订单列表');
        $this->assertNotNull($orderListItem);
        $this->assertNotEmpty($orderListItem->getUri());
        $this->assertEquals('fas fa-shopping-cart', $orderListItem->getAttribute('icon'));

        $agentAccountItem = $agentManagementMenu->getChild('代理账户');
        $this->assertNotNull($agentAccountItem);
        $this->assertNotEmpty($agentAccountItem->getUri());
        $this->assertEquals('fas fa-user-tie', $agentAccountItem->getAttribute('icon'));

        $hotelAuthItem = $agentManagementMenu->getChild('酒店授权');
        $this->assertNotNull($hotelAuthItem);
        $this->assertNotEmpty($hotelAuthItem->getUri());
        $this->assertEquals('fas fa-shield-alt', $hotelAuthItem->getAttribute('icon'));

        // Also verify finance menu was created
        $financeMenu = $rootMenu->getChild('财务结算');
        $this->assertNotNull($financeMenu);
    }

    public function testInvokeCreatesFinanceSettlementMenu(): void
    {
        $menuFactory = new MenuFactory();
        $rootMenu = new MenuItem('root', $menuFactory);

        ($this->createAdminMenu())($rootMenu);

        $financeMenu = $rootMenu->getChild('财务结算');
        $this->assertNotNull($financeMenu);

        $agentBillItem = $financeMenu->getChild('代理账单');
        $this->assertNotNull($agentBillItem);
        $this->assertNotEmpty($agentBillItem->getUri());
        $this->assertEquals('fas fa-file-invoice-dollar', $agentBillItem->getAttribute('icon'));

        $paymentRecordItem = $financeMenu->getChild('支付记录');
        $this->assertNotNull($paymentRecordItem);
        $this->assertNotEmpty($paymentRecordItem->getUri());
        $this->assertEquals('fas fa-credit-card', $paymentRecordItem->getAttribute('icon'));

        $auditLogItem = $financeMenu->getChild('审核日志');
        $this->assertNotNull($auditLogItem);
        $this->assertNotEmpty($auditLogItem->getUri());
        $this->assertEquals('fas fa-history', $auditLogItem->getAttribute('icon'));
    }

    public function testInvokeWithExistingAgentManagementMenu(): void
    {
        $menuFactory = new MenuFactory();
        $rootMenu = new MenuItem('root', $menuFactory);
        $existingAgentMenu = new MenuItem('代理管理', $menuFactory);
        $rootMenu->addChild($existingAgentMenu);

        ($this->createAdminMenu())($rootMenu);

        $agentManagementMenu = $rootMenu->getChild('代理管理');
        $this->assertSame($existingAgentMenu, $agentManagementMenu);

        $this->assertNotNull($agentManagementMenu->getChild('订单列表'));
        $this->assertNotNull($agentManagementMenu->getChild('代理账户'));
        $this->assertNotNull($agentManagementMenu->getChild('酒店授权'));
    }

    public function testInvokeWithExistingFinanceSettlementMenu(): void
    {
        $menuFactory = new MenuFactory();
        $rootMenu = new MenuItem('root', $menuFactory);
        $existingFinanceMenu = new MenuItem('财务结算', $menuFactory);
        $rootMenu->addChild($existingFinanceMenu);

        ($this->createAdminMenu())($rootMenu);

        $financeMenu = $rootMenu->getChild('财务结算');
        $this->assertSame($existingFinanceMenu, $financeMenu);

        $this->assertNotNull($financeMenu->getChild('代理账单'));
        $this->assertNotNull($financeMenu->getChild('支付记录'));
        $this->assertNotNull($financeMenu->getChild('审核日志'));
    }
}
