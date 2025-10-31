<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 支付状态枚举
 */
enum PaymentStatusEnum: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 'pending';        // 待支付
    case SUCCESS = 'success';        // 支付成功
    case FAILED = 'failed';          // 支付失败
    case REFUNDED = 'refunded';      // 已退款
    case CANCELLED = 'cancelled';    // 已取消

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待支付',
            self::SUCCESS => '支付成功',
            self::FAILED => '支付失败',
            self::REFUNDED => '已退款',
            self::CANCELLED => '已取消',
        };
    }

    /**
     * 生成枚举下拉选项
     */
}
