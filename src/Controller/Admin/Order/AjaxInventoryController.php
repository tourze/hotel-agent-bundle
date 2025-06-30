<?php

namespace Tourze\HotelAgentBundle\Controller\Admin\Order;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\HotelAgentBundle\Exception\OrderProcessingException;
use Tourze\HotelContractBundle\Service\InventoryQueryService;

/**
 * Ajax接口：获取库存信息
 */
class AjaxInventoryController extends AbstractController
{
    public function __construct(
        private readonly InventoryQueryService $inventoryQueryService
    ) {}

    #[Route(path: '/admin/order/ajax/inventory', name: 'admin_order_ajax_inventory', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        try {
            $roomTypeId = $request->request->get('room_type_id');
            $checkInDate = $request->request->get('check_in_date');
            $checkOutDate = $request->request->get('check_out_date');
            $roomCount = (int)$request->request->get('room_count', 1);

            if (null === $roomTypeId || null === $checkInDate || null === $checkOutDate) {
                throw new OrderProcessingException('参数不完整');
            }

            $data = $this->inventoryQueryService->getInventoryData(
                (int)$roomTypeId,
                $checkInDate,
                $checkOutDate,
                $roomCount
            );

            return $this->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
