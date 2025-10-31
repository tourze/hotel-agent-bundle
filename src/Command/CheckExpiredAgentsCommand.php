<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Enum\AgentStatusEnum;
use Tourze\HotelAgentBundle\Repository\AgentRepository;

/**
 * 检查过期代理命令
 *
 * 用于定期检查并更新过期代理账户状态
 */
#[AsCommand(name: self::NAME, description: '检查并更新过期的代理账户状态', help: <<<'TXT'

    这个命令会检查所有代理账户的有效期，并执行以下操作：
    1. 将已过期但状态不是"过期"的代理标记为过期
    2. 显示即将过期的代理列表（默认7天内）

    使用示例：
      php bin/console agent:check-expired
      php bin/console agent:check-expired --dry-run
      php bin/console agent:check-expired --days=30
                
    TXT)]
class CheckExpiredAgentsCommand extends Command
{
    public const NAME = 'agent:check-expired';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AgentRepository $agentRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, '仅显示将要更新的代理，不实际更新')
            ->addOption('days', null, InputOption::VALUE_REQUIRED, '提前几天提醒即将过期的代理', 7)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $daysOption = $input->getOption('days');
        $days = is_numeric($daysOption) ? (int) $daysOption : 7;

        $io->title('代理账户过期检查');

        $this->processExpiredAgents($io, $dryRun);
        $this->processExpiringAgents($io, $days);
        $this->displayStatistics($io);

        return Command::SUCCESS;
    }

    private function processExpiredAgents(SymfonyStyle $io, bool $dryRun): void
    {
        $expiredAgents = $this->agentRepository->findExpiredAgents();

        if ([] === $expiredAgents) {
            $io->info('没有发现需要更新状态的过期代理');

            return;
        }

        $io->section('已过期的代理账户');
        $tableData = $this->buildExpiredAgentsTableData($expiredAgents);
        $updatedCount = $this->updateExpiredAgents($expiredAgents, $dryRun);

        $io->table(['代理编号', '公司名称', '联系人', '过期日期', '当前状态'], $tableData);
        $this->displayExpiredAgentsResult($io, $dryRun, $updatedCount, count($expiredAgents));
    }

    /**
     * @param Agent[] $expiredAgents
     * @return array<int, array<int, string>>
     */
    private function buildExpiredAgentsTableData(array $expiredAgents): array
    {
        $tableData = [];
        foreach ($expiredAgents as $agent) {
            $tableData[] = [
                $agent->getCode(),
                $agent->getCompanyName(),
                $agent->getContactPerson(),
                $agent->getExpiryDate()?->format('Y-m-d') ?? 'N/A',
                $agent->getStatus()->getLabel(),
            ];
        }

        return $tableData;
    }

    /**
     * @param Agent[] $expiredAgents
     */
    private function updateExpiredAgents(array $expiredAgents, bool $dryRun): int
    {
        if ($dryRun) {
            return 0;
        }

        $updatedCount = 0;
        foreach ($expiredAgents as $agent) {
            $agent->setStatus(AgentStatusEnum::EXPIRED);
            $this->entityManager->persist($agent);
            ++$updatedCount;
        }

        $this->entityManager->flush();

        return $updatedCount;
    }

    private function displayExpiredAgentsResult(SymfonyStyle $io, bool $dryRun, int $updatedCount, int $totalExpired): void
    {
        if ($dryRun) {
            $io->info(sprintf('干运行模式：发现 %d 个需要更新状态的过期代理', $totalExpired));
        } else {
            $io->success(sprintf('已更新 %d 个过期代理的状态', $updatedCount));
        }
    }

    private function processExpiringAgents(SymfonyStyle $io, int $days): void
    {
        $expiringAgents = $this->agentRepository->findAgentsExpiringInDays($days);

        if ([] === $expiringAgents) {
            $io->info(sprintf('没有发现即将在 %d 天内过期的代理', $days));

            return;
        }

        $io->section(sprintf('即将过期的代理账户（%d天内）', $days));
        $tableData = $this->buildExpiringAgentsTableData($expiringAgents);

        $io->table(['代理编号', '公司名称', '联系人', '过期日期', '剩余天数'], $tableData);
        $io->warning(sprintf('发现 %d 个即将过期的代理账户，请及时联系续期', count($expiringAgents)));
    }

    /**
     * @param Agent[] $expiringAgents
     * @return array<int, array<int, string>>
     */
    private function buildExpiringAgentsTableData(array $expiringAgents): array
    {
        $tableData = [];
        foreach ($expiringAgents as $agent) {
            $expiryDate = $agent->getExpiryDate();
            $daysToExpiry = $expiryDate?->diff(new \DateTime())->days ?? 0;
            $tableData[] = [
                $agent->getCode(),
                $agent->getCompanyName(),
                $agent->getContactPerson(),
                $expiryDate?->format('Y-m-d') ?? 'N/A',
                $daysToExpiry . ' 天',
            ];
        }

        return $tableData;
    }

    private function displayStatistics(SymfonyStyle $io): void
    {
        $io->section('统计信息');

        $statistics = [
            ['总代理数' => $this->agentRepository->count([])],
            ['激活状态' => $this->agentRepository->count(['status' => AgentStatusEnum::ACTIVE])],
            ['冻结状态' => $this->agentRepository->count(['status' => AgentStatusEnum::FROZEN])],
            ['禁用状态' => $this->agentRepository->count(['status' => AgentStatusEnum::DISABLED])],
            ['过期状态' => $this->agentRepository->count(['status' => AgentStatusEnum::EXPIRED])],
        ];

        $io->definitionList(...$statistics);
    }
}
