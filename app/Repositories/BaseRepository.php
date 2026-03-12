<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

abstract class BaseRepository
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * 获取查询构建器
     */
    public function query(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * 获取分页列表
     */
    public function paginate(array $params = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->buildQuery($params);
        return $query->paginate($perPage);
    }

    /**
     * 获取所有记录
     */
    public function all(array $params = []): Collection
    {
        $query = $this->buildQuery($params);
        return $query->get();
    }

    /**
     * 根据ID获取记录
     */
    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * 根据ID获取记录，不存在则抛出异常
     */
    public function findOrFail(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /**
     * 创建记录
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * 更新记录
     */
    public function update(int $id, array $data): Model
    {
        $model = $this->findOrFail($id);
        $model->update($data);
        return $model->fresh();
    }

    /**
     * 删除记录
     */
    public function delete(int $id): bool
    {
        $model = $this->findOrFail($id);
        return $model->delete();
    }

    /**
     * 批量删除
     */
    public function batchDelete(array $ids): int
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    /**
     * 更新状态
     */
    public function updateStatus(int $id, int $status): bool
    {
        return $this->model->where('id', $id)->update(['status' => $status]) > 0;
    }

    /**
     * 根据条件检查是否存在
     */
    public function exists(array $conditions): bool
    {
        return $this->model->where($conditions)->exists();
    }

    /**
     * 构建查询条件
     * 子类可重写此方法实现自定义查询逻辑
     */
    protected function buildQuery(array $params): Builder
    {
        $query = $this->query();

        // 通用状态筛选
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', $params['status']);
        }

        // 通用时间范围筛选
        if (!empty($params['created_at_start'])) {
            $query->where('created_at', '>=', $params['created_at_start']);
        }
        if (!empty($params['created_at_end'])) {
            $query->where('created_at', '<=', $params['created_at_end']);
        }

        // 默认按ID倒序
        if (!isset($params['order_by'])) {
            $query->orderByDesc('id');
        }

        return $query;
    }
}
