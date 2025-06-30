<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\HotelAgentBundle\DependencyInjection\HotelAgentExtension;

final class HotelAgentExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $extension = new HotelAgentExtension();
        $container = new ContainerBuilder();
        
        $extension->load([], $container);
        
        self::assertInstanceOf(ContainerBuilder::class, $container);
    }
}