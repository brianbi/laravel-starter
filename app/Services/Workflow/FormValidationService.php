<?php

declare(strict_types=1);

namespace App\Services\Workflow;

use App\Models\FormDefinition;
use App\Models\WorkflowDefinition;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * 工作流表单验证服务
 *
 * 根据流程定义的表单规则验证表单数据
 */
class FormValidationService
{
    /**
     * 验证发起流程的表单数据
     *
     * @param WorkflowDefinition $definition 流程定义
     * @param array $formData 表单数据
     * @return array 验证通过的数据
     * @throws ValidationException 验证失败
     */
    public function validateStartData(WorkflowDefinition $definition, array $formData): array
    {
        if ($definition->form_type === WorkflowDefinition::FORM_TYPE_MODEL) {
            return $this->validateModelForm($definition, $formData);
        }

        if ($definition->form_type === WorkflowDefinition::FORM_TYPE_BUILTIN) {
            return $this->validateBuiltinForm($definition, $formData);
        }

        return $formData;
    }

    /**
     * 验证业务模型表单
     */
    protected function validateModelForm(WorkflowDefinition $definition, array $formData): array
    {
        $modelClass = $definition->model_class;

        if (!class_exists($modelClass)) {
            throw new InvalidArgumentException("流程定义的模型类不存在: {$modelClass}");
        }

        $rules = $this->getModelRules($modelClass);
        $messages = $this->getModelMessages($modelClass);

        $validator = Validator::make($formData, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * 验证内置表单
     *
     * 内置表单直接使用表单设计器的验证规则
     */
    protected function validateBuiltinForm(WorkflowDefinition $definition, array $formData): array
    {
        if (!$definition->form_id) {
            return $formData;
        }

        $formDefinition = FormDefinition::find($definition->form_id);
        if (!$formDefinition) {
            return $formData;
        }

        return $formDefinition->validateData($formData);
    }

    /**
     * 获取模型的验证规则
     */
    protected function getModelRules(string $modelClass): array
    {
        if (method_exists($modelClass, 'getWorkflowValidationRules')) {
            return $modelClass::getWorkflowValidationRules();
        }

        return [];
    }

    /**
     * 获取模型的验证消息
     */
    protected function getModelMessages(string $modelClass): array
    {
        if (method_exists($modelClass, 'getWorkflowValidationMessages')) {
            return $modelClass::getWorkflowValidationMessages();
        }

        return [];
    }

    /**
     * 验证任务处理时的表单数据
     *
     * @param WorkflowDefinition $definition 流程定义
     * @param string $nodeId 当前节点ID
     * @param array $formData 表单数据
     * @return array 验证通过的数据
     * @throws ValidationException 验证失败
     */
    public function validateTaskData(WorkflowDefinition $definition, string $nodeId, array $formData): array
    {
        $node = $definition->getNode($nodeId);
        if (!$node) {
            return $formData;
        }

        $fieldPermissions = $node['field_permissions'] ?? [];

        if (empty($fieldPermissions)) {
            return $formData;
        }

        $allowedFields = array_keys(array_filter($fieldPermissions, fn($perm) => $perm['editable'] ?? true));

        if (empty($allowedFields)) {
            return [];
        }

        $rules = [];
        foreach ($allowedFields as $field) {
            $rules[$field] = 'sometimes';
        }

        $validator = Validator::make($formData, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * 获取流程的必填字段
     */
    public function getRequiredFields(WorkflowDefinition $definition): array
    {
        if ($definition->form_type === WorkflowDefinition::FORM_TYPE_BUILTIN) {
            if (!$definition->form_id) {
                return [];
            }

            $formDefinition = FormDefinition::find($definition->form_id);
            if (!$formDefinition) {
                return [];
            }

            return collect($formDefinition->getFields())
                ->filter(fn($field) => $field['required'] ?? false)
                ->pluck('name')
                ->toArray();
        }

        return [];
    }
}
