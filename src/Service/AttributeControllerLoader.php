<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Service;

use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Routing\RouteCollection;
use Tourze\HotelAgentBundle\Controller\Admin\AgentBillCrudController;
use Tourze\HotelAgentBundle\Controller\Admin\AgentCrudController;
use Tourze\HotelAgentBundle\Controller\Admin\AgentHotelMappingCrudController;
use Tourze\HotelAgentBundle\Controller\Admin\BillAuditLogCrudController;
use Tourze\HotelAgentBundle\Controller\Admin\BillReport\AuditStatsController;
use Tourze\HotelAgentBundle\Controller\Admin\BillReport\DetailedReportController;
use Tourze\HotelAgentBundle\Controller\Admin\BillReport\ExportAuditLogsController;
use Tourze\HotelAgentBundle\Controller\Admin\BillReport\ExportReportController;
use Tourze\HotelAgentBundle\Controller\Admin\BillReport\IndexController;
use Tourze\HotelAgentBundle\Controller\Admin\BillReport\MonthlyStatsController;
use Tourze\HotelAgentBundle\Controller\Admin\Order\AjaxInventoryController;
use Tourze\HotelAgentBundle\Controller\Admin\OrderCrudController;
use Tourze\HotelAgentBundle\Controller\Admin\PaymentCrudController;
use Tourze\RoutingAutoLoaderBundle\Service\RoutingAutoLoaderInterface;

#[AutoconfigureTag(name: 'routing.loader')]
class AttributeControllerLoader extends Loader implements RoutingAutoLoaderInterface
{
    private AttributeRouteControllerLoader $controllerLoader;

    public function __construct()
    {
        parent::__construct();
        $this->controllerLoader = new AttributeRouteControllerLoader();
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        return $this->autoload();
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return false;
    }

    public function autoload(): RouteCollection
    {
        $collection = new RouteCollection();

        $collection->addCollection($this->controllerLoader->load(AgentBillCrudController::class));
        $collection->addCollection($this->controllerLoader->load(AgentCrudController::class));
        $collection->addCollection($this->controllerLoader->load(AgentHotelMappingCrudController::class));
        $collection->addCollection($this->controllerLoader->load(BillAuditLogCrudController::class));
        $collection->addCollection($this->controllerLoader->load(AuditStatsController::class));
        $collection->addCollection($this->controllerLoader->load(DetailedReportController::class));
        $collection->addCollection($this->controllerLoader->load(ExportAuditLogsController::class));
        $collection->addCollection($this->controllerLoader->load(ExportReportController::class));
        $collection->addCollection($this->controllerLoader->load(IndexController::class));
        $collection->addCollection($this->controllerLoader->load(MonthlyStatsController::class));
        $collection->addCollection($this->controllerLoader->load(AjaxInventoryController::class));
        $collection->addCollection($this->controllerLoader->load(OrderCrudController::class));
        $collection->addCollection($this->controllerLoader->load(PaymentCrudController::class));

        return $collection;
    }
}
