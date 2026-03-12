<?php

namespace App\Http\Controllers\Admin\Workflow;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Workflow\DelegateRequest;
use App\Models\WorkflowDelegate;
use App\Services\Workflow\DelegateService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DelegateController extends Controller
{
    use ApiResponse;

    protected DelegateService $delegateService;

    public function __construct(DelegateService $delegateService)
    {
        $this->delegateService = $delegateService;
    }

    /**
     * 获取我的代理配置列表
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $delegates = $this->delegateService->getUserDelegates(
            $request->user()->id,
            $perPage
        );

        return $this->success($delegates);
    }

    /**
     * 获取我作为代理人的配置列表
     */
    public function asDelegate(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $delegates = $this->delegateService->getAsDelegate(
            $request->user()->id,
            $perPage
        );

        return $this->success($delegates);
    }

    /**
     * 创建代理配置
     */
    public function store(DelegateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        try {
            $delegate = $this->delegateService->create($data);
            return $this->success($delegate, '创建成功', 201);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取代理配置详情
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $delegate = WorkflowDelegate::with(['delegate', 'definition'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return $this->success($delegate);
    }

    /**
     * 更新代理配置
     */
    public function update(DelegateRequest $request, int $id): JsonResponse
    {
        $delegate = WorkflowDelegate::where('user_id', $request->user()->id)
            ->findOrFail($id);

        try {
            $delegate = $this->delegateService->update($delegate, $request->validated());
            return $this->success($delegate, '更新成功');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 删除代理配置
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $delegate = WorkflowDelegate::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $this->delegateService->delete($delegate);

        return $this->success(null, '删除成功');
    }

    /**
     * 启用代理配置
     */
    public function enable(int $id, Request $request): JsonResponse
    {
        $delegate = WorkflowDelegate::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $this->delegateService->enable($delegate);

        return $this->success(null, '已启用');
    }

    /**
     * 禁用代理配置
     */
    public function disable(int $id, Request $request): JsonResponse
    {
        $delegate = WorkflowDelegate::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $this->delegateService->disable($delegate);

        return $this->success(null, '已禁用');
    }
}
