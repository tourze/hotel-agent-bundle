<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class HotelAgentExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
