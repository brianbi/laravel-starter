<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DepartmentRequest;
use App\Services\DepartmentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected DepartmentService $departmentService
    ) {}

    /**
     * 部门列表
     */
    public function index(Request $request): JsonResponse
    {
        $params = $request->only(['name', 'status']);
        $departments = $this->departmentService->all($params);

        return $this->success($departments);
    }

    /**
     * 部门树
     */
    public function tree(Request $request): JsonResponse
    {
        $params = $request->only(['status']);
        $tree = $this->departmentService->getTree($params);

        return $this->success($tree);
    }

    /**
     * 创建部门
     */
    public function store(DepartmentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $department = $this->departmentService->create($data);

        return $this->created($department, '部门创建成功');
    }

    /**
     * 部门详情
     */
    public function show(int $id): JsonResponse
    {
        $department = $this->departmentService->findOrFail($id);
        return $this->success($department);
    }

    /**
     * 更新部门
     */
    public function update(DepartmentRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();

        // 不能将部门设置为自己的子部门
        if (isset($data['parent_id']) && $data['parent_id'] == $id) {
            return $this->error('不能将部门设置为自己的子部门');
        }

        $department = $this->departmentService->update($id, $data);
        return $this->success($department, '部门更新成功');
    }

    /**
     * 删除部门
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->departmentService->delete($id);
            return $this->noContent('部门删除成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
