<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Menu;
use App\Repositories\MenuRepository;
use Illuminate\Database\Eloquent\Model;

class MenuService extends BaseService
{
    public function __construct(MenuRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * 获取菜单树
     */
    public function getTree(array $params = []): array
    {
        return $this->repository->getTree($params);
    }

    /**
     * 创建菜单
     */
    public function create(array $data): Model
    {
        // 如果是按钮类型，清除路由相关字段
        if (($data['type'] ?? 'M') === 'B') {
            $data['route'] = null;
            $data['component'] = null;
            $data['redirect'] = null;
            $data['icon'] = null;
        }

        return $this->repository->create($data);
    }

    /**
     * 删除菜单（检查是否有子菜单）
     */
    public function delete(int $id): bool
    {
        $menu = $this->repository->findOrFail($id);

        // 检查是否有子菜单
        if ($menu->children()->count() > 0) {
            throw new \Exception('该菜单下有子菜单，无法删除');
        }

        return $this->repository->delete($id);
    }
}
