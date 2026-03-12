<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Dictionary;
use App\Models\DictionaryItem;
use App\Repositories\DictionaryRepository;

class DictionaryService extends BaseService
{
    public function __construct(DictionaryRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * 检查编码是否存在
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        return $this->repository->codeExists($code, $excludeId);
    }

    /**
     * 删除字典（检查是否有字典项）
     */
    public function delete(int $id): bool
    {
        $dictionary = $this->repository->findOrFail($id);

        // 级联删除会自动删除字典项，这里不需要额外检查
        return $this->repository->delete($id);
    }

    /**
     * 获取字典项列表
     */
    public function getItems(int $dictionaryId, array $params = []): mixed
    {
        $query = DictionaryItem::where('dictionary_id', $dictionaryId);

        if (!empty($params['label'])) {
            $query->where('label', 'like', "%{$params['label']}%");
        }

        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', $params['status']);
        }

        return $query->orderBy('sort')->orderBy('id')->get();
    }

    /**
     * 创建字典项
     */
    public function createItem(int $dictionaryId, array $data): DictionaryItem
    {
        $data['dictionary_id'] = $dictionaryId;
        return DictionaryItem::create($data);
    }

    /**
     * 更新字典项
     */
    public function updateItem(int $dictionaryId, int $itemId, array $data): DictionaryItem
    {
        $item = DictionaryItem::where('dictionary_id', $dictionaryId)
            ->where('id', $itemId)
            ->firstOrFail();

        $item->update($data);
        return $item->fresh();
    }

    /**
     * 删除字典项
     */
    public function deleteItem(int $dictionaryId, int $itemId): bool
    {
        return DictionaryItem::where('dictionary_id', $dictionaryId)
            ->where('id', $itemId)
            ->delete() > 0;
    }

    /**
     * 根据编码获取字典项
     */
    public function getItemsByCode(string $code): array
    {
        return Dictionary::getItemsByCode($code);
    }
}
