<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowRecord extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'instance_id',
        'task_id',
        'node_id',
        'node_name',
        'user_id',
        'action',
        'comment',
        'form_data',
        'created_at',
        'ip',
        'user_agent',
        'request_id',
    ];

    protected $casts = [
        'form_data' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (WorkflowRecord $record) {
            $record->created_at = $record->created_at ?? now();
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
     * 关联任务
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(WorkflowTask::class, 'task_id');
    }

    /**
     * 操作人
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 操作文本
     */
    public function getActionTextAttribute(): string
    {
        return match ($this->action) {
            'submit' => '提交',
            'approve' => '通过',
            'reject' => '拒绝',
            'return' => '退回',
            'delegate' => '转办',
            'add_sign' => '加签',
            'withdraw' => '撤回',
            'cc' => '抄送',
            default => $this->action,
        };
    }

    /**
     * 创建审批记录
     *
     * @param WorkflowInstance $instance 流程实例
     * @param string $nodeId 节点ID
     * @param string $nodeName 节点名称
     * @param int $userId 用户ID
     * @param string $action 操作类型
     * @param string|null $comment 备注
     * @param array|null $formData 表单数据
     * @param int|null $taskId 任务ID
     * @return self
     */
    public static function createRecord(
        WorkflowInstance $instance,
        string $nodeId,
        string $nodeName,
        int $userId,
        string $action,
        ?string $comment = null,
        ?array $formData = null,
        ?int $taskId = null
    ): self {
        $request = request();

        return self::create([
            'instance_id' => $instance->id,
            'task_id' => $taskId,
            'node_id' => $nodeId,
            'node_name' => $nodeName,
            'user_id' => $userId,
            'action' => $action,
            'comment' => $comment,
            'form_data' => $formData,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_id' => $request->header('X-Request-ID'),
        ]);
    }

    // Scopes
    public function scopeByInstance($query, int $instanceId)
    {
        return $query->where('instance_id', $instanceId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }
}
