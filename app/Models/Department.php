<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasStatus;
use App\Traits\HasTree;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes, HasStatus, HasTree;

    protected $fillable = [
        'parent_id',
        'name',
        'leader',
        'phone',
        'email',
        'sort',
        'status',
        'level',
        'path',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'level' => 'integer',
    ];

    /**
     * 部门下的用户
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * 创建时自动更新层级和路径
     */
    protected static function booted(): void
    {
        static::creating(function (Department $department) {
            $department->updateLevelAndPath();
        });

        static::updating(function (Department $department) {
            if ($department->isDirty('parent_id')) {
                $department->updateLevelAndPath();
            }
        });
    }

    /**
     * 更新层级和路径
     */
    public function updateLevelAndPath(): void
    {
        if ($this->parent_id == 0) {
            $this->level = 1;
            $this->path = '0';
        } else {
            $parent = static::find($this->parent_id);
            if ($parent) {
                $this->level = $parent->level + 1;
                $this->path = $parent->path . ',' . $parent->id;
            }
        }
    }

    /**
     * 获取所有下级部门ID（基于path字段，高效查询）
     */
    public function getChildDepartmentIds(): array
    {
        return static::where('path', 'like', $this->path . ',' . $this->id . '%')
            ->pluck('id')
            ->toArray();
    }

    /**
     * 获取本部门及所有下级部门ID
     */
    public function getSelfAndChildDepartmentIds(): array
    {
        return array_merge([$this->id], $this->getChildDepartmentIds());
    }
}
