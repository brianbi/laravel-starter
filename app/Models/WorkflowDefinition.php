<?php

namespace App\Models;

use App\Traits\HasCreator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowDefinition extends Model
{
    use HasFactory, HasCreator;
    // 状态常量
    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;

    // 表单类型
    public const FORM_TYPE_BUILTIN = 'builtin';
    public const FORM_TYPE_MODEL = 'model';

    protected $fillable = [
        'code',
        'name',
        'description',
        'form_type',
        'form_id',
        'model_class',
        'version',
        'graph',
        'status',
        'created_by',
    ];

    protected $casts = [
        'graph' => 'array',
        'version' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 创建人
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 节点定义
     */
    public function nodes(): HasMany
    {
        return $this->hasMany(WorkflowDefinitionNode::class, 'definition_id')->orderBy('sort');
    }

    /**
     * 流程实例
     */
    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class, 'definition_id');
    }

    /**
     * 是否启用
     */
    public function isEnabled(): bool
    {
        return $this->status === self::STATUS_ENABLED;
    }

    /**
     * 获取指定节点配置
     */
    public function getNode(string $nodeId): ?array
    {
        $graph = $this->graph ?? [];
        $nodes = $graph['nodes'] ?? [];
        
        foreach ($nodes as $node) {
            if ($node['id'] === $nodeId) {
                return $node;
            }
        }
        
        return null;
    }

    /**
     * 获取节点的后续连接
     */
    public function getOutgoingEdges(string $nodeId): array
    {
        $graph = $this->graph ?? [];
        $edges = $graph['edges'] ?? [];
        
        return array_values(array_filter($edges, fn($edge) => $edge['source'] === $nodeId));
    }

    /**
     * 获取开始节点
     */
    public function getStartNode(): ?array
    {
        $graph = $this->graph ?? [];
        $nodes = $graph['nodes'] ?? [];
        
        foreach ($nodes as $node) {
            if ($node['type'] === 'start') {
                return $node;
            }
        }
        
        return null;
    }

    /**
     * 发布新版本
     */
    public function publish(): self
    {
        $this->increment('version');
        $this->update(['status' => self::STATUS_ENABLED]);
        
        return $this;
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
     * 表单类型文本
     */
    public function getFormTypeTextAttribute(): string
    {
        return match ($this->form_type) {
            self::FORM_TYPE_BUILTIN => '内置表单',
            self::FORM_TYPE_MODEL => '业务模型',
            default => '未知',
        };
    }

    // Scopes
    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_ENABLED);
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }
}
