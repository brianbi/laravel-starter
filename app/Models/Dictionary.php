<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Dictionary extends Model
{
    use HasStatus;

    protected $fillable = [
        'name',
        'code',
        'status',
        'remark',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    /**
     * 字典项
     */
    public function items(): HasMany
    {
        return $this->hasMany(DictionaryItem::class)->orderBy('sort')->orderBy('id');
    }

    /**
     * 启用的字典项
     */
    public function enabledItems(): HasMany
    {
        return $this->items()->where('status', self::STATUS_ENABLED);
    }

    /**
     * 根据编码获取字典项列表
     */
    public static function getItemsByCode(string $code): array
    {
        $cacheKey = "dictionary:{$code}";

        return Cache::remember($cacheKey, 3600, function () use ($code) {
            $dictionary = static::where('code', $code)
                ->where('status', self::STATUS_ENABLED)
                ->first();

            if (!$dictionary) {
                return [];
            }

            return $dictionary->enabledItems()
                ->get(['label', 'value'])
                ->toArray();
        });
    }

    /**
     * 根据编码和值获取标签
     */
    public static function getLabel(string $code, string $value): ?string
    {
        $items = static::getItemsByCode($code);

        foreach ($items as $item) {
            if ($item['value'] === $value) {
                return $item['label'];
            }
        }

        return null;
    }

    /**
     * 清除字典缓存
     */
    public function clearCache(): void
    {
        Cache::forget("dictionary:{$this->code}");
    }

    /**
     * 模型事件
     */
    protected static function booted(): void
    {
        static::saved(function (Dictionary $dictionary) {
            $dictionary->clearCache();
        });

        static::deleted(function (Dictionary $dictionary) {
            $dictionary->clearCache();
        });
    }
}
