<?php

namespace App\Models;

use App\Traits\HasCreator;
use App\Traits\HasStatus;
use App\Traits\Exportable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles, HasCreator, HasStatus, Exportable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'name',
        'email',
        'phone',
        'avatar',
        'password',
        'department_id',
        'status',
        'login_ip',
        'login_at',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'login_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'integer',
        ];
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    /**
     * 所属部门
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * 岗位（多对多）
     */
    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'user_positions');
    }

    /**
     * 更新登录信息
     */
    public function updateLoginInfo(string $ip): void
    {
        $this->update([
            'login_ip' => $ip,
            'login_at' => now(),
        ]);
    }

    /**
     * 是否为超级管理员
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * Define exportable fields for this model.
     */
    public function getExportableFields(): array
    {
        return [
            'id' => ['label' => 'ID', 'width' => 10],
            'username' => ['label' => '用户名', 'width' => 20],
            'name' => ['label' => '姓名', 'width' => 20],
            'email' => ['label' => '邮箱', 'width' => 30],
            'phone' => ['label' => '手机号', 'width' => 15],
            'department.name' => ['label' => '部门', 'width' => 20],
            'status' => ['label' => '状态', 'width' => 10, 'formatter' => 'formatStatusForExport'],
            'login_ip' => ['label' => '最后登录IP', 'width' => 18],
            'login_at' => ['label' => '最后登录时间', 'width' => 20, 'formatter' => 'formatDatetime'],
            'created_at' => ['label' => '创建时间', 'width' => 20, 'formatter' => 'formatDatetime'],
        ];
    }

    /**
     * Format status for export.
     */
    public function formatStatusForExport($value): string
    {
        return $value == self::STATUS_ENABLED ? '正常' : '禁用';
    }

    /**
     * Format datetime for export.
     */
    public function formatDatetime($value): string
    {
        return $value ? $value->format('Y-m-d H:i:s') : '';
    }
}
