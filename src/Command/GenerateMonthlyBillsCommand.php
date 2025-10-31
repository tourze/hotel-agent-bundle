<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Command;

use Brick\Math\BigDecimal;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Enum\AgentStatusEnum;
use Tourze\HotelAgentBundle\Enum\OrderStatusEnum;
use Tourze\HotelAgentBundle\Repository\AgentRepository;
use Tourze\HotelAgentBundle\Repository\OrderRepository;
use Tourze\HotelAgentBundle\Service\AgentBillService;

/**
 * 自动生成代理月结账单的定时任务
 */
#[AsCommand(name: self::NAME, description: '自动生成代理月结账单', help: <<<'TXT'

    该命令用于自动生成代理月结账单。

    使用方式：
      php bin/console app:generate-monthly-bills                    # 生成上个月的账单
      php bin/console app:generate-monthly-bills 2024-01            # 生成指定月份的账单
      php bin/console app:generate-monthly-bills --force            # 强制重新生成
      php bin/console app:generate-monthly-bills --dry-run          # 试运行模式

    建议在每月1日通过cron定时执行：
    0 2 1 * * php /path/to/bin/console app:generate-monthly-bills
                
    TXT)]
#[WithMonologChannel(channel: 'hotel_agent')]
class GenerateMonthlyBillsCommand extends Command
{
    public const NAME = 'app:generate-monthly-bills';

    public function __construct(
        private readonly AgentBillService $agentBillService,
        private readonly LoggerInterface $logger,
        private readonly AgentRepository $agentRepository,
        private readonly OrderRepository $orderRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('billMonth', InputArgument::OPTIONAL, '账单月份 (YYYY-MM格式)', null)
            ->addOption('force', 'f', InputOption::VALUE_NONE, '强制重新生成已存在的账单')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, '试运行模式，不实际创建账单')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $billMonthArg = $input->getArgument('billMonth');
        $force = $input->getOption('force');
        $dryRun = $input->getOption('dry-run');

        $billMonth = $this->normalizeBillMonth(is_string($billMonthArg) ? $billMonthArg : null);

        if (!$this->validateBillMonthFormat($billMonth)) {
            $io->error('账单月份格式错误，请使用 YYYY-MM 格式');

            return Command::FAILURE;
        }

        $io->title("生成代理月结账单: {$billMonth}");

        if (true === $dryRun) {
            $io->note('试运行模式 - 不会实际创建账单');
        }

