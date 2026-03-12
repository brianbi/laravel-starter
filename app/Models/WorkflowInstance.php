<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkflowInstance extends Model
{
    use HasFactory;

    // 状态常量
    public const STATUS_DRAFT = 0;
    public const STATUS_PENDING = 1;      // 别名 - 用于兼容
    public const STATUS_RUNNING = 1;
    public const STATUS_APPROVED = 2;
    public const STATUS_REJECTED = 3;
    public const STATUS_WITHDRAWN = 4;

    protected $fillable = [
        'definition_id',
        'definition_version',
        'business_type',
        'business_id',
        'form_data',
        'initiator_id',
        'current_node_id',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'form_data' => 'array',
        'definition_version' => 'integer',
        'status' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * 流程定义
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'definition_id');
    }

    /**
     * 发起人
     */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    /**
     * 待办任务
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(WorkflowTask::class, 'instance_id');
    }

    /**
     * 审批记录
     */
    public function records(): HasMany
    {
        return $this->hasMany(WorkflowRecord::class, 'instance_id')->orderBy('created_at');
    }

    /**
     * 关联的业务模型
     */
    public function business(): MorphTo
    {
        return $this->morphTo(null, 'business_type', 'business_id');
    }

    /**
     * 获取当前待处理的任务
     */
    public function getPendingTasks(): HasMany
    {
        return $this->tasks()->pending();
    }

    /**
     * 是否草稿
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * 是否进行中
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * 是否已通过
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * 是否已拒绝
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * 是否已撤回
     */
    public function isWithdrawn(): bool
    {
        return $this->status === self::STATUS_WITHDRAWN;
    }

    /**
     * 是否已完成（通过、拒绝、撤回）
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_WITHDRAWN,
        ]);
    }

    /**
     * 启动流程
     */
    public function start(string $nodeId): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'current_node_id' => $nodeId,
            'started_at' => now(),
        ]);
    }

    /**
     * 完成流程（通过）
     */
    public function approve(): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'completed_at' => now(),
        ]);
    }

    /**
     * 拒绝流程
     */
    public function reject(): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'completed_at' => now(),
        ]);
    }

    /**
     * 撤回流程
     */
    public function withdraw(): void
    {
        $this->update([
            'status' => self::STATUS_WITHDRAWN,
            'completed_at' => now(),
        ]);
    }

    /**
     * 移动到指定节点
     */
    public function moveToNode(string $nodeId): void
    {
        $this->update(['current_node_id' => $nodeId]);
    }

    /**
     * 更新表单数据
     */
    public function updateFormData(array $data): void
    {
        $formData = $this->form_data ?? [];
        $this->update(['form_data' => array_merge($formData, $data)]);
    }

    /**
     * 获取表单字段值
     */
    public function getFormValue(string $field, $default = null)
    {
        $formData = $this->form_data ?? [];
        return data_get($formData, $field, $default);
    }

    /**
     * 状态文本
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => '草稿',
            self::STATUS_RUNNING => '进行中',
            self::STATUS_APPROVED => '已通过',
            self::STATUS_REJECTED => '已拒绝',
            self::STATUS_WITHDRAWN => '已撤回',
            default => '未知',
        };
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeByInitiator($query, int $userId)
    {
        return $query->where('initiator_id', $userId);
    }

    public function scopeByBusiness($query, string $type, int $id)
    {
        return $query->where('business_type', $type)->where('business_id', $id);
    }
}
