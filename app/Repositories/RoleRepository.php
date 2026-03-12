<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;

class RoleRepository extends BaseRepository
{
    public function __construct(Role $model)
    {
        parent::__construct($model);
    }

    /**
     * 构建查询条件
     */
    protected function buildQuery(array $params): Builder
    {
        $query = $this->query();

        // 名称搜索
        if (!empty($params['name'])) {
            $query->where('name', 'like', "%{$params['name']}%");
        }

        // 默认按ID排序
        $query->orderBy('id');

        return $query;
    }
}
