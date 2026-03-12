<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MenuTypeEnum;
use App\Traits\HasStatus;
use App\Traits\HasTree;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Menu extends Model
{
    use HasStatus, HasTree;

    protected $fillable = [
        'parent_id',
        'name',
        'code',
        'type',
        'icon',
        'route',
        'component',
        'redirect',
        'permission',
        'sort',
        'status',
        'is_hidden',
        'is_cache',
        'remark',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'is_hidden' => 'integer',
        'is_cache' => 'integer',
    ];

    /**
     * 关联的角色
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_menus');
    }

    /**
     * 获取类型文本
     */
    public function getTypeTextAttribute(): string
    {
        return MenuTypeEnum::tryFrom($this->type)?->label() ?? '未知';
    }

    /**
     * 是否为菜单
     */
    public function isMenu(): bool
    {
        return $this->type === MenuTypeEnum::MENU->value;
    }

    /**
     * 是否为按钮
     */
    public function isButton(): bool
    {
        return $this->type === MenuTypeEnum::BUTTON->value;
    }

    /**
     * 是否为外链
     */
    public function isLink(): bool
    {
        return $this->type === MenuTypeEnum::LINK->value;
    }
}