        try {
            $startTime = microtime(true);

            if (true === $dryRun) {
                return $this->handleDryRunMode($io, $billMonth);
            }

            return $this->handleNormalMode($io, $billMonth, $startTime);
        } catch (\Throwable $e) {
            $this->handleError($io, $billMonth, $e);

            return Command::FAILURE;
        }
    }

    private function normalizeBillMonth(?string $billMonth): string
    {
        return $billMonth ?? (new \DateTime('first day of last month'))->format('Y-m');
    }

    private function validateBillMonthFormat(string $billMonth): bool
    {
        return 1 === preg_match('/^\d{4}-\d{2}$/', $billMonth);
    }

    private function handleDryRunMode(SymfonyStyle $io, string $billMonth): int
    {
        $result = $this->dryRunBillGeneration($billMonth);
        $io->success(sprintf('试运行完成：将生成 %d 个代理的账单', $result['agentCount']));

        if ([] !== $result['agents']) {
            $this->displayDryRunTable($io, $result['agents']);
        }

        $this->logCompletion($billMonth, $result['agentCount'], true);

        return Command::SUCCESS;
    }

    private function handleNormalMode(SymfonyStyle $io, string $billMonth, float $startTime): int
    {
        $generatedBills = $this->agentBillService->generateMonthlyBills($billMonth);
        $duration = round(microtime(true) - $startTime, 2);

        if ([] === $generatedBills) {
            $io->warning('没有生成任何账单 - 可能没有符合条件的代理或订单');

            return Command::SUCCESS;
        }

        $io->success(sprintf('成功生成 %d 个代理账单，耗时 %s 秒', count($generatedBills), $duration));
        $this->displayGeneratedBillsTable($io, $generatedBills);
        $this->logCompletion($billMonth, count($generatedBills), false);

        return Command::SUCCESS;
    }

    /**
     * @param array<int, array<string, mixed>> $agents
     */
    private function displayDryRunTable(SymfonyStyle $io, array $agents): void
    {
        $tableData = array_map(function ($agent) {
            $commissionAmount = isset($agent['commissionAmount']) && is_numeric($agent['commissionAmount'])
                ? (float) $agent['commissionAmount']
                : 0.0;

            return [
                $agent['code'],
                $agent['companyName'],
                $agent['orderCount'],
                '￥' . number_format($commissionAmount, 2),
            ];
        }, $agents);

        $io->table(['代理编号', '公司名称', '预计订单数', '预计佣金'], $tableData);
    }

    /**
     * @param AgentBill[] $generatedBills
     */
    private function displayGeneratedBillsTable(SymfonyStyle $io, array $generatedBills): void
    {
        $tableData = [];
        $totalCommission = BigDecimal::zero();

        foreach ($generatedBills as $bill) {
            $agent = $bill->getAgent();
            $tableData[] = [
                $agent?->getCode() ?? 'N/A',
                $agent?->getCompanyName() ?? 'N/A',
                $bill->getOrderCount(),
                '￥' . number_format((float) $bill->getTotalAmount(), 2),
                '￥' . number_format((float) $bill->getCommissionAmount(), 2),
                $bill->getStatus()->getLabel(),
            ];

            $totalCommission = $totalCommission->plus(BigDecimal::of($bill->getCommissionAmount()));
        }

        $io->table(['代理编号', '公司名称', '订单数', '订单总额', '佣金金额', '状态'], $tableData);
        $io->info(sprintf('总佣金金额: ￥%s', number_format($totalCommission->toFloat(), 2)));
    }

    private function logCompletion(string $billMonth, int $billCount, bool $dryRun): void
    {
        $this->logger->info('月结账单生成任务完成', [
            'billMonth' => $billMonth,
            'billCount' => $billCount,
            'dryRun' => $dryRun,
        ]);
    }

    private function handleError(SymfonyStyle $io, string $billMonth, \Throwable $e): void
    {
        $io->error('生成账单时发生错误: ' . $e->getMessage());
        $this->logger->error('月结账单生成失败', [
            'billMonth' => $billMonth,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /**
     * 试运行账单生成
     */
    /**
     * @return array{agentCount: int, agents: array<int, array<string, mixed>>}
     */
    private function dryRunBillGeneration(string $billMonth): array
    {
        $dateRange = $this->createDateRange($billMonth);
        $activeAgents = $this->getActiveAgents();

        $agents = [];
        $agentCount = 0;

        foreach ($activeAgents as $agent) {
            $orders = $this->getAgentOrdersForPeriod($agent, $dateRange);

            if ([] === $orders) {
                continue;
            }

            $agentData = $this->calculateAgentBillData($agent, $orders);
            $agents[] = $agentData;
            ++$agentCount;
        }

        return [
            'agentCount' => $agentCount,
            'agents' => $agents,
        ];
    }

    /**
     * @return array{start: \DateTime, end: \DateTime}
     */
    private function createDateRange(string $billMonth): array
    {
        $startDate = new \DateTime($billMonth . '-01 00:00:00');
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');
        $endDate->setTime(23, 59, 59);

        return ['start' => $startDate, 'end' => $endDate];
    }

    /**
     * @return Agent[]
     */
    private function getActiveAgents(): array
    {
        /** @var Agent[] */
        return $this->agentRepository->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', AgentStatusEnum::ACTIVE)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param array{start: \DateTime, end: \DateTime} $dateRange
     * @return Order[]
     */
    private function getAgentOrdersForPeriod(Agent $agent, array $dateRange): array
    {
        /** @var Order[] */
        return $this->orderRepository->createQueryBuilder('o')
            ->andWhere('o.agent = :agent')
            ->andWhere('o.status = :status')
            ->andWhere('o.createTime BETWEEN :startDate AND :endDate')
            ->setParameter('agent', $agent)
            ->setParameter('status', OrderStatusEnum::CONFIRMED)
            ->setParameter('startDate', $dateRange['start'])
            ->setParameter('endDate', $dateRange['end'])
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param Order[] $orders
     * @return array<string, mixed>
     */
    private function calculateAgentBillData(Agent $agent, array $orders): array
    {
        $totalAmount = BigDecimal::zero();
        $totalProfit = BigDecimal::zero();

        foreach ($orders as $order) {
            $totalAmount = $totalAmount->plus(BigDecimal::of($order->getTotalAmount()));

            foreach ($order->getOrderItems() as $item) {
                $itemProfit = BigDecimal::of($item->getAmount())->minus(BigDecimal::of($item->getCostPrice()));
                $totalProfit = $totalProfit->plus($itemProfit);
            }
        }

        $commissionAmount = $totalProfit->multipliedBy(
            BigDecimal::of($agent->getCommissionRate())->dividedBy(BigDecimal::of('100'), 4)
        )->toScale(2);

        return [
            'code' => $agent->getCode(),
            'companyName' => $agent->getCompanyName(),
            'orderCount' => count($orders),
            'totalAmount' => $totalAmount->toScale(2)->__toString(),
            'commissionAmount' => $commissionAmount->__toString(),
        ];
    }
}
