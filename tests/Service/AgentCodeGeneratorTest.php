<?php

namespace Tourze\HotelAgentBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Repository\AgentRepository;
use Tourze\HotelAgentBundle\Service\AgentCodeGenerator;

class AgentCodeGeneratorTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;
    /** @var AgentRepository&MockObject */
    private AgentRepository $agentRepository;
    private AgentCodeGenerator $generator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->agentRepository = $this->createMock(AgentRepository::class);
        $this->generator = new AgentCodeGenerator($this->agentRepository);
    }

    public function test_generateCode_creates_first_code_of_day(): void
    {
        $this->setupRepositoryToReturnNoAgent();

        $code = $this->generator->generateCode();

        $expectedPrefix = 'AGT' . date('Ymd');
        $this->assertStringStartsWith($expectedPrefix, $code);
        $this->assertStringEndsWith('01', $code);
        $this->assertSame(strlen($expectedPrefix) + 2, strlen($code));
    }

    public function test_generateCode_increments_sequence_number(): void
    {
        $existingAgent = new Agent();
        $existingAgent->setCode('AGT' . date('Ymd') . '03');

        $this->setupRepositoryToReturnAgent($existingAgent);

        $code = $this->generator->generateCode();

        $expectedCode = 'AGT' . date('Ymd') . '04';
        $this->assertSame($expectedCode, $code);
    }

    public function test_generateCode_uses_timestamp_when_sequence_exceeds_99(): void
    {
        $existingAgent = new Agent();
        $existingAgent->setCode('AGT' . date('Ymd') . '99');

        $this->setupRepositoryToReturnAgent($existingAgent);

        $code = $this->generator->generateCode();

        $expectedPrefix = 'AGT' . date('Ymd');
        $this->assertStringStartsWith($expectedPrefix, $code);
        $this->assertGreaterThan(strlen($expectedPrefix) + 2, strlen($code));
    }

    public function test_generateCode_ensures_uniqueness_with_collision(): void
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

    public function test_generateCode_uses_microtime_as_fallback(): void
    {
        // 模拟所有可能的编号都被占用的情况
        $this->setupRepositoryAlwaysReturnAgent();

        $code = $this->generator->generateCode();

        $this->assertStringStartsWith('AGT', $code);
        $this->assertMatchesRegularExpression('/^AGT\d{8,}$/', $code);
    }

    public function test_isValidCode_with_valid_codes(): void
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

    public function test_isValidCode_with_invalid_codes(): void
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

    public function test_isValidCode_with_edge_cases(): void
    {
        $this->assertFalse($this->generator->isValidCode('AGT2025010'));  // 刚好少一位
        $this->assertTrue($this->generator->isValidCode('AGT20250101'));   // 最小有效长度
        $this->assertTrue($this->generator->isValidCode('AGT1234567890')); // 最大有效长度
        $this->assertFalse($this->generator->isValidCode('AGT12345678901')); // 刚好多一位
    }

    public function test_isValidCode_with_null_and_special_characters(): void
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
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('where')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        $this->agentRepository->expects($this->atLeast(1))
            ->method('findOneBy')
            ->with($this->callback(function ($criteria) {
                return is_array($criteria) && isset($criteria['code']);
            }))
            ->willReturn(null);
    }

    private function setupRepositoryToReturnAgent(Agent $agent): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->agentRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('where')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($agent);

        $this->agentRepository->expects($this->atLeast(1))
            ->method('findOneBy')
            ->with($this->callback(function ($criteria) {
                return is_array($criteria) && isset($criteria['code']);
            }))
            ->willReturn(null);
    }

    private function setupRepositoryWithCollision(Agent $firstAgent, Agent $secondAgent): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->agentRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('where')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($firstAgent);

        // 允许更多次调用findByCode以满足uniqueness检查
        $this->agentRepository->expects($this->atLeast(2))
            ->method('findOneBy')
            ->willReturnCallback(function (array $criteria) use ($firstAgent) {
                $code = $criteria['code'] ?? '';
                // 第一次检查返回已存在的agent，后续检查返回null表示不存在
                static $callCount = 0;
                $callCount++;
                return $callCount === 1 ? $firstAgent : null;
            });
    }

    private function setupRepositoryAlwaysReturnAgent(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $agent = new Agent();

        $this->agentRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('where')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($agent);

        // 模拟前100次调用都返回存在的代理，最后返回null
        $this->agentRepository->expects($this->atLeast(1))
            ->method('findOneBy')
            ->willReturnCallback(function () {
                static $callCount = 0;
                $callCount++;
                return $callCount <= 100 ? new Agent() : null;
            });
    }
}
