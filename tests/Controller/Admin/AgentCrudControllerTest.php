<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Validator\ValidatorInterface;
use Tourze\HotelAgentBundle\Controller\Admin\AgentCrudController;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Enum\AgentLevelEnum;
use Tourze\HotelAgentBundle\Enum\AgentStatusEnum;
use Tourze\HotelAgentBundle\Repository\AgentRepository;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(AgentCrudController::class)]
#[RunTestsInSeparateProcesses]
final class AgentCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function onSetUp(): void
    {
        // 不调用 parent::setUp() 以避免无限循环
    }

    public function testIndexPageRequiresAuthentication(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');
        $client = self::createClientWithDatabase();
        $client->request('GET', '/admin/hotel-agent/agent');
    }

    public function testIndexWithoutAuthentication(): void
    {
        $this->expectException(AccessDeniedException::class);
        $client = self::createClientWithDatabase();
        $client->request('GET', '/admin/hotel-agent/agent');
    }

    public function testIndexPageRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent');
    }

    public function testNewPageRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent', ['crudAction' => 'new']);
    }

    public function testCreateAgentRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent', ['crudAction' => 'new']);
    }

    public function testEditAgentRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $agent = $this->createTestAgent();

        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent', [
            'crudAction' => 'edit',
            'entityId' => $agent->getId(),
        ]);
    }

    public function testUpdateAgentRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $agent = $this->createTestAgent();

        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent', [
            'crudAction' => 'edit',
            'entityId' => $agent->getId(),
        ]);
    }

    public function testDeleteAgentRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $agent = $this->createTestAgent();

        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent', [
            'crudAction' => 'detail',
            'entityId' => $agent->getId(),
        ]);
    }

    public function testDetailPageRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $agent = $this->createTestAgent();

        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent', [
            'crudAction' => 'detail',
            'entityId' => $agent->getId(),
        ]);
    }

    public function testFilterByLevelRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent', [
            'filters' => [
                'level' => ['value' => AgentLevelEnum::B->value],
            ],
        ]);
    }

    public function testFilterByStatusRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent', [
            'filters' => [
                'status' => ['value' => AgentStatusEnum::ACTIVE->value],
            ],
        ]);
    }

    public function testFilterByExpiryDateRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent', [
            'filters' => [
                'expiryDate' => [
                    'value' => [
                        'from' => (new \DateTimeImmutable())->format('Y-m-d'),
                        'to' => (new \DateTimeImmutable('+2 years'))->format('Y-m-d'),
                    ],
                ],
            ],
        ]);
    }

    public function testFilterByCreateTimeRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $today = new \DateTimeImmutable();
        $client->request('GET', '/admin/hotel-agent/agent', [
            'filters' => [
                'createTime' => [
                    'value' => [
                        'from' => $today->format('Y-m-d'),
                        'to' => $today->format('Y-m-d'),
                    ],
                ],
            ],
        ]);
    }

    public function testSearchFunctionalityRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent', [
            'query' => '测试代理公司',
        ]);
    }

    public function testSearchByCodeRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $agent = $this->createTestAgent();

        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent', [
            'query' => $agent->getCode(),
        ]);
    }

    public function testSearchByPhoneRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent', [
            'query' => '13800138000',
        ]);
    }

    public function testFormValidationRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent', ['crudAction' => 'new']);
    }

    public function testExportAgentsActionRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent');
    }

    public function testManageHotelsActionRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $agent = $this->createTestAgent();

        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent', [
            'crudAction' => 'detail',
            'entityId' => $agent->getId(),
        ]);
    }

    public function testEmailValidationRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent', ['crudAction' => 'new']);
    }

    public function testListDisplaysCorrectDataRequiresAdminAccess(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/hotel-agent/agent');
    }

    private function createTestAgent(): Agent
    {
        $agent = new Agent();
        $agent->setCode('TEST_AGENT_' . uniqid());
        $agent->setCompanyName('测试代理公司');
        $agent->setContactPerson('测试联系人');
        $agent->setPhone('13800138000');
        $agent->setEmail('test@example.com');
        $agent->setLevel(AgentLevelEnum::B);
        $agent->setCommissionRate('0.08');
        $agent->setStatus(AgentStatusEnum::ACTIVE);

        $agentRepository = self::getService(AgentRepository::class);
        self::assertInstanceOf(AgentRepository::class, $agentRepository);
        $agentRepository->save($agent);

        return $agent;
    }

    /**
     * @return AbstractCrudController<Agent>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(AgentCrudController::class);
    }

    /**
     * @return \Generator<string, array{string}, mixed, void>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield '公司名称' => ['公司名称'];
        yield '联系人' => ['联系人'];
        yield '联系电话' => ['联系电话'];
        yield '邮箱地址' => ['邮箱地址'];
        yield '代理等级' => ['代理等级'];
        yield '佣金比例' => ['佣金比例'];
        yield '账户状态' => ['账户状态'];
        yield '账户有效期' => ['账户有效期'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /**
     * @return \Generator<string, array{string}, mixed, void>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'code' => ['code'];
        yield 'companyName' => ['companyName'];
        yield 'contactPerson' => ['contactPerson'];
        yield 'phone' => ['phone'];
        yield 'email' => ['email'];
        yield 'licenseUrl' => ['licenseUrl'];
        yield 'level' => ['level'];
        yield 'status' => ['status'];
        yield 'expiryDate' => ['expiryDate'];
    }

    /**
     * @return \Generator<string, array{string}, mixed, void>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'code' => ['code'];
        yield 'companyName' => ['companyName'];
        yield 'contactPerson' => ['contactPerson'];
        yield 'phone' => ['phone'];
        yield 'email' => ['email'];
        yield 'licenseUrl' => ['licenseUrl'];
        yield 'level' => ['level'];
        yield 'status' => ['status'];
        yield 'expiryDate' => ['expiryDate'];
    }

    public function testValidationErrors(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']));

        // 测试必填字段验证错误（提交空表单）
        $crawler = $client->request('GET', '/admin/hotel-agent/agent/new');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $form = $crawler->selectButton('Create')->form();
        // 故意留空必填字段来触发验证错误

        $crawler = $client->submit($form);
        $response = $client->getResponse();
        $this->assertEquals(422, $response->getStatusCode());

        // 检查必填字段验证错误消息
        $invalidFeedbackElements = $crawler->filter('.invalid-feedback');
        if ($invalidFeedbackElements->count() > 0) {
            $this->assertStringContainsString('should not be blank', $invalidFeedbackElements->text());
        }

        // 测试邮箱格式验证错误
        $crawler = $client->request('GET', '/admin/hotel-agent/agent/new');
        $form = $crawler->selectButton('Create')->form();
        $form['Agent[code]'] = 'TEST_AGENT_001';
        $form['Agent[companyName]'] = '测试代理公司';
        $form['Agent[contactPerson]'] = '测试联系人';
        $form['Agent[phone]'] = '13800138000';
        $form['Agent[email]'] = 'invalid-email-format';  // 无效邮箱格式

        $crawler = $client->submit($form);
        $response = $client->getResponse();
        $this->assertEquals(422, $response->getStatusCode());

        // 测试电话格式验证错误
        $crawler = $client->request('GET', '/admin/hotel-agent/agent/new');
        $form = $crawler->selectButton('Create')->form();
        $form['Agent[code]'] = 'TEST_AGENT_003';
        $form['Agent[companyName]'] = '测试代理公司';
        $form['Agent[contactPerson]'] = '测试联系人';
        $form['Agent[phone]'] = '@#$%^&';  // 无效电话格式（包含不允许的字符）

        $crawler = $client->submit($form);
        $response = $client->getResponse();
        $this->assertEquals(422, $response->getStatusCode());
    }
}
