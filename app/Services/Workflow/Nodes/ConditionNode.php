<?php

namespace App\Services\Workflow\Nodes;

use App\Models\WorkflowInstance;
use App\Models\WorkflowTask;

/**
 * 条件分支节点
 * 
 * 根据表单数据条件判断走不同的分支
 */
class ConditionNode extends BaseNode
{
    public function getType(): string
    {
        return 'condition';
    }

    public function getName(): string
    {
        return '条件分支';
    }

    public function getConfigSchema(): array
    {
        return [
            'conditions' => [
                'type' => 'array',
                'label' => '条件配置',
                'items' => [
                    'expression' => [
                        'type' => 'string',
                        'label' => '条件表达式',
                        'description' => '如: amount > 10000, status == "approved"',
                    ],
                    'target' => [
                        'type' => 'string',
                        'label' => '目标节点ID',
                    ],
                ],
            ],
            'default_target' => [
                'type' => 'string',
                'label' => '默认目标节点',
                'description' => '当所有条件都不满足时的目标节点',
            ],
        ];
    }

    public function execute(WorkflowInstance $instance, array $node): void
    {
        $conditions = $this->getNodeConfig($node, 'conditions', []);
        $defaultTarget = $this->getNodeConfig($node, 'default_target');
        $formData = $instance->form_data ?? [];

        $targetNodeId = null;

        // 依次检查条件
        foreach ($conditions as $condition) {
            $expression = $condition['expression'] ?? '';
            $target = $condition['target'] ?? null;

            if ($expression === 'default') {
                $defaultTarget = $target;
                continue;
            }

            if ($this->evaluateExpression($expression, $formData)) {
                $targetNodeId = $target;
                break;
            }
        }

        // 如果没有匹配的条件，使用默认目标
        if (!$targetNodeId) {
            $targetNodeId = $defaultTarget;
        }

        // 流转到目标节点
        if ($targetNodeId) {
            $this->engine->executeNode($instance, $targetNodeId);
        }
    }

    /**
     * 评估条件表达式
     * 
     * 安全增强：添加输入验证、空值处理和类型安全检查
     */
    protected function evaluateExpression(string $expression, array $data): bool
    {
        // 空表达式直接返回 false
        if (empty($expression) || !is_string($expression)) {
            return false;
        }

        // 安全：只允许字母、数字、下划线作为字段名
        $safeExpression = preg_replace('/[^\w\s\-_>.<>=!\'\"\[\],]/', '', $expression);
        
        // 支持的操作符: ==, !=, >, >=, <, <=, in, not_in, contains
        $patterns = [
            // field in [value1, value2]
            '/^(\w+)\s+in\s+\[([^\]]+)\]$/' => function($matches, $data) {
                $field = $matches[1];
                $valuesStr = $matches[2];
                
                // 安全：解析逗号分隔的值列表
                $values = array_map(function($v) {
                    $trimmed = trim($v);
                    return trim($trimmed, '"\'');
                }, explode(',', $valuesStr));
                
                $fieldValue = data_get($data, $field);
                
                // 安全：空值处理
                if ($fieldValue === null || $fieldValue === '') {
                    return false;
                }
                
                return in_array($fieldValue, $values, true);
            },
            // field not_in [value1, value2]
            '/^(\w+)\s+not_in\s+\[([^\]]+)\]$/' => function($matches, $data) {
                $field = $matches[1];
                $valuesStr = $matches[2];
                
                $values = array_map(function($v) {
                    $trimmed = trim($v);
                    return trim($trimmed, '"\'');
                }, explode(',', $valuesStr));
                
                $fieldValue = data_get($data, $field);
                
                // 安全：空值处理
                if ($fieldValue === null || $fieldValue === '') {
                    return true;
                }
                
                return !in_array($fieldValue, $values, true);
            },
            // field contains "value"
            '/^(\w+)\s+contains\s+"([^"]*)"$/' => function($matches, $data) {
                $field = $matches[1];
                $value = $matches[2];
                $fieldValue = data_get($data, $field, '');
                
                // 安全：确保是字符串才能使用 str_contains
                if (!is_string($fieldValue)) {
                    return false;
                }
                
                return str_contains($fieldValue, $value);
            },
            // field == value, field != value, field > value, etc.
            '/^(\w+)\s*(==|!=|>|>=|<|<=)\s*(.+)$/' => function($matches, $data) {
                $field = $matches[1];
                $operator = $matches[2];
                $value = trim($matches[3], '"\'');
                
                $fieldValue = data_get($data, $field);
                
                // 安全：空值处理 - 空值不满足任何比较条件
                if ($fieldValue === null || $fieldValue === '') {
                    // 但等于比较空字符串时返回 true
                    if ($operator === '==' && $value === '') {
                        return true;
                    }
                    return false;
                }
                
                // 安全：类型处理
                $isFieldNumeric = is_numeric($fieldValue);
                $isValueNumeric = is_numeric($value);
                
                // 如果两者都是数值，进行数值比较
                if ($isFieldNumeric && $isValueNumeric) {
                    $fieldValue = floatval($fieldValue);
                    $value = floatval($value);
                }
                
                // 字符串比较
                if (!$isFieldNumeric || !$isValueNumeric) {
                    $fieldValue = (string) $fieldValue;
                }
                
                return match ($operator) {
                    '==' => $fieldValue == $value,
                    '!=' => $fieldValue != $value,
                    '>' => $fieldValue > $value,
                    '>=' => $fieldValue >= $value,
                    '<' => $fieldValue < $value,
                    '<=' => $fieldValue <= $value,
                    default => false,
                };
            },
        ];

        foreach ($patterns as $pattern => $evaluator) {
            if (preg_match($pattern, $safeExpression, $matches)) {
                try {
                    return $evaluator($matches, $data);
                } catch (\Throwable $e) {
                    // 表达式求值出错时返回 false
                    return false;
                }
            }
        }

        // 未知表达式格式返回 false
        return false;
    }

    public function complete(WorkflowTask $task, string $action, array $data): void
    {
        // 条件节点不需要人工处理
    }

    public function canAutoComplete(WorkflowInstance $instance, array $node): bool
    {
        return true;
    }
}
