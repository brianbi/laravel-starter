<?php

namespace App\Http\Controllers\Admin\Form;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Form\FormDefinitionRequest;
use App\Http\Requests\Admin\Form\FormDataRequest;
use App\Models\FormDefinition;
use App\Services\FormService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormDefinitionController extends Controller
{
    use ApiResponse;

    protected FormService $formService;

    public function __construct(FormService $formService)
    {
        $this->formService = $formService;
    }

    /**
     * 获取表单定义列表
     */
    public function index(Request $request): JsonResponse
    {
        $params = $request->only(['name', 'code', 'status', 'created_by', 'created_at_start', 'created_at_end']);
        $perPage = $request->input('per_page', 15);

        $forms = $this->formService->paginate($params, $perPage);

        return $this->success($forms);
    }

    /**
     * 创建表单定义
     */
    public function store(FormDefinitionRequest $request): JsonResponse
    {
        $form = $this->formService->create($request->validated());

        return $this->created($form, '创建成功');
    }

    /**
     * 获取表单定义详情
     */
    public function show(int $id): JsonResponse
    {
        $form = FormDefinition::with(['creator'])->findOrFail($id);

        return $this->success($form);
    }

    /**
     * 更新表单定义
     */
    public function update(FormDefinitionRequest $request, int $id): JsonResponse
    {
        $form = $this->formService->update($id, $request->validated());

        return $this->success($form, '更新成功');
    }

    /**
     * 删除表单定义
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->formService->delete($id);
            return $this->success(null, '删除成功');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 批量删除表单定义
     */
    public function batchDestroy(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return $this->error('请选择要删除的表单');
        }

        try {
            $count = $this->formService->batchDelete($ids);
            return $this->success(['count' => $count], '删除成功');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 启用表单
     */
    public function enable(int $id): JsonResponse
    {
        $this->formService->enable($id);

        return $this->success(null, '启用成功');
    }

    /**
     * 禁用表单
     */
    public function disable(int $id): JsonResponse
    {
        $this->formService->disable($id);

        return $this->success(null, '禁用成功');
    }

    /**
     * 复制表单
     */
    public function copy(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:100',
        ]);

        try {
            $newForm = $this->formService->copy($id, $request->input('code'), $request->input('name'));
            return $this->success($newForm, '复制成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取字段类型列表
     */
    public function fieldTypes(): JsonResponse
    {
        $types = $this->formService->getFieldTypes();

        return $this->success($types);
    }

    /**
     * 获取启用的表单列表
     */
    public function enabled(): JsonResponse
    {
        $forms = $this->formService->getEnabledForms();

        return $this->success($forms);
    }

    /**
     * 验证表单数据（不保存）
     */
    public function validate(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $this->formService->validateFormData($id, $request->all());
            return $this->success($validated, '验证通过');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), '验证失败');
        }
    }

    /**
     * 获取表单数据统计
     */
    public function statistics(int $id): JsonResponse
    {
        $statistics = $this->formService->getFormDataStatistics($id);

        return $this->success($statistics);
    }
}
