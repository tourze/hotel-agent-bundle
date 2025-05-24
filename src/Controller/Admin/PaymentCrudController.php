<?php

namespace Tourze\HotelAgentBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Tourze\HotelAgentBundle\Entity\Payment;
use Tourze\HotelAgentBundle\Enum\PaymentMethodEnum;
use Tourze\HotelAgentBundle\Enum\PaymentStatusEnum;
use Tourze\HotelAgentBundle\Service\PaymentService;

/**
 * 支付记录管理控制器
 */
class PaymentCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly AdminUrlGenerator $adminUrlGenerator
    ) {}

    public static function getEntityFqcn(): string
    {
        return Payment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('支付记录')
            ->setEntityLabelInPlural('支付记录')
            ->setPageTitle('index', '支付记录管理')
            ->setPageTitle('new', '新建支付记录')
            ->setPageTitle('edit', '编辑支付记录')
            ->setPageTitle('detail', '支付记录详情')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('agentBill', '关联账单')
                ->setFormTypeOptions([
                    'choice_label' => function ($agentBill) {
                        return sprintf('%s (%s)', $agentBill->getBillMonth(), $agentBill->getAgent()->getCompanyName());
                    }
                ])
                ->setCrudController(AgentBillCrudController::class),

            TextField::new('paymentNo', '支付单号')
                ->hideOnForm(),

            MoneyField::new('amount', '支付金额')
                ->setCurrency('CNY'),

            ChoiceField::new('paymentMethod', '支付方式')
                ->setChoices(PaymentMethodEnum::cases())
                ->renderExpanded(false),

            ChoiceField::new('status', '支付状态')
                ->setChoices(PaymentStatusEnum::cases())
                ->renderExpanded(false)
                ->renderAsBadges([
                    PaymentStatusEnum::PENDING->value => 'warning',
                    PaymentStatusEnum::SUCCESS->value => 'success',
                    PaymentStatusEnum::FAILED->value => 'danger',
                    PaymentStatusEnum::REFUNDED->value => 'info',
                    PaymentStatusEnum::CANCELLED->value => 'secondary'
                ]),

            TextField::new('transactionId', '第三方交易号')
                ->hideOnIndex(),

            UrlField::new('paymentProofUrl', '支付凭证')
                ->hideOnIndex(),

            UrlField::new('digitalSignatureUrl', '电子签章')
                ->hideOnIndex(),

            DateTimeField::new('paymentTime', '支付时间')
                ->hideOnForm()
                ->hideOnIndex(),

            DateTimeField::new('confirmTime', '确认时间')
                ->hideOnForm()
                ->hideOnIndex(),

            TextareaField::new('remarks', '备注')
                ->hideOnIndex(),

            TextareaField::new('failureReason', '失败原因')
                ->hideOnIndex()
                ->hideOnForm(),

            DateTimeField::new('createTime', '创建时间')
                ->hideOnForm(),

            DateTimeField::new('updateTime', '更新时间')
                ->hideOnForm()
                ->hideOnIndex()
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $processSuccessAction = Action::new('processSuccess', '标记成功', 'fa fa-check')
            ->linkToCrudAction('processPaymentSuccess')
            ->displayIf(static function (Payment $payment) {
                return $payment->getStatus() === PaymentStatusEnum::PENDING;
            })
            ->setCssClass('btn btn-success');

        $processFailureAction = Action::new('processFailure', '标记失败', 'fa fa-times')
            ->linkToCrudAction('processPaymentFailure')
            ->displayIf(static function (Payment $payment) {
                return $payment->getStatus() === PaymentStatusEnum::PENDING;
            })
            ->setCssClass('btn btn-danger');

        $confirmAction = Action::new('confirm', '确认支付', 'fa fa-check-circle')
            ->linkToCrudAction('confirmPayment')
            ->displayIf(static function (Payment $payment) {
                return $payment->getStatus() === PaymentStatusEnum::SUCCESS;
            })
            ->setCssClass('btn btn-info');

        return $actions
            ->add(Crud::PAGE_INDEX, $processSuccessAction)
            ->add(Crud::PAGE_INDEX, $processFailureAction)
            ->add(Crud::PAGE_INDEX, $confirmAction)
            ->add(Crud::PAGE_DETAIL, $processSuccessAction)
            ->add(Crud::PAGE_DETAIL, $processFailureAction)
            ->add(Crud::PAGE_DETAIL, $confirmAction)
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->displayIf(static function (Payment $payment) {
                    return $payment->getStatus() === PaymentStatusEnum::PENDING;
                });
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->displayIf(static function (Payment $payment) {
                    return $payment->getStatus() === PaymentStatusEnum::PENDING;
                });
            });
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('agentBill')
            ->add(ChoiceFilter::new('status')->setChoices(PaymentStatusEnum::cases()))
            ->add(ChoiceFilter::new('paymentMethod')->setChoices(PaymentMethodEnum::cases()))
            ->add(DateTimeFilter::new('createTime'))
            ->add(DateTimeFilter::new('paymentTime'));
    }

    /**
     * 处理支付成功
     */
    public function processPaymentSuccess(AdminContext $context): Response
    {
        $payment = $context->getEntity()->getInstance();
        
        if (!$payment instanceof Payment) {
            $this->addFlash('error', '无效的支付记录');
            return $this->redirectToRoute('admin');
        }

        $success = $this->paymentService->processPaymentSuccess($payment);
        
        if ($success) {
            $this->addFlash('success', '支付已标记为成功');
        } else {
            $this->addFlash('error', '支付状态更新失败');
        }

        return $this->redirect($this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl());
    }

    /**
     * 处理支付失败
     */
    public function processPaymentFailure(AdminContext $context): Response
    {
        $payment = $context->getEntity()->getInstance();
        
        if (!$payment instanceof Payment) {
            $this->addFlash('error', '无效的支付记录');
            return $this->redirectToRoute('admin');
        }

        $request = $context->getRequest();
        
        if ($request->isMethod('POST')) {
            $failureReason = $request->request->get('failureReason', '管理员标记失败');
            
            $success = $this->paymentService->processPaymentFailure($payment, $failureReason);
            
            if ($success) {
                $this->addFlash('success', '支付已标记为失败');
            } else {
                $this->addFlash('error', '支付状态更新失败');
            }

            return $this->redirect($this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl());
        }

        // 显示失败原因输入表单
        return $this->render('admin/payment/failure_form.html.twig', [
            'payment' => $payment
        ]);
    }

    /**
     * 确认支付
     */
    public function confirmPayment(AdminContext $context): Response
    {
        $payment = $context->getEntity()->getInstance();
        
        if (!$payment instanceof Payment) {
            $this->addFlash('error', '无效的支付记录');
            return $this->redirectToRoute('admin');
        }

        $success = $this->paymentService->confirmPayment($payment);
        
        if ($success) {
            $this->addFlash('success', '支付已确认');
        } else {
            $this->addFlash('error', '支付确认失败');
        }

        return $this->redirect($this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl());
    }

    /**
     * 自定义新建表单
     */
    public function new(AdminContext $context): Response
    {
        $response = parent::new($context);
        
        if ($context->getRequest()->isMethod('POST')) {
            $payment = $context->getEntity()->getInstance();
            if ($payment instanceof Payment && $payment->getId()) {
                // 新建成功后自动生成支付单号
                if (empty($payment->getPaymentNo())) {
                    $payment->generatePaymentNo();
                    $this->getDoctrine()->getManager()->flush();
                }
            }
        }
        
        return $response;
    }
}
