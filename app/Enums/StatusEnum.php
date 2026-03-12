<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 状态枚举
 */
enum StatusEnum: int
{
    case DISABLED = 0;
    case ENABLED = 1;

    /**
     * 获取标签
     */
    public function label(): string
    {
        return match ($this) {
            self::DISABLED => '禁用',
            self::ENABLED => '正常',
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
