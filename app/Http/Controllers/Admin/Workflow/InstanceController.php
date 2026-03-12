<?php

namespace App\Http\Controllers\Admin\Workflow;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Workflow\InstanceRequest;
use App\Models\WorkflowInstance;
use App\Services\Workflow\WorkflowService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstanceController extends Controller
{
    use ApiResponse;

    protected WorkflowService $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * 获取我发起的流程列表
     */
    public function initiated(Request $request): JsonResponse
    {
        $filters = $request->only(['status']);
        $perPage = $request->input('per_page', 15);

        $instances = $this->workflowService->getMyInitiatedInstances(
            $request->user()->id,
            $filters,
            $perPage
        );

        return $this->success($instances);
    }

    /**
     * 发起流程
     */
    public function store(InstanceRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $instance = $this->workflowService->startWorkflow(
                $data['definition_code'],
                $data['form_data'] ?? [],
                $request->user()->id,
                $data['business_type'] ?? null,
                $data['business_id'] ?? null
            );

            return $this->created([
                'id' => $instance->id,
                'status' => $instance->status,
                'status_text' => $instance->status_text,
            ], '流程发起成功');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取流程详情
     */
    public function show(int $id): JsonResponse
    {
        $instance = $this->workflowService->getInstance($id);

        return $this->success([
            'id' => $instance->id,
            'definition' => [
                'id' => $instance->definition->id,
                'code' => $instance->definition->code,
                'name' => $instance->definition->name,
            ],
            'initiator' => $instance->initiator ? [
                'id' => $instance->initiator->id,
                'name' => $instance->initiator->name,
            ] : null,
            'form_data' => $instance->form_data,
            'current_node_id' => $instance->current_node_id,
            'status' => $instance->status,
            'status_text' => $instance->status_text,
            'started_at' => $instance->started_at?->format('Y-m-d H:i:s'),
            'completed_at' => $instance->completed_at?->format('Y-m-d H:i:s'),
            'created_at' => $instance->created_at->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 获取流程时间线
     */
    public function timeline(int $id): JsonResponse
    {
        $instance = WorkflowInstance::findOrFail($id);
        
        $timeline = $this->workflowService->getInstanceTimeline($instance);

        return $this->success($timeline);
    }

    /**
     * 撤回流程
     */
    public function withdraw(Request $request, int $id): JsonResponse
    {
        $instance = WorkflowInstance::findOrFail($id);

        try {
            $this->workflowService->withdrawInstance($instance, $request->user()->id);
            return $this->success(null, '撤回成功');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取业务单据的审批状态
     */
    public function businessStatus(Request $request): JsonResponse
    {
        $businessType = $request->input('business_type');
        $businessId = $request->input('business_id');

        if (!$businessType || !$businessId) {
            return $this->error('业务类型和业务ID不能为空');
        }

        $status = $this->workflowService->getBusinessApprovalStatus($businessType, $businessId);

        return $this->success($status);
    }
}
