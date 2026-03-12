<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DataScopeEnum;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'data_scope',
        'remark',
    ];

    protected $casts = [
        'data_scope' => 'integer',
    ];

    /**
     * 关联的菜单
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'role_menus');
    }

    /**
     * 关联的部门（用于自定义数据权限）
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'role_departments');
    }

    /**
     * 获取数据权限范围文本
     */
    public function getDataScopeTextAttribute(): string
    {
        return DataScopeEnum::tryFrom($this->data_scope ?? 1)?->label() ?? '未知';
    }

    /**
     * 同步菜单
     */
    public function syncMenus(array $menuIds): void
    {
        $this->menus()->sync($menuIds);
    }

    /**
     * 同步部门（自定义数据权限）
     */
    public function syncDepartments(array $departmentIds): void
    {
        $this->departments()->sync($departmentIds);
    }
}
