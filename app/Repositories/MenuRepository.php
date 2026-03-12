<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Builder;

class MenuRepository extends BaseRepository
{
    public function __construct(Menu $model)
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

        // 状态筛选
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', $params['status']);
        }

        // 类型筛选
        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }

        // 排序
        $query->orderBy('sort')->orderBy('id');

        return $query;
    }

    /**
     * 获取树形结构
     */
    public function getTree(array $params = []): array
    {
        $query = $this->buildQuery($params);
        $items = $query->get();

        return Menu::buildTree($items);
    }
}
