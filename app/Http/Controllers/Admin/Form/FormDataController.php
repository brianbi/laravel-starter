<?php

namespace App\Http\Controllers\Admin\Form;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Form\FormDataRequest;
use App\Models\FormData;
use App\Services\FormService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormDataController extends Controller
{
    use ApiResponse;

    protected FormService $formService;

    public function __construct(FormService $formService)
    {
        $this->formService = $formService;
    }

    /**
     * 获取表单数据列表
     */
    public function index(Request $request, int $formId): JsonResponse
    {
        $params = $request->only(['created_by', 'instance_id', 'created_at_start', 'created_at_end']);
        $perPage = $request->input('per_page', 15);

        $data = $this->formService->getFormDataList($formId, $params, $perPage);

        return $this->success($data);
    }

    /**
     * 提交表单数据
     */
    public function store(FormDataRequest $request, int $formId): JsonResponse
    {
        try {
            $formData = $this->formService->submitFormData(
                $formId,
                $request->input('data', []),
                $request->input('instance_id')
            );

            return $this->created($formData, '提交成功');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), '数据验证失败');
        }
    }

    /**
     * 获取表单数据详情
     */
    public function show(int $formId, int $id): JsonResponse
    {
        $formData = $this->formService->getFormData($id);

        // 验证数据属于该表单
        if ($formData->form_id !== $formId) {
            return $this->notFound('数据不属于该表单');
        }

        return $this->success($formData);
    }

    /**
     * 更新表单数据
     */
    public function update(FormDataRequest $request, int $formId, int $id): JsonResponse
    {
        $formData = FormData::findOrFail($id);

        // 验证数据属于该表单
        if ($formData->form_id !== $formId) {
            return $this->notFound('数据不属于该表单');
        }

        try {
            $formData = $this->formService->updateFormData($id, $request->input('data', []));
            return $this->success($formData, '更新成功');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), '数据验证失败');
        }
    }

    /**
     * 删除表单数据
     */
    public function destroy(int $formId, int $id): JsonResponse
    {
        $formData = FormData::findOrFail($id);

        // 验证数据属于该表单
        if ($formData->form_id !== $formId) {
            return $this->notFound('数据不属于该表单');
        }

        // 检查是否关联流程实例
        if ($formData->instance_id) {
            return $this->error('该数据已关联流程实例，无法删除');
        }

        $this->formService->deleteFormData($id);

        return $this->success(null, '删除成功');
    }

    /**
     * 根据流程实例获取表单数据
     */
    public function byInstance(int $instanceId): JsonResponse
    {
        $formData = $this->formService->getFormDataByInstance($instanceId);

        if (!$formData) {
            return $this->notFound('未找到表单数据');
        }

        return $this->success($formData->load(['form', 'creator']));
    }
}
