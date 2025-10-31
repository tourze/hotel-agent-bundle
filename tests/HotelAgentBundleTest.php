<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\HotelAgentBundle\HotelAgentBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(HotelAgentBundle::class)]
#[RunTestsInSeparateProcesses]
final class HotelAgentBundleTest extends AbstractBundleTestCase
{
}
