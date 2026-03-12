<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

abstract class BaseService
{
    protected BaseRepository $repository;

    public function __construct(BaseRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 获取分页列表
     */
    public function paginate(array $params = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($params, $perPage);
    }

    /**
     * 获取所有记录
     */
    public function all(array $params = []): Collection
    {
        return $this->repository->all($params);
    }

    /**
     * 根据ID获取记录
     */
    public function find(int $id): ?Model
    {
        return $this->repository->find($id);
    }

    /**
     * 根据ID获取记录，不存在则抛出异常
     */
    public function findOrFail(int $id): Model
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * 创建记录
     */
    public function create(array $data): Model
    {
        return $this->repository->create($data);
    }

    /**
     * 更新记录
     */
    public function update(int $id, array $data): Model
    {
        return $this->repository->update($id, $data);
    }

    /**
     * 删除记录
     */
    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * 批量删除
     */
    public function batchDelete(array $ids): int
    {
        return $this->repository->batchDelete($ids);
    }

    /**
     * 更新状态
     */
    public function updateStatus(int $id, int $status): bool
    {
        return $this->repository->updateStatus($id, $status);
    }
}
