<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * 状态字段处理
 */
trait HasStatus
{
    /**
     * 状态：禁用
     */
    public const STATUS_DISABLED = 0;

    /**
     * 状态：正常
     */
    public const STATUS_ENABLED = 1;

    /**
     * 状态映射
     */
    public static function getStatusMap(): array
    {
        return [
            self::STATUS_DISABLED => '禁用',
            self::STATUS_ENABLED => '正常',
        ];
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute(): string
    {
        return self::getStatusMap()[$this->status] ?? '未知';
    }

    /**
     * 是否启用
     */
    public function isEnabled(): bool
    {
        return $this->status === self::STATUS_ENABLED;
    }

    /**
     * 是否禁用
     */
    public function isDisabled(): bool
    {
        return $this->status === self::STATUS_DISABLED;
    }

    /**
     * 筛选启用的记录
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ENABLED);
    }

    /**
     * 筛选禁用的记录
     */
    public function scopeDisabled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DISABLED);
    }
}
