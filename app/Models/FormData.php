<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 表单数据模型
 * 
 * 存储用户提交的表单数据
 */
class FormData extends Model
{
    use HasFactory;
    protected $table = 'form_data';

    protected $fillable = [
        'form_id',
        'instance_id',
        'data',
        'created_by',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * 关联的表单定义
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class, 'form_id');
    }

    /**
     * 关联的流程实例
     */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'instance_id');
    }

    /**
     * 创建人
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 获取表单数据
     */
    public function getData(): array
    {
        return $this->data ?? [];
    }

    /**
     * 获取指定字段的值
     */
    public function getValue(string $field, $default = null)
    {
        return data_get($this->data, $field, $default);
    }

    /**
     * 设置字段值
     */
    public function setValue(string $field, $value): void
    {
        $data = $this->data ?? [];
        data_set($data, $field, $value);
        $this->data = $data;
    }

    /**
     * 批量设置值
     */
    public function setValues(array $values): void
    {
        $data = $this->data ?? [];
        $this->data = array_merge($data, $values);
    }

    /**
     * 验证并保存数据
     */
    public function validateAndSave(array $data): self
    {
        // 验证数据
        $validated = $this->form->validateData($data);
        
        // 更新数据
        $this->data = $validated;
        $this->save();
        
        return $this;
    }

    // Scopes
    public function scopeByForm($query, int $formId)
    {
        return $query->where('form_id', $formId);
    }

    public function scopeByInstance($query, int $instanceId)
    {
        return $query->where('instance_id', $instanceId);
    }

    public function scopeByCreator($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }
}
