<?php

namespace Tourze\HotelAgentBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 订单来源枚举
 */
enum OrderSourceEnum: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case EXCEL_IMPORT = 'excel_import';
    case MANUAL_INPUT = 'manual_input';

    public function getLabel(): string
    {
        return match($this) {
            self::EXCEL_IMPORT => 'Excel导入',
            self::MANUAL_INPUT => '后台录入',
        };
    }
} 