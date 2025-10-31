<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Controller\Admin;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Tourze\HotelAgentBundle\Entity\Order;
use Tourze\HotelAgentBundle\Entity\OrderItem;
use Tourze\HotelAgentBundle\Enum\OrderItemStatusEnum;
use Tourze\HotelProfileBundle\Entity\Hotel;
use Tourze\HotelProfileBundle\Entity\RoomType;

/**
 * 订单明细管理控制器
 * @extends AbstractCrudController<OrderItem>
 */
#[AdminCrud(routePath: '/hotel-agent/order-item', routeName: 'hotel_agent_order_item')]
final class OrderItemCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return OrderItem::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('订单明细')
            ->setEntityLabelInPlural('订单明细管理')
            ->setSearchFields(['order.orderNo', 'hotel.name', 'roomType.name'])
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(30)
            ->showEntityActionsInlined()
            ->setFormOptions([
                'validation_groups' => ['Default'],
            ])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        // 构建订单明细状态选择项
        $statusChoices = [];
        foreach (OrderItemStatusEnum::cases() as $case) {
            $statusChoices[$case->getLabel()] = $case->value;
        }

        return $filters
            ->add(ChoiceFilter::new('status', '状态')->setChoices($statusChoices))
            ->add(EntityFilter::new('order', '订单'))
            ->add(EntityFilter::new('hotel', '酒店'))
            ->add(EntityFilter::new('roomType', '房型'))
            ->add(DateTimeFilter::new('checkInDate', '入住日期'))
            ->add(DateTimeFilter::new('checkOutDate', '退房日期'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('id', 'ID')->onlyOnIndex();

        yield from $this->getBasicFields($pageName);
        yield from $this->getPriceFields($pageName);
        yield from $this->getStatusFields($pageName);
        yield from $this->getDetailFields($pageName);
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getBasicFields(string $pageName): iterable
    {
        // 关联订单
        yield AssociationField::new('order', '订单')
            ->setColumns(3)
            ->setRequired(true)
            ->formatValue(function (?Order $value): string {
                return $value?->getOrderNo() ?? '';
            })
        ;

        // 酒店
        yield AssociationField::new('hotel', '酒店')
            ->setColumns(3)
            ->setRequired(true)
            ->formatValue(function (?Hotel $value): string {
                return $value?->getName() ?? '';
            })
        ;

        // 房型
        yield AssociationField::new('roomType', '房型')
            ->setColumns(3)
            ->setRequired(true)
            ->formatValue(function (?RoomType $value): string {
                return $value?->getName() ?? '';
            })
        ;

        // 入住日期
        yield DateField::new('checkInDate', '入住日期')
            ->setColumns(3)
            ->setRequired(true)
            ->setFormat('yyyy-MM-dd')
        ;

        // 退房日期
        yield DateField::new('checkOutDate', '退房日期')
            ->setColumns(3)
            ->setRequired(true)
            ->setFormat('yyyy-MM-dd')
        ;

        // 数量 (天数)
        if (Crud::PAGE_INDEX === $pageName) {
            yield IntegerField::new('nights', '数量')
                ->setColumns(3)
            ;
        }

        // 创建时间
        yield DateTimeField::new('createTime', '创建时间')
            ->setColumns(3)
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getPriceFields(string $pageName): iterable
    {
        // 销售单价
        yield MoneyField::new('unitPrice', '销售单价')
            ->setColumns(3)
            ->setCurrency('CNY')
            ->setStoredAsCents(false)
            ->setRequired(true)
        ;

        // 采购成本价
        yield MoneyField::new('costPrice', '采购成本')
            ->setColumns(3)
            ->setCurrency('CNY')
            ->setStoredAsCents(false)
            ->hideOnIndex()
        ;

        // 小计金额
        yield MoneyField::new('amount', '小计金额')
            ->setColumns(3)
            ->setCurrency('CNY')
            ->setStoredAsCents(false)
            ->hideWhenCreating()
            ->hideWhenUpdating()
        ;

        // 利润金额
        yield MoneyField::new('profit', '利润金额')
            ->setColumns(3)
            ->setCurrency('CNY')
            ->setStoredAsCents(false)
            ->hideOnIndex()
            ->hideWhenCreating()
            ->hideWhenUpdating()
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getStatusFields(string $pageName): iterable
    {
        // 状态
        yield ChoiceField::new('status', '状态')
            ->setColumns(2)
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => OrderItemStatusEnum::class])
            ->formatValue(function ($value) {
                return $value instanceof OrderItemStatusEnum ? $value->getLabel() : '';
            })
            ->renderAsBadges([
                'pending' => 'warning',
                'confirmed' => 'success',
                'canceled' => 'danger',
                'completed' => 'info',
            ])
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getDetailFields(string $pageName): iterable
    {
        // 只在详情和编辑页面显示的字段
        if (!in_array($pageName, [Crud::PAGE_DETAIL, Crud::PAGE_EDIT], true)) {
            return;
        }

        // 合同
        yield AssociationField::new('contract', '合同')
            ->setColumns(4)
            ->formatValue(fn ($value) => $this->formatObjectName($value))
            ->hideWhenUpdating()
        ;

        // 日库存
        yield AssociationField::new('dailyInventory', '日库存')
            ->setColumns(4)
            ->formatValue(fn ($value) => $this->formatObjectId($value))
            ->hideWhenUpdating()
        ;

        // 合同切换原因
        yield TextareaField::new('contractChangeReason', '合同切换原因')
            ->setColumns(6)
            ->hideOnIndex()
            ->setFormTypeOptions(['attr' => ['rows' => 3]])
            ->hideWhenUpdating()
        ;

        // 最后修改人
        yield IntegerField::new('lastModifiedBy', '最后修改人ID')
            ->setColumns(3)
            ->hideWhenUpdating()
        ;

        // 更新时间
        yield DateTimeField::new('updateTime', '更新时间')
            ->setColumns(3)
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideWhenUpdating()
        ;
    }

    /**
     * 格式化对象名称
     * @param mixed $value
     */
    private function formatObjectName($value): string
    {
        if (is_object($value) && method_exists($value, 'getName')) {
            $result = $value->getName();

            return (null !== $result && is_scalar($result)) ? (string) $result : '无';
        }

        return '无';
    }

    /**
     * 格式化对象ID
     * @param mixed $value
     */
    private function formatObjectId($value): string
    {
        if (is_object($value) && method_exists($value, 'getId')) {
            $result = $value->getId();

            return (null !== $result && is_scalar($result)) ? (string) $result : '无';
        }

        return '无';
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        // 默认排序和关联查询优化
        $queryBuilder
            ->addOrderBy('entity.createTime', 'DESC')
            ->leftJoin('entity.order', 'o')
            ->leftJoin('entity.hotel', 'h')
            ->leftJoin('entity.roomType', 'rt')
            ->addSelect('o', 'h', 'rt')
        ;

        return $queryBuilder;
    }
}
