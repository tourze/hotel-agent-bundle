<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\HotelAgentBundle\Command\CheckExpiredAgentsCommand;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(CheckExpiredAgentsCommand::class)]
#[RunTestsInSeparateProcesses]
final class CheckExpiredAgentsCommandTest extends AbstractCommandTestCase
{
    protected function getCommandTester(): CommandTester
    {
        $command = self::getService(CheckExpiredAgentsCommand::class);

        return new CommandTester($command);
    }

    protected function onSetUp(): void
    {
    }

    public function testExecuteWithNoExpiredAgents(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('代理账户过期检查', $output);
        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testExecuteWithDryRun(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--dry-run' => true]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('代理账户过期检查', $output);
        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testExecuteWithCustomDays(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--days' => 30]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('代理账户过期检查', $output);
        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testCommandName(): void
    {
        $command = self::getService(CheckExpiredAgentsCommand::class);
        $this->assertSame('agent:check-expired', CheckExpiredAgentsCommand::NAME);
        $this->assertSame('agent:check-expired', $command->getName());
    }

    public function testCommandConfiguration(): void
    {
        $command = self::getService(CheckExpiredAgentsCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('dry-run'));
        $this->assertTrue($definition->hasOption('days'));

        $daysOption = $definition->getOption('days');
        $this->assertSame(7, $daysOption->getDefault());
    }

    public function testOptionDryRun(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('代理账户过期检查', $output);
    }

    public function testOptionDays(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--days' => 15]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('代理账户过期检查', $output);
    }
}
