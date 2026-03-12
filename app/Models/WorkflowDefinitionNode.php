<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowDefinitionNode extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'definition_id',
        'node_id',
        'type',
        'name',
        'config',
        'field_permissions',
        'timeout_config',
        'sort',
    ];

    protected $casts = [
        'config' => 'array',
        'field_permissions' => 'array',
        'timeout_config' => 'array',
        'sort' => 'integer',
    ];

    /**
     * 所属流程定义
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'definition_id');
    }

    /**
     * 获取审批人配置
     */
    public function getAssigneeConfig(): array
    {
        return $this->config['assignee'] ?? [];
    }

    /**
     * 获取字段权限
     */
    public function getFieldPermission(string $field): array
    {
        $permissions = $this->field_permissions ?? [];
        return $permissions[$field] ?? ['visible' => true, 'editable' => false];
    }

    /**
     * 是否启用超时
     */
    public function isTimeoutEnabled(): bool
    {
        $config = $this->timeout_config ?? [];
        return $config['enabled'] ?? false;
    }

    /**
     * 获取超时时间（小时）
     */
    public function getTimeoutHours(): ?int
    {
        $config = $this->timeout_config ?? [];
        return $config['hours'] ?? null;
    }

    /**
     * 获取超时操作
     */
    public function getTimeoutAction(): ?string
    {
        $config = $this->timeout_config ?? [];
        return $config['action'] ?? null;
    }
}
