<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * 表单定义模型
 * 
 * 支持可视化表单设计器的表单定义存储
 */
class FormDefinition extends Model
{
    use HasFactory;
    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;

    // 字段类型常量
    public const FIELD_TYPE_INPUT = 'input';
    public const FIELD_TYPE_TEXTAREA = 'textarea';
    public const FIELD_TYPE_NUMBER = 'number';
    public const FIELD_TYPE_SELECT = 'select';
    public const FIELD_TYPE_MULTI_SELECT = 'multi_select';
    public const FIELD_TYPE_RADIO = 'radio';
    public const FIELD_TYPE_CHECKBOX = 'checkbox';
    public const FIELD_TYPE_DATE = 'date';
    public const FIELD_TYPE_DATETIME = 'datetime';
    public const FIELD_TYPE_TIME = 'time';
    public const FIELD_TYPE_FILE = 'file';
    public const FIELD_TYPE_IMAGE = 'image';
    public const FIELD_TYPE_USER_SELECT = 'user_select';
    public const FIELD_TYPE_DEPT_SELECT = 'dept_select';
    public const FIELD_TYPE_MONEY = 'money';
    public const FIELD_TYPE_RATE = 'rate';
    public const FIELD_TYPE_SWITCH = 'switch';
    public const FIELD_TYPE_RICH_TEXT = 'rich_text';
    public const FIELD_TYPE_TABLE = 'table';

    protected $fillable = [
        'code',
        'name',
        'description',
        'fields',
        'layout',
        'rules',
        'status',
        'created_by',
    ];

