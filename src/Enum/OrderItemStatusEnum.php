<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 订单明细状态枚举
 */
enum OrderItemStatusEnum: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELED = 'canceled';
    case COMPLETED = 'completed';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待确认',
            self::CONFIRMED => '已确认',
            self::CANCELED => '已取消',
            self::COMPLETED => '已完成',
        };
    }

    /**
     * 生成枚举下拉选项
     */
}
