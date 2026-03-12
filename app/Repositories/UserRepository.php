<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * 构建查询条件
     */
    protected function buildQuery(array $params): Builder
    {
        $query = parent::buildQuery($params);

        // 用户名搜索
        if (!empty($params['username'])) {
            $query->where('username', 'like', "%{$params['username']}%");
        }

        // 姓名搜索
        if (!empty($params['name'])) {
            $query->where('name', 'like', "%{$params['name']}%");
        }

        // 手机号搜索
        if (!empty($params['phone'])) {
            $query->where('phone', 'like', "%{$params['phone']}%");
        }

        // 邮箱搜索
        if (!empty($params['email'])) {
            $query->where('email', 'like', "%{$params['email']}%");
        }

        // 部门筛选
        if (!empty($params['department_id'])) {
            $query->where('department_id', $params['department_id']);
        }

        // 包含子部门
        if (!empty($params['department_ids'])) {
            $query->whereIn('department_id', $params['department_ids']);
        }

        return $query;
    }

    /**
     * 检查用户名是否存在
     */
    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $query = $this->model->where('username', $username);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    /**
     * 检查邮箱是否存在
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = $this->model->where('email', $email);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }
}
