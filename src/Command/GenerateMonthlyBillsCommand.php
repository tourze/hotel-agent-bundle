<?php

namespace Tourze\HotelAgentBundle\Command;

use Brick\Math\BigDecimal;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\HotelAgentBundle\Enum\AgentStatusEnum;
use Tourze\HotelAgentBundle\Enum\OrderStatusEnum;
use Tourze\HotelAgentBundle\Repository\AgentRepository;
use Tourze\HotelAgentBundle\Repository\OrderRepository;
use Tourze\HotelAgentBundle\Service\AgentBillService;

/**
 * 自动生成代理月结账单的定时任务
 */
#[AsCommand(
    name: self::NAME,
    description: '自动生成代理月结账单'
)]
class GenerateMonthlyBillsCommand extends Command
{
    public const NAME = 'app:generate-monthly-bills';
    public function __construct(
        private readonly AgentBillService $agentBillService,
        private readonly LoggerInterface $logger,
        private readonly AgentRepository $agentRepository,
        private readonly OrderRepository $orderRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('billMonth', InputArgument::OPTIONAL, '账单月份 (YYYY-MM格式)', null)
            ->addOption('force', 'f', InputOption::VALUE_NONE, '强制重新生成已存在的账单')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, '试运行模式，不实际创建账单')
            ->setHelp('
该命令用于自动生成代理月结账单。

使用方式：
  php bin/console app:generate-monthly-bills                    # 生成上个月的账单
  php bin/console app:generate-monthly-bills 2024-01            # 生成指定月份的账单
  php bin/console app:generate-monthly-bills --force            # 强制重新生成
  php bin/console app:generate-monthly-bills --dry-run          # 试运行模式

建议在每月1日通过cron定时执行：
0 2 1 * * php /path/to/bin/console app:generate-monthly-bills
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $billMonth = $input->getArgument('billMonth');
        $force = $input->getOption('force');
        $dryRun = $input->getOption('dry-run');

        // 如果没有指定月份，使用上个月
        if (null === $billMonth) {
            $billMonth = (new \DateTime('first day of last month'))->format('Y-m');
        }

        // 验证月份格式
        if (!preg_match('/^\d{4}-\d{2}$/', $billMonth)) {
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
                $result = $this->dryRunBillGeneration($billMonth);
                $io->success(sprintf(
                    '试运行完成：将生成 %d 个代理的账单',
                    $result['agentCount']
                ));

                if (!empty($result['agents'])) {
                    $io->table(
                        ['代理编号', '公司名称', '预计订单数', '预计佣金'],
                        array_map(function ($agent) {
                            return [
                                $agent['code'],
                                $agent['companyName'],
                                $agent['orderCount'],
                                '￥' . number_format($agent['commissionAmount'], 2)
                            ];
                        }, $result['agents'])
                    );
                }
            } else {
                $generatedBills = $this->agentBillService->generateMonthlyBills($billMonth);

                $endTime = microtime(true);
                $duration = round($endTime - $startTime, 2);

                if (empty($generatedBills)) {
                    $io->warning('没有生成任何账单 - 可能没有符合条件的代理或订单');
                    return Command::SUCCESS;
                }

                $io->success(sprintf(
                    '成功生成 %d 个代理账单，耗时 %s 秒',
                    count($generatedBills),
                    $duration
                ));

                // 显示生成的账单详情
                $tableData = [];
                $totalCommission = BigDecimal::zero();

                foreach ($generatedBills as $bill) {
                    $tableData[] = [
                        $bill->getAgent()->getCode(),
                        $bill->getAgent()->getCompanyName(),
                        $bill->getOrderCount(),
                        '￥' . number_format($bill->getTotalAmount(), 2),
                        '￥' . number_format($bill->getCommissionAmount(), 2),
                        $bill->getStatus()->getLabel()
                    ];

                    $totalCommission = $totalCommission->plus(BigDecimal::of($bill->getCommissionAmount()));
                }

                $io->table(
                    ['代理编号', '公司名称', '订单数', '订单总额', '佣金金额', '状态'],
                    $tableData
                );

                $io->info(sprintf('总佣金金额: ￥%s', number_format($totalCommission->toFloat(), 2)));
            }

            $this->logger->info('月结账单生成任务完成', [
                'billMonth' => $billMonth,
                'billCount' => true === $dryRun ? $result['agentCount'] : count($generatedBills),
                'dryRun' => $dryRun
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('生成账单时发生错误: ' . $e->getMessage());
            $this->logger->error('月结账单生成失败', [
                'billMonth' => $billMonth,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * 试运行账单生成
     */
    private function dryRunBillGeneration(string $billMonth): array
    {
        $startDate = new \DateTime($billMonth . '-01 00:00:00');
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');
        $endDate->setTime(23, 59, 59);

        $agents = [];
        $agentCount = 0;

        // 获取代理和订单数据
        $activeAgents = $this->agentRepository->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', AgentStatusEnum::ACTIVE)
            ->getQuery()
            ->getResult();

        foreach ($activeAgents as $agent) {
            $orders = $this->orderRepository->createQueryBuilder('o')
                ->andWhere('o.agent = :agent')
                ->andWhere('o.status = :status')
                ->andWhere('o.createTime BETWEEN :startDate AND :endDate')
                ->setParameter('agent', $agent)
                ->setParameter('status', OrderStatusEnum::CONFIRMED)
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->getQuery()
                ->getResult();

            if (!empty($orders)) {
                $totalAmount = BigDecimal::zero();
                $totalProfit = BigDecimal::zero();

                foreach ($orders as $order) {
                    $totalAmount = $totalAmount->plus(BigDecimal::of($order->getTotalAmount()));
                    foreach ($order->getItems() as $item) {
                        $itemProfit = BigDecimal::of($item->getAmount())->minus(BigDecimal::of($item->getCostPrice()));
                        $totalProfit = $totalProfit->plus($itemProfit);
                    }
                }

                $commissionAmount = $totalProfit->multipliedBy(
                    BigDecimal::of($agent->getCommissionRate())->dividedBy(BigDecimal::of('100'), 4)
                )->toScale(2);

                $agents[] = [
                    'code' => $agent->getCode(),
                    'companyName' => $agent->getCompanyName(),
                    'orderCount' => count($orders),
                    'totalAmount' => $totalAmount->toScale(2)->__toString(),
                    'commissionAmount' => $commissionAmount->__toString()
                ];

                $agentCount++;
            }
        }

        return [
            'agentCount' => $agentCount,
            'agents' => $agents
        ];
    }
}
