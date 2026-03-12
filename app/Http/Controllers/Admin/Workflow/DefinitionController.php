<?php

namespace App\Http\Controllers\Admin\Workflow;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Workflow\DefinitionRequest;
use App\Models\WorkflowDefinition;
use App\Services\Workflow\WorkflowService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DefinitionController extends Controller
{
    use ApiResponse;

    protected WorkflowService $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * 获取流程定义列表
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['keyword', 'status']);
        $perPage = $request->input('per_page', 15);

        $definitions = $this->workflowService->getDefinitions($filters, $perPage);

        return $this->success($definitions);
    }

    /**
     * 创建流程定义
     */
    public function store(DefinitionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $definition = $this->workflowService->createDefinition($data);

        return $this->created($definition, '创建成功');
    }

    /**
     * 获取流程定义详情
     */
    public function show(int $id): JsonResponse
    {
        $definition = WorkflowDefinition::with(['nodes', 'creator'])->findOrFail($id);

        return $this->success($definition);
    }

    /**
     * 更新流程定义
     */
    public function update(DefinitionRequest $request, int $id): JsonResponse
    {
        $definition = WorkflowDefinition::findOrFail($id);
        
        $definition = $this->workflowService->updateDefinition($definition, $request->validated());

        return $this->success($definition, '更新成功');
    }

    /**
     * 删除流程定义
     */
    public function destroy(int $id): JsonResponse
    {
        $definition = WorkflowDefinition::findOrFail($id);

        try {
            $this->workflowService->deleteDefinition($definition);
            return $this->success(null, '删除成功');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 发布流程定义
     */
    public function publish(int $id): JsonResponse
    {
        $definition = WorkflowDefinition::findOrFail($id);
        
        $definition = $this->workflowService->publishDefinition($definition);

        return $this->success($definition, '发布成功');
    }

    /**
     * 获取版本历史
     */
    public function versions(int $id): JsonResponse
    {
        $definition = WorkflowDefinition::findOrFail($id);

        // 简单实现：返回当前定义的版本信息
        return $this->success([
            'current_version' => $definition->version,
            'code' => $definition->code,
            'name' => $definition->name,
        ]);
    }

    /**
     * 获取可用的节点类型
     */
    public function nodeTypes(): JsonResponse
    {
        $types = $this->workflowService->getAvailableNodeTypes();

        return $this->success($types);
    }

    /**
     * 获取启用的流程定义（用于发起流程）
     */
    public function enabled(): JsonResponse
    {
        $definitions = WorkflowDefinition::enabled()
            ->select(['id', 'code', 'name', 'description', 'form_type'])
            ->orderBy('name')
            ->get();

        return $this->success($definitions);
    }
}
