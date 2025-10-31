<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\HotelAgentBundle\Command\GenerateMonthlyBillsCommand;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(GenerateMonthlyBillsCommand::class)]
#[RunTestsInSeparateProcesses]
final class GenerateMonthlyBillsCommandTest extends AbstractCommandTestCase
{
    protected function getCommandTester(): CommandTester
    {
        $command = self::getService(GenerateMonthlyBillsCommand::class);

        return new CommandTester($command);
    }

    protected function onSetUp(): void
    {
    }

    public function testExecuteWithDefaultMonth(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('生成代理月结账单:', $output);
        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testExecuteWithSpecificMonth(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'billMonth' => '2024-01',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('生成代理月结账单: 2024-01', $output);
        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testExecuteWithInvalidMonthFormat(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'billMonth' => 'invalid-format',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('账单月份格式错误', $output);
        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }

    public function testExecuteWithDryRun(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            '--dry-run' => true,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('试运行模式', $output);
        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testExecuteWithForce(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            '--force' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testCommandName(): void
    {
        $this->assertSame('app:generate-monthly-bills', GenerateMonthlyBillsCommand::NAME);
        $command = self::getService(GenerateMonthlyBillsCommand::class);
        $this->assertSame('app:generate-monthly-bills', $command->getName());
    }

    public function testCommandConfiguration(): void
    {
        $command = self::getService(GenerateMonthlyBillsCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('billMonth'));
        $this->assertTrue($definition->hasOption('force'));
        $this->assertTrue($definition->hasOption('dry-run'));

        $billMonthArgument = $definition->getArgument('billMonth');
        $this->assertFalse($billMonthArgument->isRequired());
    }

    public function testArgumentBillMonth(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['billMonth' => '2024-01']);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('生成代理月结账单: 2024-01', $output);
    }

    public function testOptionForce(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--force' => true]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testOptionDryRun(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('试运行模式', $output);
    }
}
