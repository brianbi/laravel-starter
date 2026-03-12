<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DictionaryRequest;
use App\Http\Requests\Admin\DictionaryItemRequest;
use App\Services\DictionaryService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DictionaryController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected DictionaryService $dictionaryService
    ) {}

    /**
     * 字典列表
     */
    public function index(Request $request): JsonResponse
    {
        $params = $request->only(['name', 'code', 'status', 'page', 'per_page']);
        $perPage = (int) ($params['per_page'] ?? 15);

        $dictionaries = $this->dictionaryService->paginate($params, $perPage);

        return $this->success($dictionaries);
    }

    /**
     * 创建字典
     */
    public function store(DictionaryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $dictionary = $this->dictionaryService->create($data);

        return $this->created($dictionary, '字典创建成功');
    }

    /**
     * 字典详情
     */
    public function show(int $id): JsonResponse
    {
        $dictionary = $this->dictionaryService->findOrFail($id);
        $dictionary->load('items');

        return $this->success($dictionary);
    }

    /**
     * 更新字典
     */
    public function update(DictionaryRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $dictionary = $this->dictionaryService->update($id, $data);

        return $this->success($dictionary, '字典更新成功');
    }

    /**
     * 删除字典
     */
    public function destroy(int $id): JsonResponse
    {
        $this->dictionaryService->delete($id);
        return $this->noContent('字典删除成功');
    }

    /**
     * 获取字典项列表
     */
    public function items(Request $request, int $dictionary): JsonResponse
    {
        $params = $request->only(['label', 'status']);
        $items = $this->dictionaryService->getItems($dictionary, $params);

        return $this->success($items);
    }

    /**
     * 创建字典项
     */
    public function storeItem(DictionaryItemRequest $request, int $dictionary): JsonResponse
    {
        $data = $request->validated();
        $item = $this->dictionaryService->createItem($dictionary, $data);

        return $this->created($item, '字典项创建成功');
    }

    /**
     * 更新字典项
     */
    public function updateItem(DictionaryItemRequest $request, int $dictionary, int $item): JsonResponse
    {
        $data = $request->validated();
        $dictionaryItem = $this->dictionaryService->updateItem($dictionary, $item, $data);

        return $this->success($dictionaryItem, '字典项更新成功');
    }

    /**
     * 删除字典项
     */
    public function destroyItem(int $dictionary, int $item): JsonResponse
    {
        $this->dictionaryService->deleteItem($dictionary, $item);
        return $this->noContent('字典项删除成功');
    }
}