    protected $casts = [
        'fields' => 'array',
        'layout' => 'array',
        'rules' => 'array',
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
     * 表单数据
     */
    public function formData(): HasMany
    {
        return $this->hasMany(FormData::class, 'form_id');
    }

    /**
     * 是否启用
     */
    public function isEnabled(): bool
    {
        return $this->status === self::STATUS_ENABLED;
    }

    /**
     * 获取所有字段定义
     */
    public function getFields(): array
    {
        return $this->fields ?? [];
    }

    /**
     * 获取指定字段定义
     */
    public function getField(string $fieldName): ?array
    {
        $fields = $this->getFields();
        
        foreach ($fields as $field) {
            if (($field['name'] ?? '') === $fieldName) {
                return $field;
            }
        }
        
        return null;
    }

    /**
     * 获取字段名称列表
     */
    public function getFieldNames(): array
    {
        $fields = $this->getFields();
        return array_column($fields, 'name');
    }

    /**
     * 生成 Laravel 验证规则
     */
    public function generateValidationRules(): array
    {
        $rules = [];
        $fields = $this->getFields();

        foreach ($fields as $field) {
            $fieldName = $field['name'] ?? '';
            if (!$fieldName) {
                continue;
            }

            $fieldRules = [];
            $fieldConfig = $field['config'] ?? [];

            // 必填
            if (!empty($field['required'])) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            // 根据字段类型添加规则
            switch ($field['type'] ?? '') {
                case self::FIELD_TYPE_INPUT:
                    $fieldRules[] = 'string';
                    if (!empty($fieldConfig['max_length'])) {
                        $fieldRules[] = 'max:' . $fieldConfig['max_length'];
                    }
                    break;

                case self::FIELD_TYPE_TEXTAREA:
                case self::FIELD_TYPE_RICH_TEXT:
                    $fieldRules[] = 'string';
                    if (!empty($fieldConfig['max_length'])) {
                        $fieldRules[] = 'max:' . $fieldConfig['max_length'];
                    }
                    break;

                case self::FIELD_TYPE_NUMBER:
                case self::FIELD_TYPE_MONEY:
                    $fieldRules[] = 'numeric';
                    if (isset($fieldConfig['min'])) {
                        $fieldRules[] = 'min:' . $fieldConfig['min'];
                    }
                    if (isset($fieldConfig['max'])) {
                        $fieldRules[] = 'max:' . $fieldConfig['max'];
                    }
                    break;

                case self::FIELD_TYPE_SELECT:
                case self::FIELD_TYPE_RADIO:
                    $options = $fieldConfig['options'] ?? [];
                    if (!empty($options)) {
                        $values = array_column($options, 'value');
                        $fieldRules[] = 'in:' . implode(',', $values);
                    }
                    break;

                case self::FIELD_TYPE_MULTI_SELECT:
                case self::FIELD_TYPE_CHECKBOX:
                    $fieldRules[] = 'array';
                    break;

                case self::FIELD_TYPE_DATE:
                    $fieldRules[] = 'date';
                    break;

                case self::FIELD_TYPE_DATETIME:
                    $fieldRules[] = 'date';
                    break;

                case self::FIELD_TYPE_TIME:
                    $fieldRules[] = 'date_format:H:i:s';
                    break;

                case self::FIELD_TYPE_USER_SELECT:
                case self::FIELD_TYPE_DEPT_SELECT:
                    if (!empty($fieldConfig['multiple'])) {
                        $fieldRules[] = 'array';
                    } else {
                        $fieldRules[] = 'integer';
                    }
                    break;

                case self::FIELD_TYPE_SWITCH:
                    $fieldRules[] = 'boolean';
                    break;

                case self::FIELD_TYPE_RATE:
                    $fieldRules[] = 'integer';
                    $fieldRules[] = 'min:0';
                    $fieldRules[] = 'max:' . ($fieldConfig['max'] ?? 5);
                    break;

                case self::FIELD_TYPE_FILE:
                case self::FIELD_TYPE_IMAGE:
                    if (!empty($fieldConfig['multiple'])) {
                        $fieldRules[] = 'array';
                    } else {
                        $fieldRules[] = 'string';
                    }
                    break;

                case self::FIELD_TYPE_TABLE:
                    $fieldRules[] = 'array';
                    break;
            }

            // 添加自定义规则
            if (!empty($field['rules'])) {
                $customRules = is_array($field['rules']) ? $field['rules'] : [$field['rules']];
                $fieldRules = array_merge($fieldRules, $customRules);
            }

            $rules[$fieldName] = $fieldRules;
        }

        // 合并表单级别的自定义规则
        if (!empty($this->rules)) {
            $rules = array_merge($rules, $this->rules);
        }

        return $rules;
    }

    /**
     * 验证表单数据
     */
    public function validateData(array $data): array
    {
        $rules = $this->generateValidationRules();
        $messages = $this->generateValidationMessages();
        $attributes = $this->generateValidationAttributes();

        $validator = Validator::make($data, $rules, $messages, $attributes);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * 生成验证消息
     */
    protected function generateValidationMessages(): array
    {
        $messages = [];
        $fields = $this->getFields();

        foreach ($fields as $field) {
            $fieldName = $field['name'] ?? '';
            $label = $field['label'] ?? $fieldName;

            if (!empty($field['required'])) {
                $messages["{$fieldName}.required"] = "{$label}不能为空";
            }
        }

        return $messages;
    }

    /**
     * 生成字段属性名
     */
    protected function generateValidationAttributes(): array
    {
        $attributes = [];
        $fields = $this->getFields();

        foreach ($fields as $field) {
            $fieldName = $field['name'] ?? '';
            $attributes[$fieldName] = $field['label'] ?? $fieldName;
        }

        return $attributes;
    }

    /**
     * 获取可用的字段类型列表
     */
    public static function getFieldTypes(): array
    {
        return [
            self::FIELD_TYPE_INPUT => ['name' => '单行文本', 'icon' => 'text'],
            self::FIELD_TYPE_TEXTAREA => ['name' => '多行文本', 'icon' => 'textarea'],
            self::FIELD_TYPE_NUMBER => ['name' => '数字', 'icon' => 'number'],
            self::FIELD_TYPE_SELECT => ['name' => '下拉选择', 'icon' => 'select'],
            self::FIELD_TYPE_MULTI_SELECT => ['name' => '多选下拉', 'icon' => 'multi-select'],
            self::FIELD_TYPE_RADIO => ['name' => '单选框', 'icon' => 'radio'],
            self::FIELD_TYPE_CHECKBOX => ['name' => '复选框', 'icon' => 'checkbox'],
            self::FIELD_TYPE_DATE => ['name' => '日期', 'icon' => 'date'],
            self::FIELD_TYPE_DATETIME => ['name' => '日期时间', 'icon' => 'datetime'],
            self::FIELD_TYPE_TIME => ['name' => '时间', 'icon' => 'time'],
            self::FIELD_TYPE_FILE => ['name' => '文件上传', 'icon' => 'file'],
            self::FIELD_TYPE_IMAGE => ['name' => '图片上传', 'icon' => 'image'],
            self::FIELD_TYPE_USER_SELECT => ['name' => '人员选择', 'icon' => 'user'],
            self::FIELD_TYPE_DEPT_SELECT => ['name' => '部门选择', 'icon' => 'department'],
            self::FIELD_TYPE_MONEY => ['name' => '金额', 'icon' => 'money'],
            self::FIELD_TYPE_RATE => ['name' => '评分', 'icon' => 'rate'],
            self::FIELD_TYPE_SWITCH => ['name' => '开关', 'icon' => 'switch'],
            self::FIELD_TYPE_RICH_TEXT => ['name' => '富文本', 'icon' => 'rich-text'],
            self::FIELD_TYPE_TABLE => ['name' => '子表格', 'icon' => 'table'],
        ];
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
