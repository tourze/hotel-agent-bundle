<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\HotelAgentBundle\Controller\Admin\OrderItemCrudController;
use Tourze\HotelAgentBundle\Entity\OrderItem;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(OrderItemCrudController::class)]
#[RunTestsInSeparateProcesses]
final class OrderItemCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function onSetUp(): void
    {
        // 不调用 parent::setUp() 以避免无限循环
    }

    /**
     * @return AbstractCrudController<OrderItem>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(OrderItemCrudController::class);
    }

    /**
     * @return \Generator<string, array{string}, mixed, void>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '订单' => ['订单'];
        yield '酒店' => ['酒店'];
        yield '房型' => ['房型'];
        yield '入住日期' => ['入住日期'];
        yield '退房日期' => ['退房日期'];
        yield '数量' => ['数量'];
        yield '创建时间' => ['创建时间'];
        yield '销售单价' => ['销售单价'];
        yield '小计金额' => ['小计金额'];
        yield '状态' => ['状态'];
    }

    /**
     * @return \Generator<string, array{string}, mixed, void>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'order' => ['order'];
        yield 'hotel' => ['hotel'];
        yield 'roomType' => ['roomType'];
        yield 'checkInDate' => ['checkInDate'];
        yield 'checkOutDate' => ['checkOutDate'];
        yield 'unitPrice' => ['unitPrice'];
        yield 'costPrice' => ['costPrice'];
        yield 'status' => ['status'];
        // amount (小计金额) 在创建时隐藏
        // nights (数量) 是虚拟字段，在表单中隐藏
    }

    /**
     * @return \Generator<string, array{string}, mixed, void>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'order' => ['order'];
        yield 'hotel' => ['hotel'];
        yield 'roomType' => ['roomType'];
        yield 'checkInDate' => ['checkInDate'];
        yield 'checkOutDate' => ['checkOutDate'];
        yield 'unitPrice' => ['unitPrice'];
        yield 'costPrice' => ['costPrice'];
        yield 'status' => ['status'];
        // amount (小计金额) 在编辑时隐藏
        // nights (数量) 是虚拟字段，在表单中隐藏
    }
}
