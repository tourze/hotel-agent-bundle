<?php

declare(strict_types=1);

namespace Tourze\HotelAgentBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 审核状态枚举
 */
enum AuditStatusEnum: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 'pending';
    case APPROVED = 'approved';
    case RISK_REVIEW = 'risk_review';
    case REJECTED = 'rejected';
    case COMPLETED = 'completed';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待审核',
            self::APPROVED => '通过',
            self::RISK_REVIEW => '风险审核',
            self::REJECTED => '拒绝',
            self::COMPLETED => '已完成',
        };
    }

    /**
     * 获取选择选项（用于EasyAdmin）
     * @return array<string, string>
     */
    public static function getSelectOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->getLabel()] = $case->value;
        }

        return $options;
    }

    /**
     * 生成枚举下拉选项
     */
}
