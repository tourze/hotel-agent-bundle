<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\HotelAgentBundle\Controller\Admin\AgentBillCrudController;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(AgentBillCrudController::class)]
#[RunTestsInSeparateProcesses]
final class AgentBillCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    public function testUnauthorizedAccess(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent-bill');
    }

    public function testIndexPageRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent-bill');
    }

    public function testFilterFunctionalityRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent-bill', [
            'filters' => [
                'status' => ['value' => 'pending'],
            ],
        ]);
    }

    public function testSearchFunctionalityRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent-bill', [
            'query' => 'test',
        ]);
    }

    /**
     * @return AbstractCrudController<AgentBill>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(AgentBillCrudController::class);
    }

    /**
     * @return \Generator<string, array{string}, mixed, void>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield '代理商' => ['代理商'];
        yield '账单月份' => ['账单月份'];
        yield '订单数量' => ['订单数量'];
        yield '订单总金额' => ['订单总金额'];
        yield '佣金比例' => ['佣金比例'];
        yield '佣金总额' => ['佣金总额'];
        yield '结算方式' => ['结算方式'];
        yield '账单状态' => ['账单状态'];
        yield '创建时间' => ['创建时间'];
    }

    /**
     * @return \Generator<string, array{string}, mixed, void>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'agent' => ['agent'];
        yield 'billMonth' => ['billMonth'];
        yield 'settlementType' => ['settlementType'];
        yield 'status' => ['status'];
    }

    /**
     * @return \Generator<string, array{string}, mixed, void>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'agent' => ['agent'];
        yield 'billMonth' => ['billMonth'];
        yield 'settlementType' => ['settlementType'];
        yield 'status' => ['status'];
    }

    public function testConfirmBillActionRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent-bill');
    }

    public function testRecalculateBillActionRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent-bill');
    }

    public function testGenerateBatchBillsActionRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent-bill');
    }

    public function testViewPaymentsActionRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent-bill');
    }

    public function testBillStatisticsActionRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent-bill');
    }
}
