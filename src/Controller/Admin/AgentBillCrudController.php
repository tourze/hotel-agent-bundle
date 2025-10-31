<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Tourze\HotelAgentBundle\Entity\AgentBill;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;
use Tourze\HotelAgentBundle\Enum\SettlementTypeEnum;
use Tourze\HotelAgentBundle\Service\AgentBillService;

/**
 * 代理账单管理控制器
 * @extends AbstractCrudController<AgentBill>
 */
#[AdminCrud(routePath: '/hotel-agent/agent-bill', routeName: 'hotel_agent_agent_bill')]
final class AgentBillCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AgentBillService $agentBillService,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return AgentBill::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('代理账单')
            ->setEntityLabelInPlural('代理账单')
            ->setPageTitle('index', '代理账单管理')
            ->setPageTitle('new', '新建代理账单')
            ->setPageTitle('edit', '编辑代理账单')
            ->setPageTitle('detail', '代理账单详情')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->showEntityActionsInlined()
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('agent', '代理商')
                ->setFormTypeOptions([
                    'choice_label' => 'companyName',
                ])
                ->setCrudController(AgentCrudController::class),

            TextField::new('billMonth', '账单月份')
                ->setHelp('格式：YYYY-MM')
                ->setFormTypeOptions([
                    'attr' => ['placeholder' => '2024-01'],
                ]),

            IntegerField::new('orderCount', '订单数量')
                ->hideOnForm(),

            MoneyField::new('totalAmount', '订单总金额')
                ->setCurrency('CNY')
                ->hideOnForm(),

            TextField::new('commissionRate', '佣金比例')
                ->setFormTypeOptions([
                    'attr' => ['placeholder' => '10.00'],
                ])
                ->setHelp('百分比，如：10.00')
                ->hideOnForm(),

            MoneyField::new('commissionAmount', '佣金总额')
                ->setCurrency('CNY')
                ->hideOnForm(),

            ChoiceField::new('settlementType', '结算方式')
                ->setChoices(SettlementTypeEnum::cases())
                ->renderExpanded(false),

            ChoiceField::new('status', '账单状态')
                ->setChoices(BillStatusEnum::cases())
                ->renderExpanded(false)
                ->renderAsBadges([
                    BillStatusEnum::PENDING->value => 'warning',
                    BillStatusEnum::CONFIRMED->value => 'info',
                    BillStatusEnum::PAID->value => 'success',
                ]),

            DateTimeField::new('confirmTime', '确认时间')
                ->hideOnForm()
                ->hideOnIndex(),

            DateTimeField::new('payTime', '支付时间')
                ->hideOnForm()
                ->hideOnIndex(),

            TextField::new('paymentReference', '支付凭证号')
                ->hideOnForm()
                ->hideOnIndex(),

            DateTimeField::new('createTime', '创建时间')
                ->hideOnForm(),

            DateTimeField::new('updateTime', '更新时间')
                ->hideOnForm()
                ->hideOnIndex(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $confirmAction = Action::new('confirm', '确认账单', 'fa fa-check')
            ->linkToCrudAction('confirmBill')
            ->displayIf(static function (AgentBill $bill) {
                return BillStatusEnum::PENDING === $bill->getStatus();
            })
            ->setCssClass('btn btn-success')
        ;

        $recalculateAction = Action::new('recalculate', '重新计算', 'fa fa-calculator')
            ->linkToCrudAction('recalculateBill')
            ->displayIf(static function (AgentBill $bill) {
                return BillStatusEnum::PAID !== $bill->getStatus();
            })
            ->setCssClass('btn btn-warning')
        ;

        $generateBatchBillsAction = Action::new('generateBatchBills', '批量生成账单', 'fa fa-plus-circle')
            ->linkToCrudAction('generateBatchBills')
            ->createAsGlobalAction()
            ->setCssClass('btn btn-primary')
        ;

        $viewPaymentsAction = Action::new('viewPayments', '查看支付', 'fa fa-credit-card')
            ->linkToCrudAction('viewPayments')
            ->setCssClass('btn btn-info')
        ;

        return $actions
            // 添加自定义 actions
            ->add(Crud::PAGE_INDEX, $confirmAction)
            ->add(Crud::PAGE_INDEX, $recalculateAction)
            ->add(Crud::PAGE_INDEX, $viewPaymentsAction)
            ->add(Crud::PAGE_INDEX, $generateBatchBillsAction)
            ->add(Crud::PAGE_DETAIL, $confirmAction)
            ->add(Crud::PAGE_DETAIL, $recalculateAction)
            ->add(Crud::PAGE_DETAIL, $viewPaymentsAction)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('agent')
            ->add(ChoiceFilter::new('status')->setChoices(BillStatusEnum::cases()))
            ->add('billMonth')
            ->add(ChoiceFilter::new('settlementType')->setChoices(SettlementTypeEnum::cases()))
            ->add(DateTimeFilter::new('createTime'))
        ;
    }

    /**
     * 确认账单
     */
    #[AdminAction(routeName: 'admin_agent_bill_confirm', routePath: '/agent-bill/confirm')]
    public function confirmBill(AdminContext $context): Response
    {
        $bill = $context->getEntity()->getInstance();

        if (!$bill instanceof AgentBill) {
            $this->addFlash('danger', '无效的账单');

            return $this->redirectToRoute('admin');
        }

        $success = $this->agentBillService->confirmBill($bill);

        if ($success) {
            $this->addFlash('success', '账单已确认');
        } else {
            $this->addFlash('danger', '账单确认失败');
        }

        return $this->redirect($this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl());
    }

    /**
     * 重新计算账单
     */
    #[AdminAction(routeName: 'admin_agent_bill_recalculate', routePath: '/agent-bill/recalculate')]
    public function recalculateBill(AdminContext $context): Response
    {
        $bill = $context->getEntity()->getInstance();

        if (!$bill instanceof AgentBill) {
            $this->addFlash('danger', '无效的账单');

            return $this->redirectToRoute('admin');
        }

        try {
            $this->agentBillService->recalculateBill($bill);
            $this->addFlash('success', '账单重新计算完成');
        } catch (\Throwable $e) {
            $this->addFlash('danger', '账单重新计算失败：' . $e->getMessage());
        }

        return $this->redirect($this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl());
    }

    /**
     * 批量生成账单
     */
    #[AdminAction(routeName: 'admin_agent_bill_generate_batch', routePath: '/agent-bill/generate-batch')]
    public function generateBatchBills(AdminContext $context): Response
    {
        $request = $context->getRequest();

        if ($request->isMethod('POST')) {
            $billMonth = $request->request->get('billMonth');

            if (!is_string($billMonth) || '' === $billMonth) {
                $this->addFlash('danger', '请输入账单月份');
            } else {
                try {
                    $bills = $this->agentBillService->generateMonthlyBills($billMonth);
                    $this->addFlash('success', sprintf('成功生成 %d 个账单', count($bills)));
                } catch (\Throwable $e) {
                    $this->addFlash('danger', '批量生成账单失败：' . $e->getMessage());
                }
            }

            return $this->redirect($this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl());
        }

        // 显示表单
        return $this->render('@HotelAgent/admin/agent_bill/generate_batch.html.twig', [
            'current_month' => date('Y-m'),
        ]);
    }

    /**
     * 查看支付记录
     */
    #[AdminAction(routeName: 'admin_agent_bill_view_payments', routePath: '/agent-bill/view-payments')]
    public function viewPayments(AdminContext $context): Response
    {
        $bill = $context->getEntity()->getInstance();

        if (!$bill instanceof AgentBill) {
            $this->addFlash('danger', '无效的账单');

            return $this->redirectToRoute('admin');
        }

        return $this->redirect($this->adminUrlGenerator
            ->setController(PaymentCrudController::class)
            ->setAction(Action::INDEX)
            ->set('filters[agentBill]', $bill->getId())
            ->generateUrl());
    }

    /**
     * 自定义新建表单
     */
    public function new(AdminContext $context)
    {
        $response = parent::new($context);

        // 处理成功创建后的计算逻辑
        if ($context->getRequest()->isMethod('POST')) {
            $bill = $context->getEntity()->getInstance();
            if ($bill instanceof AgentBill && $bill->getId() > 0) {
                // 新建成功后自动计算账单数据
                try {
                    $this->agentBillService->recalculateBill($bill);
                } catch (\Throwable $e) {
                    $this->addFlash('warning', '账单创建成功，但计算失败：' . $e->getMessage());
                }
            }
        }

        return $response;
    }

    /**
     * 账单统计
     */
    #[AdminAction(routeName: 'admin_agent_bill_statistics', routePath: '/agent-bill/statistics')]
    public function billStatistics(AdminContext $context): Response
    {
        $request = $context->getRequest();
        $billMonth = $request->query->get('billMonth', date('Y-m'));
        assert(is_string($billMonth));

        $statistics = $this->agentBillService->getBillStatistics($billMonth);

        return $this->render('@HotelAgent/admin/agent_bill/statistics.html.twig', [
            'statistics' => $statistics,
            'billMonth' => $billMonth,
        ]);
    }
}
