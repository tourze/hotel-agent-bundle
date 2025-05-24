<?php

namespace Tourze\HotelAgentBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 代理等级枚举
 */
enum AgentLevelEnum: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case A = 'a';
    case B = 'b';
    case C = 'c';

    public function getLabel(): string
    {
        return match($this) {
            self::A => 'A级',
            self::B => 'B级',
            self::C => 'C级',
        };
    }
}
