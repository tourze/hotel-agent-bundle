<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\HotelAgentBundle\Service\AttributeControllerLoader;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
#[RunTestsInSeparateProcesses]
final class AttributeControllerLoaderTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 空实现，这个测试类不需要额外的初始化
    }

    public function testLoadReturnsRouteCollection(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $result = $loader->load('test');
        self::assertGreaterThanOrEqual(0, $result->count());
    }

    public function testAutoloadReturnsRouteCollection(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $result = $loader->autoload();
        self::assertGreaterThanOrEqual(0, $result->count());
    }

    public function testSupportsReturnsFalse(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        self::assertFalse($loader->supports('test'));
        self::assertFalse($loader->supports('test', 'some_type'));
    }
}
