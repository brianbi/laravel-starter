<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MenuRequest;
use App\Services\MenuService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected MenuService $menuService
    ) {}

    /**
     * 菜单列表
     */
    public function index(Request $request): JsonResponse
    {
        $params = $request->only(['name', 'status', 'type']);
        $menus = $this->menuService->all($params);

        return $this->success($menus);
    }

    /**
     * 菜单树
     */
    public function tree(Request $request): JsonResponse
    {
        $params = $request->only(['status']);
        $tree = $this->menuService->getTree($params);

        return $this->success($tree);
    }

    /**
     * 创建菜单
     */
    public function store(MenuRequest $request): JsonResponse
    {
        $data = $request->validated();
        $menu = $this->menuService->create($data);

        return $this->created($menu, '菜单创建成功');
    }

    /**
     * 菜单详情
     */
    public function show(int $id): JsonResponse
    {
        $menu = $this->menuService->findOrFail($id);
        return $this->success($menu);
    }

    /**
     * 更新菜单
     */
    public function update(MenuRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $menu = $this->menuService->update($id, $data);

        return $this->success($menu, '菜单更新成功');
    }

    /**
     * 删除菜单
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->menuService->delete($id);
            return $this->noContent('菜单删除成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
