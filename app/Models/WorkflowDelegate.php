<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 工作流代理委托模型
 * 
 * 用于配置用户外出时将审批任务自动转交给代理人处理
 */
class WorkflowDelegate extends Model
{
    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;

    protected $fillable = [
        'user_id',
        'delegate_id',
        'definition_id',
        'start_at',
        'end_at',
        'status',
        'remark',
    ];

    protected $casts = [
        'status' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    /**
     * 委托人
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 代理人
     */
    public function delegate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegate_id');
    }

    /**
     * 指定流程
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'definition_id');
    }

    /**
     * 是否启用
     */
    public function isEnabled(): bool
    {
        return $this->status === self::STATUS_ENABLED;
    }

    /**
     * 是否在有效期内
     */
    public function isActive(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $now = now();
        return $now->gte($this->start_at) && $now->lte($this->end_at);
    }

    /**
     * 是否适用于指定流程
     */
    public function isApplicableTo(?int $definitionId): bool
    {
        // 如果没有指定流程（definition_id 为空），适用于所有流程
        if ($this->definition_id === null) {
            return true;
        }

        return $this->definition_id === $definitionId;
    }

    /**
     * 启用
     */
    public function enable(): void
    {
        $this->update(['status' => self::STATUS_ENABLED]);
    }

    /**
     * 禁用
     */
    public function disable(): void
    {
        $this->update(['status' => self::STATUS_DISABLED]);
    }

    /**
     * 状态文本
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DISABLED => '禁用',
            self::STATUS_ENABLED => '启用',
            default => '未知',
        };
    }

    /**
     * 是否有效（启用且在有效期内）
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->isActive();
    }

    // Scopes

    /**
     * 启用状态
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_ENABLED);
    }

    /**
     * 当前有效的代理配置
     */
    public function scopeActive($query)
    {
        $now = now();
        return $query->enabled()
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now);
    }

    /**
     * 指定用户的代理配置
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 指定代理人的配置
     */
    public function scopeByDelegate($query, int $delegateId)
    {
        return $query->where('delegate_id', $delegateId);
    }

    /**
     * 适用于指定流程的配置
     */
    public function scopeForDefinition($query, ?int $definitionId)
    {
        return $query->where(function ($q) use ($definitionId) {
            $q->whereNull('definition_id')
                ->orWhere('definition_id', $definitionId);
        });
    }

    /**
     * 查找用户当前有效的代理人
     */
    public static function findActiveDelegate(int $userId, ?int $definitionId = null): ?int
    {
        $delegate = static::active()
            ->byUser($userId)
            ->forDefinition($definitionId)
            ->first();

        return $delegate?->delegate_id;
    }
}
