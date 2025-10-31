<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Service;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Repository\AgentRepository;
use Tourze\HotelAgentBundle\Service\AgentCodeGenerator;

/**
 * @internal
 */
#[CoversClass(AgentCodeGenerator::class)]
final class AgentCodeGeneratorTest extends TestCase
{
    /** @var MockObject&AgentRepository */
    private MockObject $agentRepository;

    private AgentCodeGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * 使用具体类 AgentRepository 创建 mock 对象的原因：
         * 1. AgentRepository 是本包的 Repository 类，没有对应的接口定义
         * 2. 在测试中需要模拟复杂的数据库查询逻辑，使用具体类是合理的
         * 3. 为 Repository 创建接口会增加不必要的复杂性，且不符合 Doctrine 的传统做法
         */
        $this->agentRepository = $this->createMock(AgentRepository::class);
        $this->generator = new AgentCodeGenerator($this->agentRepository);
    }

    public function testGenerateCodeCreatesFirstCodeOfDay(): void
    {
        $this->setupRepositoryToReturnNoAgent();

        $code = $this->generator->generateCode();

        $expectedPrefix = 'AGT' . date('Ymd');
        $this->assertStringStartsWith($expectedPrefix, $code);
        $this->assertStringEndsWith('01', $code);
        $this->assertSame(strlen($expectedPrefix) + 2, strlen($code));
    }

    public function testGenerateCodeIncrementsSequenceNumber(): void
    {
        $existingAgent = new Agent();
        $existingAgent->setCode('AGT' . date('Ymd') . '03');

        $this->setupRepositoryToReturnAgent($existingAgent);

        $code = $this->generator->generateCode();

        $expectedCode = 'AGT' . date('Ymd') . '04';
        $this->assertSame($expectedCode, $code);
    }

    public function testGenerateCodeUsesTimestampWhenSequenceExceeds99(): void
    {
        $existingAgent = new Agent();
        $existingAgent->setCode('AGT' . date('Ymd') . '99');

        $this->setupRepositoryToReturnAgent($existingAgent);

        $code = $this->generator->generateCode();

        $expectedPrefix = 'AGT' . date('Ymd');
        $this->assertStringStartsWith($expectedPrefix, $code);
        $this->assertGreaterThan(strlen($expectedPrefix) + 2, strlen($code));
    }

    public function testGenerateCodeEnsuresUniquenessWithCollision(): void
    {
        $existingAgent1 = new Agent();
        $existingAgent1->setCode('AGT' . date('Ymd') . '01');

        $existingAgent2 = new Agent();
        $existingAgent2->setCode('AGT' . date('Ymd') . '02');

        $this->setupRepositoryWithCollision($existingAgent1, $existingAgent2);

        $code = $this->generator->generateCode();

        $expectedCode = 'AGT' . date('Ymd') . '03';
        $this->assertSame($expectedCode, $code);
    }

    public function testGenerateCodeUsesMicrotimeAsFallback(): void
    {
        // 模拟所有可能的编号都被占用的情况
        $this->setupRepositoryAlwaysReturnAgent();

        $code = $this->generator->generateCode();

        $this->assertStringStartsWith('AGT', $code);
        $this->assertMatchesRegularExpression('/^AGT\d{8,}$/', $code);
    }

    public function testIsValidCodeWithValidCodes(): void
    {
        $validCodes = [
            'AGT20250101',
            'AGT2025010199',
            'AGT1234567890',
        ];

        foreach ($validCodes as $code) {
            $this->assertTrue($this->generator->isValidCode($code), "Code {$code} should be valid");
        }
    }

    public function testIsValidCodeWithInvalidCodes(): void
    {
        $invalidCodes = [
            'AGT123',           // 太短
            'AGT123456789012',  // 太长
            'AGENT20250101',    // 前缀错误
            'AGT2025010A',      // 包含字母
            '20250101',         // 没有前缀
            '',                 // 空字符串
            'AGT',              // 只有前缀
        ];

        foreach ($invalidCodes as $code) {
            $this->assertFalse($this->generator->isValidCode($code), "Code {$code} should be invalid");
        }
    }

    public function testIsValidCodeWithEdgeCases(): void
    {
        $this->assertFalse($this->generator->isValidCode('AGT2025010'));  // 刚好少一位
        $this->assertTrue($this->generator->isValidCode('AGT20250101'));   // 最小有效长度
        $this->assertTrue($this->generator->isValidCode('AGT1234567890')); // 最大有效长度
        $this->assertFalse($this->generator->isValidCode('AGT12345678901')); // 刚好多一位
    }

    public function testIsValidCodeWithNullAndSpecialCharacters(): void
    {
        $this->assertFalse($this->generator->isValidCode('AGT2025-01-01'));
        $this->assertFalse($this->generator->isValidCode('AGT 20250101'));
        $this->assertFalse($this->generator->isValidCode('AGT20250101@'));
    }

    private function setupRepositoryToReturnNoAgent(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->agentRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('where')
            ->willReturn($queryBuilder)
        ;
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->willReturn($queryBuilder)
        ;
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->willReturn($queryBuilder)
        ;
        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->willReturn($queryBuilder)
        ;
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query)
        ;

        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(null)
        ;

        $this->agentRepository->expects($this->atLeast(1))
            ->method('findOneBy')
            ->with(self::callback(function ($criteria) {
                return is_array($criteria) && isset($criteria['code']);
            }))
            ->willReturn(null)
        ;
    }

    private function setupRepositoryToReturnAgent(Agent $agent): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->agentRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('where')
            ->willReturn($queryBuilder)
        ;
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->willReturn($queryBuilder)
        ;
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->willReturn($queryBuilder)
        ;
        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->willReturn($queryBuilder)
        ;
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query)
        ;

        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($agent)
        ;

        $this->agentRepository->expects($this->atLeast(1))
            ->method('findOneBy')
            ->with(self::callback(function ($criteria) {
                return is_array($criteria) && isset($criteria['code']);
            }))
            ->willReturn(null)
        ;
    }

    private function setupRepositoryWithCollision(Agent $firstAgent, Agent $secondAgent): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->agentRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('where')
            ->willReturn($queryBuilder)
        ;
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->willReturn($queryBuilder)
        ;
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->willReturn($queryBuilder)
        ;
        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->willReturn($queryBuilder)
        ;
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query)
        ;

        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($firstAgent)
        ;

        // 允许更多次调用findByCode以满足uniqueness检查
        $this->agentRepository->expects($this->atLeast(2))
            ->method('findOneBy')
            ->willReturnCallback(function (array $criteria) use ($firstAgent) {
                $code = $criteria['code'] ?? '';
                // 第一次检查返回已存在的agent，后续检查返回null表示不存在
                static $callCount = 0;
                /** @var int $callCount */
                ++$callCount;

                return 1 === $callCount ? $firstAgent : null;
            })
        ;
    }

    private function setupRepositoryAlwaysReturnAgent(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $agent = new Agent();

        $this->agentRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($queryBuilder)
        ;

        $queryBuilder->expects($this->once())
            ->method('where')
            ->willReturn($queryBuilder)
        ;
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->willReturn($queryBuilder)
        ;
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->willReturn($queryBuilder)
        ;
        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->willReturn($queryBuilder)
        ;
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query)
        ;

        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($agent)
        ;

        // 模拟前100次调用都返回存在的代理，最后返回null
        $this->agentRepository->expects($this->atLeast(1))
            ->method('findOneBy')
            ->willReturnCallback(function () {
                static $callCount = 0;
                /** @var int $callCount */
                ++$callCount;

                return $callCount <= 100 ? new Agent() : null;
            })
        ;
    }
}
