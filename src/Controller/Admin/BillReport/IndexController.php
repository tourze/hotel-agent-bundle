<?php

namespace Tourze\HotelAgentBundle\Controller\Admin\BillReport;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * 账单统计报表首页
 */
class IndexController extends AbstractController
{
    #[Route(path: '/admin/bill-report', name: 'admin_bill_report_index')]
    public function __invoke(): Response
    {
        return $this->render('admin/bill_report/index.html.twig');
    }
}
