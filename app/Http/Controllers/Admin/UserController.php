<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Http\Requests\Admin\ExportRequest;
use App\Models\User;
use App\Services\UserService;
use App\Services\ExportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected UserService $userService,
        protected ExportService $exportService
    ) {}

    /**
     * 用户列表
     */
    public function index(Request $request): JsonResponse
    {
        $params = $request->only([
            'username', 'name', 'phone', 'email',
            'department_id', 'status', 'page', 'per_page'
        ]);

        $perPage = (int) ($params['per_page'] ?? 15);
        $users = $this->userService->paginate($params, $perPage);

        // 加载关联
        $users->load(['department', 'positions', 'roles']);

        return $this->success($users);
    }

    /**
     * 创建用户
     */
    public function store(UserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $this->userService->create($data);

        return $this->created($user->load(['department', 'positions', 'roles']), '用户创建成功');
    }

    /**
     * 用户详情
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->userService->findOrFail($id);
        $user->load(['department', 'positions', 'roles']);

        return $this->success($user);
    }

    /**
     * 更新用户
     */
    public function update(UserRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $user = $this->userService->update($id, $data);

        return $this->success($user->load(['department', 'positions', 'roles']), '用户更新成功');
    }

    /**
     * 删除用户
     */
    public function destroy(int $id): JsonResponse
    {
        // 不允许删除自己
        if ($id === auth()->id()) {
            return $this->error('不能删除当前登录用户');
        }

        $this->userService->delete($id);
        return $this->noContent('用户删除成功');
    }

    /**
     * 更新用户状态
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        // 不允许禁用自己
        if ($id === auth()->id()) {
            return $this->error('不能禁用当前登录用户');
        }

        $request->validate([
            'status' => ['required', 'in:0,1'],
        ]);

        $this->userService->updateStatus($id, (int) $request->input('status'));
        return $this->success(null, '状态更新成功');
    }

    /**
     * 重置密码
     */
    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $this->userService->resetPassword($id, $request->input('password'));
        return $this->success(null, '密码重置成功');
    }

    /**
     * 导出用户列表
     */
    public function export(ExportRequest $request): JsonResponse
    {
        return $this->exportService->export(
            resource: 'users',
            modelClass: User::class,
            request: $request,
            queryBuilder: fn($query) => $this->applyFilters($query, $request)
        );
    }

    /**
     * 获取可导出字段
     */
    public function exportFields(): JsonResponse
    {
        $user = new User();
        return $this->success($user->getExportFieldOptions());
    }

    /**
     * Apply filters to query.
     */
    protected function applyFilters($query, Request $request)
    {
        $filters = $request->input('filters', []);

        if (!empty($filters['username'])) {
            $query->where('username', 'like', "%{$filters['username']}%");
        }
        if (!empty($filters['name'])) {
            $query->where('name', 'like', "%{$filters['name']}%");
        }
        if (!empty($filters['email'])) {
            $query->where('email', 'like', "%{$filters['email']}%");
        }
        if (!empty($filters['phone'])) {
            $query->where('phone', 'like', "%{$filters['phone']}%");
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        return $query->with('department');
    }
}
