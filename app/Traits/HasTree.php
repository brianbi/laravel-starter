<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * 树形结构处理
 */
trait HasTree
{
    /**
     * 获取子级
     */
    public function children()
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    /**
     * 获取父级
     */
    public function parent()
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    /**
     * 获取所有后代（递归）
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * 获取所有祖先
     */
    public function ancestors()
    {
        return $this->parent()->with('ancestors');
    }

    /**
     * 筛选顶级节点
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id')->orWhere('parent_id', 0);
    }

    /**
     * 按排序字段排序
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort')->orderBy('id');
    }

    /**
     * 构建树形结构
     */
    public static function buildTree(?Collection $items = null, int $parentId = 0): array
    {
        if ($items === null) {
            $items = static::query()->ordered()->get();
        }

        $tree = [];
        foreach ($items as $item) {
            if ($item->parent_id == $parentId) {
                $children = static::buildTree($items, $item->id);
                $node = $item->toArray();
                if (!empty($children)) {
                    $node['children'] = $children;
                }
                $tree[] = $node;
            }
        }

        return $tree;
    }

    /**
     * 获取所有子孙ID
     */
    public function getDescendantIds(): array
    {
        $ids = [];
        $children = $this->children()->get();

        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->getDescendantIds());
        }

        return $ids;
    }

    /**
     * 获取祖先ID列表（从根到当前节点）
     */
    public function getAncestorIds(): array
    {
        $ids = [];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($ids, $parent->id);
            $parent = $parent->parent;
        }

        return $ids;
    }

    /**
     * 获取层级深度
     */
    public function getDepth(): int
    {
        return count($this->getAncestorIds());
    }
}
