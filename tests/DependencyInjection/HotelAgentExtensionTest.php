<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\HotelAgentBundle\DependencyInjection\HotelAgentExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(HotelAgentExtension::class)]
final class HotelAgentExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    public function testLoadWithProdEnvironment(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');
        $extension = new HotelAgentExtension();

        // 验证扩展加载成功，不抛出异常
        $extension->load([], $container);

        // 验证生产环境配置
        $this->assertInstanceOf(HotelAgentExtension::class, $extension);
        $this->assertInstanceOf(ContainerBuilder::class, $container);
    }

    public function testLoadWithDevEnvironment(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'dev');
        $extension = new HotelAgentExtension();

        // 验证扩展加载成功，不抛出异常
        $extension->load([], $container);

        // 验证开发环境配置
        $this->assertInstanceOf(HotelAgentExtension::class, $extension);
        $this->assertInstanceOf(ContainerBuilder::class, $container);
    }

    public function testLoadWithTestEnvironment(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $extension = new HotelAgentExtension();

        // 验证扩展加载成功，不抛出异常
        $extension->load([], $container);

        // 验证测试环境配置
        $this->assertInstanceOf(HotelAgentExtension::class, $extension);
        $this->assertInstanceOf(ContainerBuilder::class, $container);
    }
}
