<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Controller\Admin\BillReport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Tourze\HotelAgentBundle\Controller\Admin\BillReport\ExportAuditLogsController;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(ExportAuditLogsController::class)]
#[RunTestsInSeparateProcesses]
final class ExportAuditLogsControllerTest extends AbstractWebTestCase
{
    public function testRequiresAuthentication(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);
        $client->catchExceptions(false);
        $client->request('POST', '/admin/bill-report/export-audit-logs');
    }

    public function testRequiresAdminRole(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser('user@example.com', 'password123');
        $this->loginAsUser($client, 'user@example.com', 'password123');

        $this->expectException(AccessDeniedException::class);
        $client->catchExceptions(false);
        $client->request('POST', '/admin/bill-report/export-audit-logs');
    }

    public function testSuccessfulExport(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client);
        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']), 'main');

        $content = json_encode([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);
        if (false === $content) {
            throw new \InvalidArgumentException('Failed to encode JSON');
        }

        $client->request(
            'POST',
            '/admin/bill-report/export-audit-logs',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $content
        );
        self::getClient($client);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertResponseHeaderSame('Content-Disposition', 'attachment; filename="audit_logs_2024-01-01_2024-01-31.csv"');
    }

    public function testWithInvalidJson(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client);
        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']), 'main');

        $client->request(
            'POST',
            '/admin/bill-report/export-audit-logs',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );
        self::getClient($client);

        // Invalid JSON now correctly returns an error response (improved behavior)
        $this->assertResponseStatusCodeSame(500);
    }

    public function testWithoutDateRange(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client);
        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']), 'main');

        $content = json_encode([]);
        if (false === $content) {
            throw new \InvalidArgumentException('Failed to encode JSON');
        }

        $client->request(
            'POST',
            '/admin/bill-report/export-audit-logs',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $content
        );
        self::getClient($client);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function testExportedCsvContainsHeaders(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client);
        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']), 'main');

        $content = json_encode([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);
        if (false === $content) {
            throw new \InvalidArgumentException('Failed to encode JSON');
        }

        $client->request(
            'POST',
            '/admin/bill-report/export-audit-logs',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $content
        );
        self::getClient($client);

        $content = $client->getResponse()->getContent();
        $this->assertNotEmpty($content);
        $this->assertIsString($content);
        $this->assertStringContainsString('ID', $content);
        $this->assertStringContainsString('账单ID', $content);
        $this->assertStringContainsString('代理名称', $content);
        $this->assertStringContainsString('操作时间', $content);
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client);
        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']), 'main');

        // For routes that only support POST method, Symfony returns MethodNotAllowedHttpException
        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request($method, '/admin/bill-report/export-audit-logs');
    }
}
