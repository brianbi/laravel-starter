<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Department;
use App\Repositories\DepartmentRepository;

class DepartmentService extends BaseService
{
    public function __construct(DepartmentRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * 获取部门树
     */
    public function getTree(array $params = []): array
    {
        return $this->repository->getTree($params);
    }

    /**
     * 删除部门（检查是否有子部门和用户）
     */
    public function delete(int $id): bool
    {
        $department = $this->repository->findOrFail($id);

        // 检查是否有子部门
        if ($department->children()->count() > 0) {
            throw new \Exception('该部门下有子部门，无法删除');
        }

        // 检查是否有用户
        if ($department->users()->count() > 0) {
            throw new \Exception('该部门下有用户，无法删除');
        }

        return $this->repository->delete($id);
    }
}
