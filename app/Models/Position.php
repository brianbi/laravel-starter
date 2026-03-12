<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use SoftDeletes, HasStatus;

    protected $fillable = [
        'name',
        'code',
        'sort',
        'status',
        'remark',
    ];

    protected $casts = [
        'sort' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 岗位下的用户
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_positions');
    }
}
