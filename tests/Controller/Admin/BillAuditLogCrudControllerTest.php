<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\HotelAgentBundle\Controller\Admin\BillAuditLogCrudController;
use Tourze\HotelAgentBundle\Entity\BillAuditLog;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(BillAuditLogCrudController::class)]
#[RunTestsInSeparateProcesses]
final class BillAuditLogCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function onSetUp(): void
    {
        // 无需额外设置
    }

    /**
     * @return AbstractCrudController<BillAuditLog>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(BillAuditLogCrudController::class);
    }

    /**
     * @return \Generator<string, array{string}, mixed, void>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield '关联账单' => ['关联账单'];
        yield '操作类型' => ['操作类型'];
        yield '操作人' => ['操作人'];
        yield '操作时间' => ['操作时间'];
    }

    /**
     * @return \Generator<string, array{string}, mixed, void>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'agentBill' => ['agentBill'];
        yield 'action' => ['action'];
        yield 'fromStatus' => ['fromStatus'];
        yield 'toStatus' => ['toStatus'];
        yield 'remarks' => ['remarks'];
        yield 'operatorName' => ['operatorName'];
        yield 'ipAddress' => ['ipAddress'];
    }

    /**
     * @return \Generator<string, array{string}, mixed, void>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'agentBill' => ['agentBill'];
        yield 'action' => ['action'];
        yield 'fromStatus' => ['fromStatus'];
        yield 'toStatus' => ['toStatus'];
        yield 'remarks' => ['remarks'];
        yield 'operatorName' => ['operatorName'];
        yield 'ipAddress' => ['ipAddress'];
    }
}
