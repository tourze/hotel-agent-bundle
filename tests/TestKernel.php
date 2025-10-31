<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use SymfonyTestingFramework\Kernel;
use Tourze\HotelAgentBundle\HotelAgentBundle;
use Tourze\HotelProfileBundle\HotelProfileBundle;

/**
 * @internal
 * @coversNothing
 * @phpstan-ignore-next-line forbiddenExtendOfNonAbstractClass
 */
class TestKernel extends Kernel
{
    public function __construct()
    {
        parent::__construct(
            'test',
            true,
            __DIR__ . '/../',
            [
                HotelProfileBundle::class => ['all' => true],
                HotelAgentBundle::class => ['all' => true],
            ]
        );
    }

    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // 注册测试实体映射
        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'HotelProfileBundle' => [
                        'type' => 'attribute',
                        'is_bundle' => true,
                        'prefix' => 'Tourze\HotelProfileBundle\Entity',
                        'alias' => 'HotelProfileBundle',
                    ],
                    'HotelAgentBundle' => [
                        'type' => 'attribute',
                        'is_bundle' => true,
                        'prefix' => 'Tourze\HotelAgentBundle\Entity',
                        'alias' => 'HotelAgentBundle',
                    ],
                ],
            ],
        ]);

        // 禁用可能引起问题的配置
        $container->prependExtensionConfig('framework', [
            'test' => true,
            'session' => [
                'storage_factory_id' => 'session.storage.factory.mock_file',
            ],
        ]);
    }
}
