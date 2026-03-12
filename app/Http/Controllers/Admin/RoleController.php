<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Services\RoleService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected RoleService $roleService
    ) {}

    /**
     * 角色列表
     */
    public function index(Request $request): JsonResponse
    {
        $params = $request->only(['name', 'page', 'per_page']);
        $perPage = (int) ($params['per_page'] ?? 15);

        $roles = $this->roleService->paginate($params, $perPage);
        $roles->load(['menus', 'permissions']);

        return $this->success($roles);
    }

    /**
     * 创建角色
     */
    public function store(RoleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $role = $this->roleService->create($data);

        return $this->created($role->load(['menus', 'permissions']), '角色创建成功');
    }

    /**
     * 角色详情
     */
    public function show(int $id): JsonResponse
    {
        $role = $this->roleService->findOrFail($id);
        $role->load(['menus', 'permissions', 'departments']);

        return $this->success([
            'id' => $role->id,
            'name' => $role->name,
            'data_scope' => $role->data_scope,
            'data_scope_text' => $role->data_scope_text,
            'remark' => $role->remark,
            'menu_ids' => $role->menus->pluck('id'),
            'permission_ids' => $role->permissions->pluck('id'),
            'department_ids' => $role->departments->pluck('id'),
            'created_at' => $role->created_at,
            'updated_at' => $role->updated_at,
        ]);
    }

    /**
     * 更新角色
     */
    public function update(RoleRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $role = $this->roleService->update($id, $data);

        return $this->success($role->load(['menus', 'permissions']), '角色更新成功');
    }

    /**
     * 删除角色
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->roleService->delete($id);
            return $this->noContent('角色删除成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 更新角色菜单权限
     */
    public function updateMenus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'menu_ids' => ['required', 'array'],
            'menu_ids.*' => ['integer', 'exists:menus,id'],
        ]);

        $this->roleService->updateMenus($id, $request->input('menu_ids'));
        return $this->success(null, '菜单权限更新成功');
    }

    /**
     * 更新角色权限
     */
    public function updatePermissions(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'permission_ids' => ['required', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $this->roleService->updatePermissions($id, $request->input('permission_ids'));
        return $this->success(null, '权限更新成功');
    }
}
