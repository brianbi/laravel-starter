<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowTask extends Model
{
    use HasFactory;

    // 状态常量
    public const STATUS_PENDING = 0;
    public const STATUS_COMPLETED = 1;    // 别名 - 用于兼容
    public const STATUS_PROCESSED = 1;
    public const STATUS_DELEGATED = 2;
    public const STATUS_CANCELLED = 3;

    public $timestamps = false;

    protected $fillable = [
        'instance_id',
        'node_id',
        'node_type',
        'node_name',
        'assignee_id',
        'assignee_type',
        'status',
        'action',
        'comment',
        'delegate_from',
        'timeout_at',
        'processed_at',
        'created_at',
    ];

    protected $casts = [
        'status' => 'integer',
        'timeout_at' => 'datetime',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (WorkflowTask $task) {
            $task->created_at = $task->created_at ?? now();
        });
    }

    /**
     * 流程实例
     */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'instance_id');
    }

    /**
     * 指派人
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * 委托来源用户
     */
    public function delegateFromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegate_from');
    }

    /**
     * 是否待处理
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * 是否已处理
     */
    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    /**
     * 是否已转办
     */
    public function isDelegated(): bool
    {
        return $this->status === self::STATUS_DELEGATED;
    }

    /**
     * 是否已取消
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * 是否超时
     */
    public function isTimeout(): bool
    {
        return $this->timeout_at && $this->timeout_at->isPast();
    }

    /**
     * 完成任务
     */
    public function complete(string $action, ?string $comment = null): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSED,
            'action' => $action,
            'comment' => $comment,
            'processed_at' => now(),
        ]);
    }

    /**
     * 转办任务
     */
    public function delegate(int $toUserId): self
    {
        // 标记当前任务为已转办
        $this->update([
            'status' => self::STATUS_DELEGATED,
            'action' => 'delegate',
            'processed_at' => now(),
        ]);

        // 创建新任务
        return self::create([
            'instance_id' => $this->instance_id,
            'node_id' => $this->node_id,
            'node_type' => $this->node_type,
            'node_name' => $this->node_name,
            'assignee_id' => $toUserId,
            'assignee_type' => 'user',
            'status' => self::STATUS_PENDING,
            'delegate_from' => $this->assignee_id,
            'timeout_at' => $this->timeout_at,
        ]);
    }

    /**
     * 取消任务
     */
    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'processed_at' => now(),
        ]);
    }

    /**
     * 状态文本
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => '待处理',
            self::STATUS_PROCESSED => '已处理',
            self::STATUS_DELEGATED => '已转办',
            self::STATUS_CANCELLED => '已取消',
            default => '未知',
        };
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', self::STATUS_PROCESSED);
    }

    public function scopeByAssignee($query, int $userId)
    {
        return $query->where('assignee_id', $userId);
    }

    public function scopeByNode($query, string $nodeId)
    {
        return $query->where('node_id', $nodeId);
    }

    public function scopeTimeout($query)
    {
        return $query->pending()->where('timeout_at', '<', now());
    }
}
