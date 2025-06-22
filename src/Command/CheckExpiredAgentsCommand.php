<?php

namespace Tourze\HotelAgentBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\HotelAgentBundle\Enum\AgentStatusEnum;
use Tourze\HotelAgentBundle\Repository\AgentRepository;

/**
 * 检查过期代理命令
 * 
 * 用于定期检查并更新过期代理账户状态
 */
#[AsCommand(
    name: self::NAME,
    description: '检查并更新过期的代理账户状态',
)]
class CheckExpiredAgentsCommand extends Command
{
    public const NAME = 'agent:check-expired';
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AgentRepository $agentRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, '仅显示将要更新的代理，不实际更新')
            ->addOption('days', null, InputOption::VALUE_REQUIRED, '提前几天提醒即将过期的代理', 7)
            ->setHelp('
这个命令会检查所有代理账户的有效期，并执行以下操作：
1. 将已过期但状态不是"过期"的代理标记为过期
2. 显示即将过期的代理列表（默认7天内）

使用示例：
  php bin/console agent:check-expired
  php bin/console agent:check-expired --dry-run
  php bin/console agent:check-expired --days=30
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $days = (int) $input->getOption('days');

        $io->title('代理账户过期检查');

        // 检查已过期的代理
        $expiredAgents = $this->agentRepository->findExpiredAgents();
        $updatedCount = 0;

        if (!empty($expiredAgents)) {
            $io->section('已过期的代理账户');
            
            $tableData = [];
            foreach ($expiredAgents as $agent) {
                $tableData[] = [
                    $agent->getCode(),
                    $agent->getCompanyName(),
                    $agent->getContactPerson(),
                    $agent->getExpiryDate()->format('Y-m-d'),
                    $agent->getStatus()->getLabel(),
                ];

                if (false === $dryRun) {
                    $agent->setStatus(AgentStatusEnum::EXPIRED);
                    $this->entityManager->persist($agent);
                    $updatedCount++;
                }
            }

            $io->table(['代理编号', '公司名称', '联系人', '过期日期', '当前状态'], $tableData);

            if (false === $dryRun) {
                $this->entityManager->flush();
                $io->success(sprintf('已更新 %d 个过期代理的状态', $updatedCount));
            } else {
                $io->info(sprintf('干运行模式：发现 %d 个需要更新状态的过期代理', count($expiredAgents)));
            }
        } else {
            $io->info('没有发现需要更新状态的过期代理');
        }

        // 检查即将过期的代理
        $expiringAgents = $this->agentRepository->findAgentsExpiringInDays($days);
        
        if (!empty($expiringAgents)) {
            $io->section(sprintf('即将过期的代理账户（%d天内）', $days));
            
            $tableData = [];
            foreach ($expiringAgents as $agent) {
                $daysToExpiry = $agent->getExpiryDate()->diff(new \DateTime())->days;
                $tableData[] = [
                    $agent->getCode(),
                    $agent->getCompanyName(),
                    $agent->getContactPerson(),
                    $agent->getExpiryDate()->format('Y-m-d'),
                    $daysToExpiry . ' 天',
                ];
            }

            $io->table(['代理编号', '公司名称', '联系人', '过期日期', '剩余天数'], $tableData);
            $io->warning(sprintf('发现 %d 个即将过期的代理账户，请及时联系续期', count($expiringAgents)));
        } else {
            $io->info(sprintf('没有发现即将在 %d 天内过期的代理', $days));
        }

        // 统计信息
        $io->section('统计信息');
        $totalAgents = $this->agentRepository->count([]);
        $activeAgents = $this->agentRepository->count(['status' => AgentStatusEnum::ACTIVE]);
        $frozenAgents = $this->agentRepository->count(['status' => AgentStatusEnum::FROZEN]);
        $disabledAgents = $this->agentRepository->count(['status' => AgentStatusEnum::DISABLED]);
        $expiredAgents = $this->agentRepository->count(['status' => AgentStatusEnum::EXPIRED]);

        $io->definitionList(
            ['总代理数' => $totalAgents],
            ['激活状态' => $activeAgents],
            ['冻结状态' => $frozenAgents],
            ['禁用状态' => $disabledAgents],
            ['过期状态' => $expiredAgents]
        );

        return Command::SUCCESS;
    }
} 