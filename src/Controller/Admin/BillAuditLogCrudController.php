<?php

namespace Tourze\HotelAgentBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Tourze\HotelAgentBundle\Entity\BillAuditLog;
use Tourze\HotelAgentBundle\Enum\BillStatusEnum;

/**
 * 账单审核日志管理控制器
 */
class BillAuditLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BillAuditLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('审核日志')
            ->setEntityLabelInPlural('审核日志')
            ->setPageTitle('index', '账单审核日志')
            ->setPageTitle('detail', '审核日志详情')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(30)
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

            TextField::new('action', '操作类型')
                ->setColumns(6),

            ChoiceField::new('fromStatus', '变更前状态')
                ->setChoices(BillStatusEnum::cases())
                ->renderAsBadges([
                    BillStatusEnum::PENDING->value => 'warning',
                    BillStatusEnum::CONFIRMED->value => 'info',
                    BillStatusEnum::PAID->value => 'success'
                ])
                ->hideOnIndex(),

            ChoiceField::new('toStatus', '变更后状态')
                ->setChoices(BillStatusEnum::cases())
                ->renderAsBadges([
                    BillStatusEnum::PENDING->value => 'warning',
                    BillStatusEnum::CONFIRMED->value => 'info',
                    BillStatusEnum::PAID->value => 'success'
                ])
                ->hideOnIndex(),

            TextareaField::new('remarks', '备注')
                ->hideOnIndex(),

            ArrayField::new('changeDetails', '变更详情')
                ->hideOnIndex(),

            TextField::new('operatorName', '操作人')
                ->setColumns(6),

            TextField::new('ipAddress', 'IP地址')
                ->hideOnIndex(),

            DateTimeField::new('createTime', '操作时间')
                ->setFormat('yyyy-MM-dd HH:mm:ss')
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('agentBill')
            ->add('action')
            ->add(ChoiceFilter::new('fromStatus')->setChoices(BillStatusEnum::cases()))
            ->add(ChoiceFilter::new('toStatus')->setChoices(BillStatusEnum::cases()))
            ->add('operatorName')
            ->add(DateTimeFilter::new('createTime'));
    }
} 