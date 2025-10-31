<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Controller\Admin\BillReport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\HotelAgentBundle\Controller\Admin\BillReport\IndexController;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(IndexController::class)]
#[RunTestsInSeparateProcesses]
final class IndexControllerTest extends AbstractWebTestCase
{
    public function testRequiresAuthentication(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);
        $client->request('GET', '/admin/bill-report');
    }

    public function testRequiresAdminRole(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser('user@example.com', 'password123');
        $this->loginAsUser($client, 'user@example.com', 'password123');

        $this->expectException(AccessDeniedException::class);
        $client->catchExceptions(false);
        $client->request('GET', '/admin/bill-report');
    }

    public function testSuccessfulAccess(): void
    {
        $client = self::createClientWithDatabase();
        $admin = $this->createAdminUser('admin@example.com', 'password123');
        $this->loginAsAdmin($client, 'admin@example.com', 'password123');

        $client->request('GET', '/admin/bill-report');
        self::getClient($client);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'text/html; charset=UTF-8');
    }

    public function testRendersCorrectTemplate(): void
    {
        $client = self::createClientWithDatabase();
        $admin = $this->createAdminUser('admin@example.com', 'password123');
        $this->loginAsAdmin($client, 'admin@example.com', 'password123');

        $client->request('GET', '/admin/bill-report');
        self::getClient($client);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('html');
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();
        $admin = $this->createAdminUser('admin@example.com', 'password123');
        $this->loginAsAdmin($client, 'admin@example.com', 'password123');

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request($method, '/admin/bill-report');
    }
}
