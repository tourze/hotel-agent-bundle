<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 代理账户状态枚举
 */
enum AgentStatusEnum: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case ACTIVE = 'active';
    case FROZEN = 'frozen';
    case DISABLED = 'disabled';
    case EXPIRED = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => '激活',
            self::FROZEN => '冻结',
            self::DISABLED => '禁用',
            self::EXPIRED => '已过期',
        };
    }

    /**
     * 生成枚举下拉选项
     */
}
