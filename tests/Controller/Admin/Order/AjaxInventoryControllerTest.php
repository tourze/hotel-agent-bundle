<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Controller\Admin\Order;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\HotelAgentBundle\Controller\Admin\Order\AjaxInventoryController;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(AjaxInventoryController::class)]
#[RunTestsInSeparateProcesses]
final class AjaxInventoryControllerTest extends AbstractWebTestCase
{
    public function testRequiresAuthentication(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);
        $client->request('POST', '/admin/order/ajax/inventory');
    }

    public function testRequiresAdminRole(): void
    {
        $client = self::createClientWithDatabase();
        $user = $this->createNormalUser('user@example.com', 'password123');
        $this->loginAsUser($client, 'user@example.com', 'password123');

        $this->expectException(AccessDeniedException::class);
        $client->catchExceptions(false);
        $client->request('POST', '/admin/order/ajax/inventory');
    }

    public function testSuccessfulRequest(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client);
        $client->loginUser(new \Symfony\Component\Security\Core\User\InMemoryUser('admin', 'password', ['ROLE_ADMIN']), 'main');

        $client->request('POST', '/admin/order/ajax/inventory', [
            'room_type_id' => 1,
            'check_in_date' => '2024-02-01',
            'check_out_date' => '2024-02-02',
            'room_count' => 2,
        ]);
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

    public function testMissingRoomTypeId(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client);
        $client->loginUser(new \Symfony\Component\Security\Core\User\InMemoryUser('admin', 'password', ['ROLE_ADMIN']), 'main');

        $client->request('POST', '/admin/order/ajax/inventory', [
            'check_in_date' => '2024-02-01',
            'check_out_date' => '2024-02-02',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseContent = $client->getResponse()->getContent();
        if (false === $responseContent) {
            throw new \RuntimeException('Failed to get response content');
        }
        $response = json_decode($responseContent, true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertFalse($response['success']);
        $this->assertIsString($response['error']);
        $this->assertStringContainsString('参数不完整', $response['error']);
    }

    public function testMissingCheckInDate(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client);
        $client->loginUser(new \Symfony\Component\Security\Core\User\InMemoryUser('admin', 'password', ['ROLE_ADMIN']), 'main');

        $client->request('POST', '/admin/order/ajax/inventory', [
            'room_type_id' => 1,
            'check_out_date' => '2024-02-02',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseContent = $client->getResponse()->getContent();
        if (false === $responseContent) {
            throw new \RuntimeException('Failed to get response content');
        }
        $response = json_decode($responseContent, true);
        $this->assertIsArray($response);
        $this->assertFalse($response['success']);
        $this->assertIsString($response['error']);
        $this->assertStringContainsString('参数不完整', $response['error']);
    }

    public function testMissingCheckOutDate(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client);
        $client->loginUser(new \Symfony\Component\Security\Core\User\InMemoryUser('admin', 'password', ['ROLE_ADMIN']), 'main');

        $client->request('POST', '/admin/order/ajax/inventory', [
            'room_type_id' => 1,
            'check_in_date' => '2024-02-01',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseContent = $client->getResponse()->getContent();
        if (false === $responseContent) {
            throw new \RuntimeException('Failed to get response content');
        }
        $response = json_decode($responseContent, true);
        $this->assertIsArray($response);
        $this->assertFalse($response['success']);
        $this->assertIsString($response['error']);
        $this->assertStringContainsString('参数不完整', $response['error']);
    }

    public function testDefaultRoomCount(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client);
        $client->loginUser(new \Symfony\Component\Security\Core\User\InMemoryUser('admin', 'password', ['ROLE_ADMIN']), 'main');

        $client->request('POST', '/admin/order/ajax/inventory', [
            'room_type_id' => 1,
            'check_in_date' => '2024-02-01',
            'check_out_date' => '2024-02-02',
        ]);
        $this->assertResponseIsSuccessful();

        $responseContent = $client->getResponse()->getContent();
        if (false === $responseContent) {
            throw new \RuntimeException('Failed to get response content');
        }
        $response = json_decode($responseContent, true);
        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
    }

    public function testEmptyStringDates(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client);
        $client->loginUser(new \Symfony\Component\Security\Core\User\InMemoryUser('admin', 'password', ['ROLE_ADMIN']), 'main');

        $client->request('POST', '/admin/order/ajax/inventory', [
            'room_type_id' => 1,
            'check_in_date' => '',
            'check_out_date' => '',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseContent = $client->getResponse()->getContent();
        if (false === $responseContent) {
            throw new \RuntimeException('Failed to get response content');
        }
        $response = json_decode($responseContent, true);
        $this->assertIsArray($response);
        $this->assertFalse($response['success']);
        $this->assertIsString($response['error']);
        $this->assertStringContainsString('参数不完整', $response['error']);
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client);
        $client->loginUser(new \Symfony\Component\Security\Core\User\InMemoryUser('admin', 'password', ['ROLE_ADMIN']), 'main');

        // For routes that only support POST method, Symfony returns MethodNotAllowedHttpException
        // when accessing with other methods
        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request($method, '/admin/order/ajax/inventory');
    }
}
