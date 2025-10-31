<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Service;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\HotelAgentBundle\Service\OrderImportService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(OrderImportService::class)]
#[RunTestsInSeparateProcesses]
final class OrderImportServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 空实现，这个测试类不需要额外的初始化
    }

    public function testServiceInstance(): void
    {
        $this->assertInstanceOf(OrderImportService::class, self::getService(OrderImportService::class));
    }

    public function testGetTemplateDownloadUrl(): void
    {
        $service = self::getService(OrderImportService::class);

        $url = $service->getTemplateDownloadUrl();

        $this->assertNotEmpty($url);
    }

    public function testGenerateTemplate(): void
    {
        $service = self::getService(OrderImportService::class);

        $spreadsheet = $service->generateTemplate();

        // 验证返回的是 Spreadsheet 实例
        $this->assertInstanceOf(Spreadsheet::class, $spreadsheet);

        // 验证工作表存在且有内容
        $activeSheet = $spreadsheet->getActiveSheet();

        // 验证工作表标题存在（至少有第一行）
        $this->assertNotEmpty(
            $activeSheet->getCell('A1')->getValue(),
            '模板第一行应包含列标题'
        );
    }

    public function testImportFromExcel(): void
    {
        self::markTestSkipped('该方法需要文件上传和数据库事务，需要完整的集成测试环境，暂时跳过');
    }
}
