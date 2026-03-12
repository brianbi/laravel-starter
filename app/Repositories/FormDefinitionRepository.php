<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\FormDefinition;
use Illuminate\Database\Eloquent\Builder;

class FormDefinitionRepository extends BaseRepository
{
    public function __construct(FormDefinition $model)
    {
        parent::__construct($model);
    }

    /**
     * 根据编码查找
     */
    public function findByCode(string $code): ?FormDefinition
    {
        return $this->model->where('code', $code)->first();
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

    /**
     * 构建查询条件
     */
    protected function buildQuery(array $params): Builder
    {
        $query = parent::buildQuery($params);

        // 名称搜索
        if (!empty($params['name'])) {
            $query->where('name', 'like', '%' . $params['name'] . '%');
        }

        // 编码搜索
        if (!empty($params['code'])) {
            $query->where('code', 'like', '%' . $params['code'] . '%');
        }

        // 创建人筛选
        if (!empty($params['created_by'])) {
            $query->where('created_by', $params['created_by']);
        }

        return $query;
    }
}
