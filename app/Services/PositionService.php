<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PositionRepository;

class PositionService extends BaseService
{
    public function __construct(PositionRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * 删除岗位（检查是否有用户）
     */
    public function delete(int $id): bool
    {
        $position = $this->repository->findOrFail($id);

        // 检查是否有用户
        if ($position->users()->count() > 0) {
            throw new \Exception('该岗位下有用户，无法删除');
        }

        return $this->repository->delete($id);
    }

    /**
     * 检查编码是否存在
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        return $this->repository->codeExists($code, $excludeId);
    }
}
