<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Controller\Admin\BillReport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\HotelAgentBundle\Controller\Admin\BillReport\DetailedReportController;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(DetailedReportController::class)]
#[RunTestsInSeparateProcesses]
final class DetailedReportControllerTest extends AbstractWebTestCase
{
    public function testRequiresAuthentication(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $client->request('POST', '/admin/bill-report/detailed-report');
    }

    public function testRequiresAdminRole(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser('user@example.com', 'password123');
        $this->loginAsUser($client, 'user@example.com', 'password123');

        $this->expectException(AccessDeniedException::class);
        $client->catchExceptions(false);
        $client->request('POST', '/admin/bill-report/detailed-report');
    }

    public function testSuccessfulRequest(): void
    {
        $client = self::createClientWithDatabase();
        $admin = $this->createAdminUser('admin@example.com', 'password123');
        $this->loginAsAdmin($client, 'admin@example.com', 'password123');

        $client->request(
            'POST',
            '/admin/bill-report/detailed-report',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
            ])
        );
        self::getClient($client);

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
        $admin = $this->createAdminUser('admin@example.com', 'password123');
        $this->loginAsAdmin($client, 'admin@example.com', 'password123');

        $client->request(
            'POST',
            '/admin/bill-report/detailed-report',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );
        self::getClient($client);

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
        $admin = $this->createAdminUser('admin@example.com', 'password123');
        $this->loginAsAdmin($client, 'admin@example.com', 'password123');

        $client->request(
            'POST',
            '/admin/bill-report/detailed-report',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode([])
        );
        self::getClient($client);

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
        $admin = $this->createAdminUser('admin@example.com', 'password123');
        $this->loginAsAdmin($client, 'admin@example.com', 'password123');

        // For routes that only support POST method, Symfony returns MethodNotAllowedHttpException
        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request($method, '/admin/bill-report/detailed-report');
    }
}
