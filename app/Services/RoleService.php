<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use App\Repositories\RoleRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RoleService extends BaseService
{
    public function __construct(RoleRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * 创建角色
     */
    public function create(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $role = $this->repository->create([
                'name' => $data['name'],
                'guard_name' => $data['guard_name'] ?? 'api',
                'data_scope' => $data['data_scope'] ?? 1,
                'remark' => $data['remark'] ?? null,
            ]);

            // 同步菜单
            if (!empty($data['menu_ids'])) {
                $role->syncMenus($data['menu_ids']);
            }

            // 同步部门（自定义数据权限）
            if (!empty($data['department_ids'])) {
                $role->syncDepartments($data['department_ids']);
            }

            return $role;
        });
    }

    /**
     * 更新角色
     */
    public function update(int $id, array $data): Model
    {
        return DB::transaction(function () use ($id, $data) {
            $role = $this->repository->update($id, [
                'name' => $data['name'] ?? null,
                'data_scope' => $data['data_scope'] ?? null,
                'remark' => $data['remark'] ?? null,
            ]);

            // 同步菜单
            if (isset($data['menu_ids'])) {
                $role->syncMenus($data['menu_ids']);
            }

            // 同步部门
            if (isset($data['department_ids'])) {
                $role->syncDepartments($data['department_ids']);
            }

            return $role;
        });
    }

    /**
     * 更新角色菜单权限
     */
    public function updateMenus(int $id, array $menuIds): void
    {
        $role = $this->repository->findOrFail($id);
        $role->syncMenus($menuIds);
    }

    /**
     * 更新角色权限
     */
    public function updatePermissions(int $id, array $permissionIds): void
    {
        $role = $this->repository->findOrFail($id);
        $role->syncPermissions($permissionIds);
    }

    /**
     * 删除角色
     */
    public function delete(int $id): bool
    {
        $role = $this->repository->findOrFail($id);

        // 检查是否有用户使用此角色
        if ($role->users()->count() > 0) {
            throw new \Exception('该角色下有用户，无法删除');
        }

        return $this->repository->delete($id);
    }
}
