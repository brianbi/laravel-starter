<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRecord extends Model
{
    public const STATUS_PENDING = 0;
    public const STATUS_SENT = 1;
    public const STATUS_FAILED = 2;

    public const CHANNEL_DATABASE = 'database';
    public const CHANNEL_MAIL = 'mail';
    public const CHANNEL_DINGTALK = 'dingtalk';
    public const CHANNEL_WECHAT = 'wechat';
    public const CHANNEL_SMS = 'sms';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'channel',
        'type',
        'title',
        'content',
        'data',
        'status',
        'error_message',
        'sent_at',
        'created_at',
    ];

    protected $casts = [
        'data' => 'array',
        'status' => 'integer',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (NotificationRecord $record) {
            $record->created_at = $record->created_at ?? now();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => '待发送',
            self::STATUS_SENT => '已发送',
            self::STATUS_FAILED => '发送失败',
            default => '未知',
        };
    }

    public function getChannelTextAttribute(): string
    {
        return match ($this->channel) {
            self::CHANNEL_DATABASE => '站内信',
            self::CHANNEL_MAIL => '邮件',
            self::CHANNEL_DINGTALK => '钉钉',
            self::CHANNEL_WECHAT => '企业微信',
            self::CHANNEL_SMS => '短信',
            default => $this->channel,
        };
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeByStatus($query, int $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeExpired($query, int $days = null)
    {
        $days = $days ?? config('notification.record_retention_days', 90);
        return $query->where('created_at', '<', now()->subDays($days));
    }
}
