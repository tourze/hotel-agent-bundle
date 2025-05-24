<?php

namespace Tourze\HotelAgentBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 结算类型枚举
 */
enum SettlementTypeEnum: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case MONTHLY = 'monthly';
    case HALF_MONTHLY = 'half_monthly';
    case WEEKLY = 'weekly';

    public function getLabel(): string
    {
        return match($this) {
            self::MONTHLY => '月结',
            self::HALF_MONTHLY => '半月结',
            self::WEEKLY => '周结',
        };
    }
}
