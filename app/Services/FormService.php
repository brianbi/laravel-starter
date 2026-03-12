<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FormData;
use App\Models\FormDefinition;
use App\Repositories\FormDefinitionRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FormService extends BaseService
{
    public function __construct(FormDefinitionRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * 获取表单定义分页列表
     */
    public function paginate(array $params = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($params, $perPage);
    }

    /**
     * 创建表单定义
     */
    public function create(array $data): Model
    {
        // 检查编码唯一性
        $this->validateUniqueCode($data['code']);

        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? FormDefinition::STATUS_DISABLED;

        return $this->repository->create($data);
    }

    /**
     * 更新表单定义
     */
    public function update(int $id, array $data): Model
    {
        // 检查编码唯一性
        if (isset($data['code'])) {
            $this->validateUniqueCode($data['code'], $id);
        }

        return $this->repository->update($id, $data);
    }

    /**
     * 删除表单定义
     */
    public function delete(int $id): bool
    {
        $form = $this->findOrFail($id);

        // 检查是否有关联的表单数据
        if ($form->formData()->exists()) {
            throw new \RuntimeException('该表单已有数据提交，无法删除');
        }

        return $this->repository->delete($id);
    }

    /**
     * 批量删除
     */
    public function batchDelete(array $ids): int
    {
        // 检查是否有关联数据
        $hasData = FormData::whereIn('form_id', $ids)->exists();
        if ($hasData) {
            throw new \RuntimeException('部分表单已有数据提交，无法删除');
        }

        return $this->repository->batchDelete($ids);
    }

    /**
     * 启用表单
     */
    public function enable(int $id): bool
    {
        return $this->repository->updateStatus($id, FormDefinition::STATUS_ENABLED);
    }

    /**
     * 禁用表单
     */
    public function disable(int $id): bool
    {
        return $this->repository->updateStatus($id, FormDefinition::STATUS_DISABLED);
    }

    /**
     * 根据编码获取表单定义
     */
    public function findByCode(string $code): ?FormDefinition
    {
        /** @var FormDefinitionRepository $repository */
        $repository = $this->repository;
        return $repository->findByCode($code);
    }

    /**
     * 获取启用的表单列表
     */
    public function getEnabledForms(): Collection
    {
        return $this->repository->all(['status' => FormDefinition::STATUS_ENABLED]);
    }

    /**
     * 复制表单定义
     */
    public function copy(int $id, string $newCode, string $newName): FormDefinition
    {
        $this->validateUniqueCode($newCode);

        $form = $this->findOrFail($id);

        $newForm = $form->replicate();
        $newForm->code = $newCode;
        $newForm->name = $newName;
        $newForm->status = FormDefinition::STATUS_DISABLED;
        $newForm->created_by = Auth::id();
        $newForm->save();

        return $newForm;
    }

    /**
     * 获取字段类型列表
     */
    public function getFieldTypes(): array
    {
        return FormDefinition::getFieldTypes();
    }

    /**
     * 提交表单数据
     */
    public function submitFormData(int $formId, array $data, ?int $instanceId = null): FormData
    {
        $form = $this->findOrFail($formId);

        if (!$form->isEnabled()) {
            throw new \RuntimeException('该表单未启用');
        }

        // 验证数据
        $validatedData = $form->validateData($data);

        return DB::transaction(function () use ($form, $validatedData, $instanceId) {
            return FormData::create([
                'form_id' => $form->id,
                'instance_id' => $instanceId,
                'data' => $validatedData,
                'created_by' => Auth::id(),
            ]);
        });
    }

    /**
     * 更新表单数据
     */
    public function updateFormData(int $dataId, array $data): FormData
    {
        $formData = FormData::findOrFail($dataId);
        $form = $formData->form;

        // 验证数据
        $validatedData = $form->validateData($data);

        $formData->data = $validatedData;
        $formData->save();

        return $formData;
    }

    /**
     * 获取表单数据
     */
    public function getFormData(int $dataId): FormData
    {
        return FormData::with(['form', 'creator'])->findOrFail($dataId);
    }

    /**
     * 获取表单数据列表
     */
    public function getFormDataList(int $formId, array $params = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = FormData::with(['creator'])
            ->where('form_id', $formId)
            ->orderByDesc('id');

        // 创建人筛选
        if (!empty($params['created_by'])) {
            $query->where('created_by', $params['created_by']);
        }

        // 实例ID筛选
        if (!empty($params['instance_id'])) {
            $query->where('instance_id', $params['instance_id']);
        }

        // 时间范围筛选
        if (!empty($params['created_at_start'])) {
            $query->where('created_at', '>=', $params['created_at_start']);
        }
        if (!empty($params['created_at_end'])) {
            $query->where('created_at', '<=', $params['created_at_end']);
        }

        return $query->paginate($perPage);
    }

    /**
     * 删除表单数据
     */
    public function deleteFormData(int $dataId): bool
    {
        return FormData::findOrFail($dataId)->delete();
    }

    /**
     * 根据流程实例获取表单数据
     */
    public function getFormDataByInstance(int $instanceId): ?FormData
    {
        return FormData::where('instance_id', $instanceId)->first();
    }

    /**
     * 验证表单数据（不保存）
     */
    public function validateFormData(int $formId, array $data): array
    {
        $form = $this->findOrFail($formId);
        return $form->validateData($data);
    }

    /**
     * 获取表单数据统计
     */
    public function getFormDataStatistics(int $formId): array
    {
        return [
            'total' => FormData::where('form_id', $formId)->count(),
            'today' => FormData::where('form_id', $formId)
                ->whereDate('created_at', today())
                ->count(),
            'this_week' => FormData::where('form_id', $formId)
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
            'this_month' => FormData::where('form_id', $formId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];
    }

    /**
     * 验证编码唯一性
     */
    protected function validateUniqueCode(string $code, ?int $excludeId = null): void
    {
        /** @var FormDefinitionRepository $repository */
        $repository = $this->repository;
        
        if ($repository->codeExists($code, $excludeId)) {
            throw ValidationException::withMessages([
                'code' => ['表单编码已存在'],
            ]);
        }
    }
}
