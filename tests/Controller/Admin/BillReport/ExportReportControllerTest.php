<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Controller\Admin\BillReport;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\HotelAgentBundle\Controller\Admin\BillReport\ExportReportController;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(ExportReportController::class)]
#[RunTestsInSeparateProcesses]
final class ExportReportControllerTest extends AbstractWebTestCase
{
    public function testRequiresAuthentication(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);
        $client->catchExceptions(false);
        $client->request('POST', '/admin/bill-report/export');
    }

    public function testRequiresAdminRole(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser('user@example.com', 'password123');
        $this->loginAsUser($client, 'user@example.com', 'password123');

        $this->expectException(AccessDeniedException::class);
        $client->catchExceptions(false);
        $client->request('POST', '/admin/bill-report/export');
    }

    public function testSuccessfulCsvExport(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client); // 设置静态客户端用于断言
        $admin = $this->createAdminUser('admin@example.com', 'password123');
        $this->loginAsAdmin($client, 'admin@example.com', 'password123');

        $content = json_encode([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'format' => 'csv',
        ]);
        if (false === $content) {
            throw new \InvalidArgumentException('Failed to encode JSON');
        }

        $client->request(
            'POST',
            '/admin/bill-report/export',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $content
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'text/csv; charset=UTF-8');
        $this->assertResponseHeaderSame('content-disposition', 'attachment; filename="bill_report_2024-01-01_2024-01-31.csv"');
    }

    public function testSuccessfulExcelExport(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client); // 设置静态客户端用于断言
        $admin = $this->createAdminUser('admin@example.com', 'password123');
        $this->loginAsAdmin($client, 'admin@example.com', 'password123');

        $content = json_encode([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'format' => 'excel',
        ]);
        if (false === $content) {
            throw new \InvalidArgumentException('Failed to encode JSON');
        }

        $client->request(
            'POST',
            '/admin/bill-report/export',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $content
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertResponseHeaderSame('content-disposition', 'attachment; filename="bill_report_2024-01-01_2024-01-31.xlsx"');

        // Verify Excel content is valid
        $responseContent = $client->getResponse()->getContent();
        $this->assertNotEmpty($responseContent);
        $this->assertIsString($responseContent);

        // Verify it's a valid Excel file by loading it
        $tempFile = tempnam(sys_get_temp_dir(), 'test_excel_');
        if (false === $tempFile) {
            throw new \RuntimeException('Failed to create temp file');
        }

        try {
            file_put_contents($tempFile, $responseContent);
            $spreadsheet = IOFactory::load($tempFile);
            $worksheet = $spreadsheet->getActiveSheet();

            // Verify some key content
            $this->assertSame('账单统计报表', $worksheet->getCell('A1')->getValue());
            $this->assertSame('统计期间', $worksheet->getCell('A2')->getValue());
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function testDefaultFormatIsCsv(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client); // 设置静态客户端用于断言
        $admin = $this->createAdminUser('admin@example.com', 'password123');
        $this->loginAsAdmin($client, 'admin@example.com', 'password123');

        $content = json_encode([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);
        if (false === $content) {
            throw new \InvalidArgumentException('Failed to encode JSON');
        }

        $client->request(
            'POST',
            '/admin/bill-report/export',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $content
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'text/csv; charset=UTF-8');
    }

    public function testUnsupportedFormatReturnsError(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client); // 设置静态客户端用于断言
        $admin = $this->createAdminUser('admin@example.com', 'password123');
        $this->loginAsAdmin($client, 'admin@example.com', 'password123');

        $content = json_encode([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'format' => 'pdf',
        ]);
        if (false === $content) {
            throw new \InvalidArgumentException('Failed to encode JSON');
        }

        $client->request(
            'POST',
            '/admin/bill-report/export',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $content
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);

        $responseContent = $client->getResponse()->getContent();
        if (false === $responseContent) {
            throw new \RuntimeException('Failed to get response content');
        }
        $response = json_decode($responseContent, true);
        if (!is_array($response)) {
            throw new \RuntimeException('Invalid JSON response');
        }
        $this->assertArrayHasKey('success', $response);
        $this->assertFalse($response['success']);
        if (!is_string($response['error'] ?? null)) {
            throw new \RuntimeException('Error field is not a string');
        }
        $this->assertStringContainsString('不支持的导出格式', $response['error']);
    }

    public function testExportedCsvContainsReportData(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client); // 设置静态客户端用于断言
        $admin = $this->createAdminUser('admin@example.com', 'password123');
        $this->loginAsAdmin($client, 'admin@example.com', 'password123');

        $content = json_encode([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'format' => 'csv',
        ]);
        if (false === $content) {
            throw new \InvalidArgumentException('Failed to encode JSON');
        }

        $client->request(
            'POST',
            '/admin/bill-report/export',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $content
        );

        $content = $client->getResponse()->getContent();
        $this->assertNotEmpty($content);
        $this->assertIsString($content);
        $this->assertStringContainsString('账单统计报表', $content);
        $this->assertStringContainsString('统计期间', $content);
        $this->assertStringContainsString('状态统计', $content);
        $this->assertStringContainsString('代理统计', $content);
        $this->assertStringContainsString('月度统计', $content);
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();
        $admin = $this->createAdminUser('admin@example.com', 'password123');
        $this->loginAsAdmin($client, 'admin@example.com', 'password123');

        // For routes that only support POST method, Symfony returns MethodNotAllowedHttpException
        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request($method, '/admin/bill-report/export');
    }
}
