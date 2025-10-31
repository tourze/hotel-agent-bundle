<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 账单状态枚举
 */
enum BillStatusEnum: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PAID = 'paid';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待确认',
            self::CONFIRMED => '已确认',
            self::PAID => '已支付',
        };
    }

    /**
     * 生成枚举下拉选项
     */
}
