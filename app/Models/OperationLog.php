<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationLog extends Model
{
    /**
     * 禁用默认时间戳
     */
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'username',
        'method',
        'router',
        'service_name',
        'ip',
        'ip_location',
        'request_data',
        'response_code',
        'response_data',
        'execution_time',
        'created_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'response_code' => 'integer',
        'execution_time' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * 关联用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 获取请求数据（解码JSON）
     */
    public function getRequestDataArrayAttribute(): ?array
    {
        if (empty($this->request_data)) {
            return null;
        }
        return json_decode($this->request_data, true);
    }

    /**
     * 获取响应数据（解码JSON）
     */
    public function getResponseDataArrayAttribute(): ?array
    {
        if (empty($this->response_data)) {
            return null;
        }
        return json_decode($this->response_data, true);
    }

    /**
     * 记录操作日志
     */
    public static function record(array $data): self
    {
        return static::create([
            'user_id' => $data['user_id'] ?? null,
            'username' => $data['username'] ?? null,
            'method' => $data['method'],
            'router' => $data['router'],
            'service_name' => $data['service_name'] ?? null,
            'ip' => $data['ip'] ?? null,
            'ip_location' => $data['ip_location'] ?? null,
            'request_data' => isset($data['request_data']) ? json_encode($data['request_data'], JSON_UNESCAPED_UNICODE) : null,
            'response_code' => $data['response_code'] ?? null,
            'response_data' => isset($data['response_data']) ? json_encode($data['response_data'], JSON_UNESCAPED_UNICODE) : null,
            'execution_time' => $data['execution_time'] ?? null,
            'created_at' => now(),
        ]);
    }
}
