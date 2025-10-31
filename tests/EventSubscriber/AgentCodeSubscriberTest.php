<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\EventSubscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\EventSubscriber\AgentCodeSubscriber;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(AgentCodeSubscriber::class)]
#[RunTestsInSeparateProcesses]
final class AgentCodeSubscriberTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 无需额外设置
    }

    public function testPrePersistGeneratesCodeWhenEmpty(): void
    {
        $agent = $this->createValidAgent();
        $agent->setCode('');

        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $this->assertNotEmpty($agent->getCode());
        $this->assertMatchesRegularExpression('/^AGT\d{10}$/', $agent->getCode());
    }

    public function testPrePersistDoesNotGenerateCodeWhenExists(): void
    {
        $agent = $this->createValidAgent();
        $existingCode = 'EXISTING123';
        $agent->setCode($existingCode);

        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $this->assertSame($existingCode, $agent->getCode());
    }

    public function testPrePersistGeneratesCodeWhenEmptyString(): void
    {
        $agent = $this->createValidAgent();
        $agent->setCode('');

        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $this->assertNotEmpty($agent->getCode());
        $this->assertMatchesRegularExpression('/^AGT\d{10}$/', $agent->getCode());
        $this->assertNotSame('', $agent->getCode());
    }

    public function testPrePersistDoesNotGenerateCodeWhenWhitespace(): void
    {
        $agent = $this->createValidAgent();
        $whitespaceCode = '   ';
        $agent->setCode($whitespaceCode);

        self::getEntityManager()->persist($agent);
        self::getEntityManager()->flush();

        $this->assertSame($whitespaceCode, $agent->getCode());
    }

    private function createValidAgent(): Agent
    {
        $agent = new Agent();
        $agent->setCompanyName('Test Company');
        $agent->setContactPerson('Test Person');
        $agent->setPhone('13800138000');

        return $agent;
    }
}
