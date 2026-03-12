<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 数据权限范围枚举
 */
enum DataScopeEnum: int
{
    case ALL = 1;                    // 全部数据权限
    case CUSTOM = 2;                 // 自定义数据权限
    case DEPARTMENT = 3;             // 本部门数据权限
    case DEPARTMENT_AND_BELOW = 4;   // 本部门及以下数据权限
    case SELF = 5;                   // 仅本人数据权限

    /**
     * 获取标签
     */
    public function label(): string
    {
        return match ($this) {
            self::ALL => '全部数据权限',
            self::CUSTOM => '自定义数据权限',
            self::DEPARTMENT => '本部门数据权限',
            self::DEPARTMENT_AND_BELOW => '本部门及以下数据权限',
            self::SELF => '仅本人数据权限',
        };
    }

    /**
     * 获取所有选项
     */
    public static function options(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
