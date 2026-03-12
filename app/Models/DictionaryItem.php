<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DictionaryItem extends Model
{
    use HasStatus;

    protected $fillable = [
        'dictionary_id',
        'label',
        'value',
        'sort',
        'status',
        'remark',
    ];

    protected $casts = [
        'dictionary_id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 所属字典
     */
    public function dictionary(): BelongsTo
    {
        return $this->belongsTo(Dictionary::class);
    }

    /**
     * 模型事件 - 更新时清除字典缓存
     */
    protected static function booted(): void
    {
        static::saved(function (DictionaryItem $item) {
            $item->dictionary?->clearCache();
        });

        static::deleted(function (DictionaryItem $item) {
            $item->dictionary?->clearCache();
        });
    }
}
