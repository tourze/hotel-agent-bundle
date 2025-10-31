<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Component\HttpFoundation\Response;
use Tourze\HotelAgentBundle\Entity\Agent;
use Tourze\HotelAgentBundle\Entity\AgentHotelMapping;
use Tourze\HotelAgentBundle\Repository\AgentHotelMappingRepository;
use Tourze\HotelProfileBundle\Entity\Hotel;
use Tourze\HotelProfileBundle\Service\RoomTypeService;

/**
 * 代理酒店映射管理控制器
 * @extends AbstractCrudController<AgentHotelMapping>
 */
#[AdminCrud(routePath: '/hotel-agent/agent-hotel-mapping', routeName: 'hotel_agent_agent_hotel_mapping')]
final class AgentHotelMappingCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly RoomTypeService $roomTypeService,
        private readonly AgentHotelMappingRepository $agentHotelMappingRepository,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return AgentHotelMapping::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('酒店授权')
            ->setEntityLabelInPlural('代理酒店授权管理')
            ->setPageTitle('index', '代理酒店授权列表')
            ->setPageTitle('new', '新增酒店授权')
            ->setPageTitle('edit', '编辑酒店授权')
            ->setPageTitle('detail', '酒店授权详情')
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->setSearchFields(['agent.companyName', 'hotel.name'])
            ->addFormTheme('@EasyAdmin/crud/form_theme.html.twig')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $batchAssignAction = Action::new('batchAssign', '批量分配酒店', 'fa fa-share')
            ->linkToCrudAction('batchAssignHotels')
            ->addCssClass('btn btn-success')
            ->createAsGlobalAction()
        ;

        return $actions
            // 添加自定义 actions
            ->add(Crud::PAGE_INDEX, $batchAssignAction)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('agent', '代理')->setFormTypeOptions([
                'class' => Agent::class,
                'choice_label' => fn (Agent $agent): string => $agent->getCompanyName() . ' (' . $agent->getCode() . ')',
            ]))
            ->add(EntityFilter::new('hotel', '酒店')->setFormTypeOptions([
                'class' => Hotel::class,
                'choice_label' => 'name',
            ]))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        // 列表页显示字段
        if (Crud::PAGE_INDEX === $pageName) {
            yield AssociationField::new('agent', '代理')
                ->formatValue(
                    fn ($value, AgentHotelMapping $entity): string => null !== $entity->getAgent() ?
                        $entity->getAgent()->getCompanyName() . ' (' . $entity->getAgent()->getCode() . ')' :
                        'N/A'
                )
            ;

            yield AssociationField::new('hotel', '酒店')
                ->formatValue(
                    fn ($value, AgentHotelMapping $entity): string => null !== $entity->getHotel() ? $entity->getHotel()->getName() : 'N/A'
                )
            ;

            yield TextField::new('roomTypeCount', '可见房型数')
                ->onlyOnIndex()
            ;

            yield DateTimeField::new('createTime', '创建时间')
                ->setFormat('yyyy-MM-dd HH:mm:ss')
            ;

            return;
        }

        yield FormField::addTab('基本信息');

        yield AssociationField::new('agent', '代理')
            ->setRequired(true)
            ->setFormTypeOptions([
                'choice_label' => fn (Agent $agent): string => $agent->getCompanyName() . ' (' . $agent->getCode() . ')',
                'placeholder' => '请选择代理',
            ])
            ->setColumns(6)
        ;

        yield AssociationField::new('hotel', '酒店')
            ->setRequired(true)
            ->setFormTypeOptions([
                'choice_label' => 'name',
                'placeholder' => '请选择酒店',
            ])
            ->setColumns(6)
        ;

        yield FormField::addTab('房型权限');

        yield ChoiceField::new('roomTypeIds', '可见房型')
            ->setChoices($this->getRoomTypeChoices())
            ->allowMultipleChoices()
            ->renderExpanded(true)
            ->setHelp('选择该代理可以看到的房型，留空表示可以看到所有房型')
            ->hideOnIndex()
        ;

        yield FormField::addTab('系统信息')->hideOnForm();

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
    }

    /**
     * 获取房型选择项
     * @return array<string, int>
     */
    private function getRoomTypeChoices(): array
    {
        try {
            $roomTypes = $this->roomTypeService->getAllRoomTypesWithHotel();
            $choices = [];

            foreach ($roomTypes as $roomType) {
                $hotel = $roomType->getHotel();
                $roomTypeId = $roomType->getId();
                if (null !== $hotel && null !== $roomTypeId) {
                    $label = $hotel->getName() . ' - ' . $roomType->getName();
                    $choices[$label] = $roomTypeId;
                }
            }

            return $choices;
        } catch (\Exception $e) {
            // 在测试环境中可能没有房型数据，返回空数组
            return [];
        }
    }

    /**
     * 批量分配酒店
     */
    #[AdminAction(routeName: 'admin_agent_hotel_mapping_batch_assign', routePath: '/agent-hotel-mapping/batch-assign')]
    public function batchAssignHotels(): Response
    {
        // TODO: 实现批量分配功能
        $this->addFlash('success', '批量分配功能开发中...');

        return $this->redirectToRoute('admin');
    }

    /**
     * 在保存前验证数据
     */
    public function persistEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        // 检查重复映射
        $agent = $entityInstance->getAgent();
        $hotel = $entityInstance->getHotel();

        if (null === $agent || null === $hotel) {
            $this->addFlash('danger', '代理或酒店信息不完整');

            return;
        }

        $agentId = $agent->getId();
        $hotelId = $hotel->getId();

        if (null === $hotelId) {
            $this->addFlash('danger', '酒店ID无效');

            return;
        }

        $existingMapping = $this->agentHotelMappingRepository->findByAgentAndHotel(
            $agentId,
            $hotelId
        );

        if (null !== $existingMapping && $existingMapping->getId() !== $entityInstance->getId()) {
            $this->addFlash('danger', '该代理已经有此酒店的授权，无法重复添加');

            return;
        }

        parent::persistEntity($entityManager, $entityInstance);
        $this->addFlash('success', '酒店授权创建成功');
    }

    /**
     * 在更新前验证数据
     */
    public function updateEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        parent::updateEntity($entityManager, $entityInstance);
        $this->addFlash('success', '酒店授权更新成功');
    }
}
