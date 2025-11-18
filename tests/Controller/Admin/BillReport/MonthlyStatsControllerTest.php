<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Controller\Admin\BillReport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Tourze\HotelAgentBundle\Controller\Admin\BillReport\MonthlyStatsController;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(MonthlyStatsController::class)]
#[RunTestsInSeparateProcesses]
final class MonthlyStatsControllerTest extends AbstractWebTestCase
{
    public function testRequiresAuthentication(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);
        $client->request('GET', '/admin/bill-report/monthly-stats/2024-01');
    }

    public function testRequiresAdminRole(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser('user@example.com', 'password123');
        $this->loginAsUser($client, 'user@example.com', 'password123');

        $this->expectException(AccessDeniedException::class);
        $client->catchExceptions(false);
        $client->request('GET', '/admin/bill-report/monthly-stats/2024-01');
    }

    public function testSuccessfulRequest(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client);
        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']), 'main');

        $client->request('GET', '/admin/bill-report/monthly-stats/2024-01');
        self::getClient($client);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseContent = $client->getResponse()->getContent();
        if (false === $responseContent) {
            throw new \RuntimeException('Failed to get response content');
        }
        $response = json_decode($responseContent, true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
        $this->assertArrayHasKey('bill_month', $response['data']);
        $this->assertArrayHasKey('total_bills', $response['data']);
        $this->assertArrayHasKey('total_amount', $response['data']);
        $this->assertArrayHasKey('total_commission', $response['data']);
        $this->assertArrayHasKey('status_breakdown', $response['data']);
    }

    public function testValidBillMonthFormat(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client);
        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']), 'main');

        $client->request('GET', '/admin/bill-report/monthly-stats/2024-12');
        self::getClient($client);

        $this->assertResponseIsSuccessful();

        $responseContent = $client->getResponse()->getContent();
        if (false === $responseContent) {
            throw new \RuntimeException('Failed to get response content');
        }
        $response = json_decode($responseContent, true);
        $this->assertIsArray($response);
        $this->assertIsArray($response['data']);
        $this->assertEquals('2024-12', $response['data']['bill_month']);
    }

    public function testResponseContainsCorrectStructure(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client);
        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']), 'main');

        $client->request('GET', '/admin/bill-report/monthly-stats/2024-01');
        self::getClient($client);

        $this->assertResponseIsSuccessful();

        $responseContent = $client->getResponse()->getContent();
        if (false === $responseContent) {
            throw new \RuntimeException('Failed to get response content');
        }
        $response = json_decode($responseContent, true);
        $this->assertIsArray($response);
        $this->assertTrue($response['success']);

        $data = $response['data'];
        $this->assertIsArray($data);
        $this->assertIsString($data['bill_month']);
        $this->assertIsInt($data['total_bills']);
        $this->assertIsString($data['total_amount']);
        $this->assertIsString($data['total_commission']);
    }

    public function testStatusBreakdownStructure(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client);
        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']), 'main');

        $client->request('GET', '/admin/bill-report/monthly-stats/2024-01');
        self::getClient($client);

        $this->assertResponseIsSuccessful();

        $responseContent = $client->getResponse()->getContent();
        if (false === $responseContent) {
            throw new \RuntimeException('Failed to get response content');
        }
        $response = json_decode($responseContent, true);
        $this->assertIsArray($response);
        $this->assertIsArray($response['data']);
        $statusBreakdown = $response['data']['status_breakdown'];
        $this->assertIsArray($statusBreakdown);

        foreach ($statusBreakdown as $stat) {
            $this->assertIsArray($stat);
            $this->assertArrayHasKey('status', $stat);
            $this->assertArrayHasKey('status_label', $stat);
            $this->assertArrayHasKey('count', $stat);
            $this->assertArrayHasKey('amount', $stat);
            $this->assertArrayHasKey('commission', $stat);
        }
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client);
        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']), 'main');

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request($method, '/admin/bill-report/monthly-stats/2024-01');
    }
}
