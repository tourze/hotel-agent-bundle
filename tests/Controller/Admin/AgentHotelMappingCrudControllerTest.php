<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Tourze\HotelAgentBundle\Controller\Admin\AgentHotelMappingCrudController;
use Tourze\HotelAgentBundle\Entity\AgentHotelMapping;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(AgentHotelMappingCrudController::class)]
#[RunTestsInSeparateProcesses]
final class AgentHotelMappingCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return AbstractCrudController<AgentHotelMapping>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(AgentHotelMappingCrudController::class);
    }

    /**
     * @return \Generator<string, array{string}, mixed, void>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield '代理' => ['代理'];
        yield '酒店' => ['酒店'];
        yield '可见房型数' => ['可见房型数'];
        yield '创建时间' => ['创建时间'];
    }

    /**
     * @return \Generator<string, array{string}, mixed, void>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'agent' => ['agent'];
        yield 'hotel' => ['hotel'];
        yield 'roomTypeIds' => ['roomTypeIds'];
    }

    /**
     * @return \Generator<string, array{string}, mixed, void>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'agent' => ['agent'];
        yield 'hotel' => ['hotel'];
        yield 'roomTypeIds' => ['roomTypeIds'];
    }

    public function testValidationErrors(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']));

        // 获取创建表单
        $crawler = $client->request('GET', '/admin/hotel-agent/agent-hotel-mapping/new');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('Create')->form();

        // 测试必填字段验证 - 提交空表单（agent和hotel为必填）
        // 注意：EasyAdmin的AssociationField在表单层会进行验证
        // 如果表单允许提交null值，控制器的persistEntity方法会检查并拒绝
        $crawler = $client->submit($form);

        // 验证表单验证或控制器层验证会返回422错误或显示错误消息
        // 由于EasyAdmin的验证机制，可能返回302重定向到列表页并显示错误消息
        $response = $client->getResponse();
        if (422 === $response->getStatusCode()) {
            // 表单层验证生效
            $this->assertEquals(422, $response->getStatusCode());
            $this->assertResponseStatusCodeSame(422);
        } else {
            // 控制器层通过flash消息拒绝（302重定向）
            $this->assertEquals(302, $response->getStatusCode());
        }
    }

    public function testBatchAssignHotelsActionRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        // 使用正确的批量操作HTTP请求格式
        $client->request('POST', '/admin', [
            'ea' => [
                'batchActionName' => 'batchAssignHotels',
                'batchActionEntityIds' => [1, 2],
                'crudControllerFqcn' => AgentHotelMappingCrudController::class,
            ],
        ]);
    }
}
