<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 支付方式枚举
 */
enum PaymentMethodEnum: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case BANK_TRANSFER = 'bank_transfer';      // 银行转账
    case ALIPAY = 'alipay';                    // 支付宝
    case WECHAT = 'wechat';                    // 微信支付
    case CASH = 'cash';                        // 现金
    case CHECK = 'check';                      // 支票
    case CREDIT_CARD = 'credit_card';          // 信用卡
    case ONLINE_BANKING = 'online_banking';    // 网银支付
    case MONTHLY_SETTLEMENT = 'monthly_settlement'; // 月结

    public function getLabel(): string
    {
        return match ($this) {
            self::BANK_TRANSFER => '银行转账',
            self::ALIPAY => '支付宝',
            self::WECHAT => '微信支付',
            self::CASH => '现金',
            self::CHECK => '支票',
            self::CREDIT_CARD => '信用卡',
            self::ONLINE_BANKING => '网银支付',
            self::MONTHLY_SETTLEMENT => '月结',
        };
    }

    /**
     * 生成枚举下拉选项
     */
}
