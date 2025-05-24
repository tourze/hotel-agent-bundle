<?php

namespace Tourze\HotelAgentBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 订单状态枚举
 */
enum OrderStatusEnum: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELED = 'canceled';
    case CLOSED = 'closed';

    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => '待确认',
            self::CONFIRMED => '已确认',
            self::CANCELED => '已取消',
            self::CLOSED => '已关闭',
        };
    }
}
