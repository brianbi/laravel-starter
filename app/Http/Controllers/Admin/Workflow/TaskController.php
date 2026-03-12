<?php

namespace App\Http\Controllers\Admin\Workflow;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Workflow\TaskActionRequest;
use App\Models\WorkflowTask;
use App\Services\Workflow\WorkflowService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    use ApiResponse;

    protected WorkflowService $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * 获取我的待办任务
     */
    public function pending(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $tasks = $this->workflowService->getMyPendingTasks(
            $request->user()->id,
            $perPage
        );

        return $this->success($tasks);
    }

    /**
     * 获取我的已办任务
     */
    public function done(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $tasks = $this->workflowService->getMyCompletedTasks(
            $request->user()->id,
            $perPage
        );

        return $this->success($tasks);
    }

    /**
     * 获取待办任务数量
     */
    public function count(Request $request): JsonResponse
    {
        $count = $this->workflowService->getPendingTaskCount($request->user()->id);

        return $this->success(['count' => $count]);
    }

    /**
     * 获取任务详情
     */
    public function show(int $id): JsonResponse
    {
        $task = $this->workflowService->getTask($id);

        $fieldPermissions = $this->workflowService->getTaskFieldPermissions($task);

        return $this->success([
            'id' => $task->id,
            'instance' => [
                'id' => $task->instance->id,
                'definition' => [
                    'id' => $task->instance->definition->id,
                    'code' => $task->instance->definition->code,
                    'name' => $task->instance->definition->name,
                ],
                'initiator' => $task->instance->initiator ? [
                    'id' => $task->instance->initiator->id,
                    'name' => $task->instance->initiator->name,
                ] : null,
                'form_data' => $task->instance->form_data,
                'status' => $task->instance->status,
                'status_text' => $task->instance->status_text,
            ],
            'node_id' => $task->node_id,
            'node_name' => $task->node_name,
            'node_type' => $task->node_type,
            'status' => $task->status,
            'status_text' => $task->status_text,
            'delegate_from' => $task->delegate_from,
            'timeout_at' => $task->timeout_at?->format('Y-m-d H:i:s'),
            'field_permissions' => $fieldPermissions,
            'created_at' => $task->created_at->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 通过
     */
    public function approve(TaskActionRequest $request, int $id): JsonResponse
    {
        $task = WorkflowTask::findOrFail($id);

        $this->checkTaskAssignee($task, $request->user()->id);

        try {
            $this->workflowService->approveTask(
                $task,
                $request->input('comment'),
                $request->input('form_data')
            );

            return $this->success(null, '审批通过');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 拒绝
     */
    public function reject(TaskActionRequest $request, int $id): JsonResponse
    {
        $task = WorkflowTask::findOrFail($id);

        $this->checkTaskAssignee($task, $request->user()->id);

        try {
            $this->workflowService->rejectTask($task, $request->input('comment'));

            return $this->success(null, '已拒绝');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 退回
     */
    public function return(TaskActionRequest $request, int $id): JsonResponse
    {
        $task = WorkflowTask::findOrFail($id);

        $this->checkTaskAssignee($task, $request->user()->id);

        try {
            $this->workflowService->returnTask(
                $task,
                $request->input('target_node'),
                $request->input('comment')
            );

            return $this->success(null, '已退回');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 转办
     */
    public function delegate(TaskActionRequest $request, int $id): JsonResponse
    {
        $task = WorkflowTask::findOrFail($id);

        $this->checkTaskAssignee($task, $request->user()->id);

        try {
            $this->workflowService->delegateTask(
                $task,
                $request->input('target_user'),
                $request->input('comment')
            );

            return $this->success(null, '已转办');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 加签
     */
    public function addSign(TaskActionRequest $request, int $id): JsonResponse
    {
        $task = WorkflowTask::findOrFail($id);

        $this->checkTaskAssignee($task, $request->user()->id);

        try {
            $this->workflowService->addSignTask(
                $task,
                $request->input('target_users'),
                $request->input('sign_type', 'after'),
                $request->input('comment')
            );

            return $this->success(null, '已加签');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取工作流统计
     */
    public function statistics(Request $request): JsonResponse
    {
        $stats = $this->workflowService->getStatistics($request->user()->id);

        return $this->success($stats);
    }

    /**
     * 检查任务指派人
     */
    protected function checkTaskAssignee(WorkflowTask $task, int $userId): void
    {
        if ($task->assignee_id !== $userId) {
            abort(403, '您没有权限处理此任务');
        }

        if (!$task->isPending()) {
            abort(400, '任务已处理');
        }
    }
}
