<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Controller\Admin\BillReport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Tourze\HotelAgentBundle\Controller\Admin\BillReport\AuditStatsController;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(AuditStatsController::class)]
#[RunTestsInSeparateProcesses]
final class AuditStatsControllerTest extends AbstractWebTestCase
{
    public function testRequiresAuthentication(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);
        $client->catchExceptions(false);
        $client->request('POST', '/admin/bill-report/audit-stats');
    }

    public function testRequiresAdminRole(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser('user@example.com', 'password123');
        $this->loginAsUser($client, 'user@example.com', 'password123');

        $this->expectException(AccessDeniedException::class);
        $client->catchExceptions(false);
        $client->request('POST', '/admin/bill-report/audit-stats');
    }

    public function testSuccessfulRequest(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client); // 设置静态客户端用于断言
        $client->loginUser(new \Symfony\Component\Security\Core\User\InMemoryUser('admin', 'password', ['ROLE_ADMIN']), 'main');

        $client->request(
            'POST',
            '/admin/bill-report/audit-stats',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
            ])
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $content = $client->getResponse()->getContent();
        if (false === $content) {
            throw new \RuntimeException('Failed to get response content');
        }
        $response = json_decode($content, true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertArrayHasKey('data', $response);
    }

    public function testWithInvalidJson(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client); // 设置静态客户端用于断言
        $client->loginUser(new \Symfony\Component\Security\Core\User\InMemoryUser('admin', 'password', ['ROLE_ADMIN']), 'main');

        $client->request(
            'POST',
            '/admin/bill-report/audit-stats',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        // Invalid JSON now correctly returns an error response (improved behavior)
        $this->assertResponseStatusCodeSame(500);

        $content = $client->getResponse()->getContent();
        if (false === $content) {
            throw new \RuntimeException('Failed to get response content');
        }
        $response = json_decode($content, true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Invalid JSON data', $response['error']);
    }

    public function testWithoutDateRange(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client); // 设置静态客户端用于断言
        $client->loginUser(new \Symfony\Component\Security\Core\User\InMemoryUser('admin', 'password', ['ROLE_ADMIN']), 'main');

        $client->request(
            'POST',
            '/admin/bill-report/audit-stats',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([])
        );

        $this->assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        if (false === $content) {
            throw new \RuntimeException('Failed to get response content');
        }
        $response = json_decode($content, true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client);
        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']), 'main');

        // For routes that only support POST method, Symfony returns MethodNotAllowedHttpException
        // when accessing with other methods
        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request($method, '/admin/bill-report/audit-stats');
    }
}
