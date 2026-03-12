<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 菜单类型枚举
 */
enum MenuTypeEnum: string
{
    case MENU = 'M';       // 菜单
    case BUTTON = 'B';     // 按钮
    case LINK = 'L';       // 外链

    /**
     * 获取标签
     */
    public function label(): string
    {
        return match ($this) {
            self::MENU => '菜单',
            self::BUTTON => '按钮',
            self::LINK => '外链',
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
