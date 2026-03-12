<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PositionRequest;
use App\Services\PositionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PositionService $positionService
    ) {}

    /**
     * 岗位列表
     */
    public function index(Request $request): JsonResponse
    {
        $params = $request->only(['name', 'code', 'status', 'page', 'per_page']);
        $perPage = (int) ($params['per_page'] ?? 15);

        $positions = $this->positionService->paginate($params, $perPage);

        return $this->success($positions);
    }

    /**
     * 创建岗位
     */
    public function store(PositionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $position = $this->positionService->create($data);

        return $this->created($position, '岗位创建成功');
    }

    /**
     * 岗位详情
     */
    public function show(int $id): JsonResponse
    {
        $position = $this->positionService->findOrFail($id);
        return $this->success($position);
    }

    /**
     * 更新岗位
     */
    public function update(PositionRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $position = $this->positionService->update($id, $data);

        return $this->success($position, '岗位更新成功');
    }

    /**
     * 删除岗位
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->positionService->delete($id);
            return $this->noContent('岗位删除成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
