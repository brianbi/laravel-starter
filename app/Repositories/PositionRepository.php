<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;

class PositionRepository extends BaseRepository
{
    public function __construct(Position $model)
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

        // 编码搜索
        if (!empty($params['code'])) {
            $query->where('code', 'like', "%{$params['code']}%");
        }

        // 状态筛选
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', $params['status']);
        }

        // 排序
        $query->orderBy('sort')->orderBy('id');

        return $query;
    }

    /**
     * 检查编码是否存在
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = $this->model->where('code', $code);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }
}
