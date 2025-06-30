<?php

namespace Tourze\HotelAgentBundle\Controller\Admin;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Enum\AuditStatusEnum;
use Tourze\HotelAgentBundle\Enum\OrderSourceEnum;
use Tourze\HotelAgentBundle\Enum\OrderStatusEnum;
use Tourze\HotelAgentBundle\Repository\AgentRepository;
use Tourze\HotelAgentBundle\Repository\OrderItemRepository;
use Tourze\HotelAgentBundle\Service\OrderCreationService;
use Tourze\HotelAgentBundle\Service\OrderImportService;
use Tourze\HotelAgentBundle\Service\OrderStatusService;
use Tourze\HotelProfileBundle\Repository\RoomTypeRepository;

/**
 * 订单管理控制器
 */
class OrderCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly OrderStatusService $orderStatusService,
        private readonly OrderImportService $orderImportService,
        private readonly OrderCreationService $orderCreationService,
        private readonly AgentRepository $agentRepository,
        private readonly OrderItemRepository $orderItemRepository,
        private readonly RoomTypeRepository $roomTypeRepository,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('订单')
            ->setEntityLabelInPlural('订单管理')
            ->setSearchFields(['orderNo', 'agent.companyName', 'remark'])
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(30)
            ->showEntityActionsInlined()
            ->setFormOptions([
                'validation_groups' => ['Default'],
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        // 确认订单操作
        $confirmAction = Action::new('confirm', '确认', 'fa fa-check')
            ->linkToCrudAction('confirmOrder')
            ->displayIf(static function (Order $order): bool {
                return $order->getStatus() === OrderStatusEnum::PENDING;
            })
            ->addCssClass('btn btn-success btn-sm');

        // 取消订单操作
        $cancelAction = Action::new('cancel', '取消', 'fa fa-times')
            ->linkToCrudAction('cancelOrder')
            ->displayIf(static function (Order $order): bool {
                return in_array($order->getStatus(), [OrderStatusEnum::PENDING, OrderStatusEnum::CONFIRMED], true);
            })
            ->addCssClass('btn btn-danger btn-sm');

        // 关闭订单操作
        $closeAction = Action::new('close', '关闭', 'fa fa-ban')
            ->linkToCrudAction('closeOrder')
            ->displayIf(static function (Order $order): bool {
                return $order->getStatus() === OrderStatusEnum::CONFIRMED;
            })
            ->addCssClass('btn btn-warning btn-sm');

        // Excel导入操作
        $importAction = Action::new('import', 'Excel导入', 'fa fa-upload')
            ->linkToCrudAction('importOrders')
            ->createAsGlobalAction()
            ->addCssClass('btn btn-primary');

        // 新建订单操作
        $newOrderAction = Action::new('newOrder', '新建订单', 'fa fa-plus')
            ->linkToCrudAction('newOrder')
            ->createAsGlobalAction()
            ->addCssClass('btn btn-success');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $confirmAction)
            ->add(Crud::PAGE_INDEX, $cancelAction)
            ->add(Crud::PAGE_INDEX, $closeAction)
            ->add(Crud::PAGE_INDEX, $importAction)
            ->add(Crud::PAGE_INDEX, $newOrderAction)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->reorder(Crud::PAGE_INDEX, [
                Action::DETAIL,
                'confirm',
                'cancel',
                'close',
                Action::EDIT
            ]);
    }

    public function configureFilters(Filters $filters): Filters
    {
        // 构建订单状态选择项
        $statusChoices = [];
        foreach (OrderStatusEnum::cases() as $case) {
            $statusChoices[$case->getLabel()] = $case->value;
        }

        // 构建订单来源选择项
        $sourceChoices = [];
        foreach (OrderSourceEnum::cases() as $case) {
            $sourceChoices[$case->getLabel()] = $case->value;
        }

        // 构建审核状态选择项
        $auditStatusChoices = [];
        foreach (AuditStatusEnum::cases() as $case) {
            $auditStatusChoices[$case->getLabel()] = $case->value;
        }

        return $filters
            ->add(ChoiceFilter::new('status', '订单状态')->setChoices($statusChoices))
            ->add(ChoiceFilter::new('source', '订单来源')->setChoices($sourceChoices))
            ->add(ChoiceFilter::new('auditStatus', '审核状态')->setChoices($auditStatusChoices))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(EntityFilter::new('agent', '代理商'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('id', 'ID')->onlyOnIndex();

        // 订单编号 - 创建时自动生成，编辑时只读
        yield TextField::new('orderNo', '订单编号')
            ->setColumns(3)
            ->setFormTypeOptions(['attr' => ['readonly' => true]])
            ->hideWhenCreating();

        // 代理商 - 必选
        yield AssociationField::new('agent', '代理商')
            ->setColumns(4)
            ->setRequired(true)
            ->formatValue(function ($value) {
                return $value?->getCompanyName();
            });

        // 订单来源
        yield ChoiceField::new('source', '订单来源')
            ->setColumns(3)
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => OrderSourceEnum::class])
            ->formatValue(function ($value) {
                return $value instanceof OrderSourceEnum ? $value->getLabel() : '';
            })
            ->hideOnIndex()
            ->setRequired(true);

        // 订单总金额 - 编辑时显示，创建时自动计算
        yield MoneyField::new('totalAmount', '订单总金额')
            ->setColumns(3)
            ->setCurrency('CNY')
            ->setStoredAsCents(false)
            ->hideWhenCreating();

        // 订单状态
        yield ChoiceField::new('status', '订单状态')
            ->setColumns(2)
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => OrderStatusEnum::class])
            ->formatValue(function ($value) {
                return $value instanceof OrderStatusEnum ? $value->getLabel() : '';
            })
            ->renderAsBadges([
                'pending' => 'warning',
                'confirmed' => 'success',
                'canceled' => 'danger',
                'closed' => 'secondary'
            ])
            ->hideWhenCreating(); // 创建时默认为 pending

        // 审核状态
        yield ChoiceField::new('auditStatus', '审核状态')
            ->setColumns(2)
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => AuditStatusEnum::class])
            ->formatValue(function ($value) {
                return $value instanceof AuditStatusEnum ? $value->getLabel() : '';
            })
            ->renderAsBadges([
                'approved' => 'success',
                'rejected' => 'danger',
                'pending' => 'warning'
            ])
            ->hideOnIndex()
            ->hideWhenCreating(); // 创建时默认为 pending

        // 订单备注
        yield TextareaField::new('remark', '订单备注')
            ->setColumns(6)
            ->hideOnIndex()
            ->setFormTypeOptions(['attr' => ['rows' => 3]]);

        // 只在详情和编辑页面显示的字段
        if (in_array($pageName, [Crud::PAGE_DETAIL, Crud::PAGE_EDIT], true)) {
            yield TextareaField::new('cancelReason', '取消原因')
                ->setColumns(6)
                ->hideOnIndex()
                ->setFormTypeOptions(['attr' => ['rows' => 2]])
                ->hideWhenUpdating();

            yield DateTimeField::new('createTime', '创建时间')
                ->setColumns(3)
                ->setFormat('yyyy-MM-dd HH:mm:ss')
                ->hideWhenUpdating();

            yield DateTimeField::new('cancelTime', '取消时间')
                ->setColumns(3)
                ->setFormat('yyyy-MM-dd HH:mm:ss')
                ->hideWhenUpdating();

            yield AssociationField::new('orderItems', '订单项')
                ->setTemplatePath('@HotelAgent/admin/order_items.html.twig')
                ->formatValue(function ($value, $entity) {
                    // 预加载关联数据避免N+1查询
                    if ($entity instanceof Order && $value) {
                        $this->orderItemRepository
                            ->createQueryBuilder('oi')
                            ->select('oi', 'h', 'rt', 'di', 'c')
                            ->leftJoin('oi.hotel', 'h')
                            ->leftJoin('oi.roomType', 'rt')
                            ->leftJoin('oi.dailyInventory', 'di')
                            ->leftJoin('di.contract', 'c')
                            ->where('oi.order = :order')
                            ->setParameter('order', $entity)
                            ->orderBy('oi.checkInDate', 'ASC')
                            ->addOrderBy('oi.id', 'ASC')
                            ->getQuery()
                            ->getResult();
                    }
                    return $value;
                })
                ->hideWhenUpdating();
        }
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        // 默认排序和关联查询优化
        $queryBuilder
            ->addOrderBy('entity.createTime', 'DESC')
            ->leftJoin('entity.agent', 'agent')
            ->addSelect('agent');

        return $queryBuilder;
    }

    /**
     * 确认订单
     */
    #[AdminAction(routeName: 'confirm')]
    public function confirmOrder(AdminContext $context): Response
    {
        $order = $context->getEntity()->getInstance();
        assert($order instanceof Order);

        if ($order->getStatus() !== OrderStatusEnum::PENDING) {
            $this->addFlash('danger', '只有待确认状态的订单才能确认');
            return $this->redirect($this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl());
        }

        try {
            $userId = null !== $this->getUser() ? $this->getUser()->getUserIdentifier() : '0';
            $this->orderStatusService->confirmOrder($order, (int)$userId);

            $this->addFlash('success', '订单确认成功');
        } catch (\Throwable $e) {
            $this->addFlash('danger', '订单确认失败：' . $e->getMessage());
        }

        return $this->redirect($this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl());
    }

    /**
     * 取消订单
     */
    #[AdminAction(routeName: 'cancel')]
    public function cancelOrder(Request $request, AdminContext $context): Response
    {
        $order = $context->getEntity()->getInstance();
        assert($order instanceof Order);

        if (!in_array($order->getStatus(), [OrderStatusEnum::PENDING, OrderStatusEnum::CONFIRMED], true)) {
            $this->addFlash('danger', '订单状态不允许取消');
            return $this->redirect($this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl());
        }

        if ($request->isMethod('POST')) {
            $reason = $request->request->get('reason', '');
            if (empty($reason)) {
                $this->addFlash('danger', '请输入取消原因');
            } else {
                try {
                    $userId = null !== $this->getUser() ? $this->getUser()->getUserIdentifier() : '0';
                    $this->orderStatusService->cancelOrder($order, $reason, (int)$userId);

                    $this->addFlash('success', '订单取消成功');
                    return $this->redirect($this->adminUrlGenerator
                        ->setController(self::class)
                        ->setAction(Action::INDEX)
                        ->generateUrl());
                } catch (\Throwable $e) {
                    $this->addFlash('danger', '订单取消失败：' . $e->getMessage());
                }
            }
        }

        return $this->render('@HotelAgent/admin/order_cancel.html.twig', [
            'order' => $order,
        ]);
    }

    /**
     * 关闭订单
     */
    #[AdminAction(routeName: 'close')]
    public function closeOrder(Request $request, AdminContext $context): Response
    {
        $order = $context->getEntity()->getInstance();
        assert($order instanceof Order);

        if ($order->getStatus() !== OrderStatusEnum::CONFIRMED) {
            $this->addFlash('danger', '只有已确认状态的订单才能关闭');
            return $this->redirect($this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl());
        }

        if ($request->isMethod('POST')) {
            $reason = $request->request->get('reason', '');
            if (empty($reason)) {
                $this->addFlash('danger', '请输入关闭原因');
            } else {
                try {
                    $userId = null !== $this->getUser() ? $this->getUser()->getUserIdentifier() : '0';
                    $this->orderStatusService->closeOrder($order, $reason, (int)$userId);

                    $this->addFlash('success', '订单关闭成功');
                    return $this->redirect($this->adminUrlGenerator
                        ->setController(self::class)
                        ->setAction(Action::INDEX)
                        ->generateUrl());
                } catch (\Throwable $e) {
                    $this->addFlash('danger', '订单关闭失败：' . $e->getMessage());
                }
            }
        }

        return $this->render('@HotelAgent/admin/order_close.html.twig', [
            'order' => $order,
        ]);
    }

    /**
     * Excel导入订单
     */
    #[AdminAction(routeName: 'admin_order_import')]
    public function importOrders(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $uploadedFile = $request->files->get('import_file');

            if (null === $uploadedFile) {
                $this->addFlash('danger', '请选择要导入的Excel文件');
            } else {
                try {
                    $userId = null !== $this->getUser() ? $this->getUser()->getUserIdentifier() : '0';
                    $result = $this->orderImportService->importFromExcel($uploadedFile, (int)$userId);

                    $this->addFlash('success', sprintf('成功导入 %d 个订单', $result['success_count']));

                    if ($result['error_count'] > 0) {
                        $this->addFlash('warning', sprintf('有 %d 个订单导入失败', $result['error_count']));
                    }

                    return $this->redirect($this->adminUrlGenerator
                        ->setController(self::class)
                        ->setAction(Action::INDEX)
                        ->generateUrl());
                } catch (\Throwable $e) {
                    $this->addFlash('danger', '导入失败：' . $e->getMessage());
                }
            }
        }

        return $this->render('@HotelAgent/admin/order_import.html.twig');
    }

    /**
     * 新建订单
     */
    #[AdminAction(routeName: 'admin_order_new')]
    public function newOrder(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $formData = $request->request->all();
                $order = $this->orderCreationService->createOrderWithItems($formData);

                $this->addFlash('success', '订单创建成功');
                return $this->redirect($this->adminUrlGenerator
                    ->setController(self::class)
                    ->setAction(Action::INDEX)
                    ->generateUrl());
            } catch (\Throwable $e) {
                $this->addFlash('danger', '订单创建失败：' . $e->getMessage());

                // 如果是库存被占用的错误，给出更具体的提示
                if (str_contains($e->getMessage(), '库存已被占用')) {
                    $this->addFlash('warning', '所选库存可能已被其他订单占用，请重新选择库存');
                }
            }
        }

        // 获取代理商列表
        $agents = $this->agentRepository->findAll();

        // 获取房型列表
        $roomTypes = $this->roomTypeRepository->findBy(
            ['status' => \Tourze\HotelProfileBundle\Enum\RoomTypeStatusEnum::ACTIVE],
            ['hotel' => 'ASC', 'name' => 'ASC']
        );

        return $this->render('@HotelAgent/admin/order_new.html.twig', [
            'agents' => $agents,
            'roomTypes' => $roomTypes,
        ]);
    }
}
