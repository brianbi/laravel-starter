<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    /**
     * 禁用默认时间戳
     */
    public $timestamps = false;

    /**
     * 状态：失败
     */
    public const STATUS_FAIL = 0;

    /**
     * 状态：成功
     */
    public const STATUS_SUCCESS = 1;

    protected $fillable = [
        'username',
        'ip',
        'ip_location',
        'os',
        'browser',
        'status',
        'message',
        'login_at',
    ];

    protected $casts = [
        'status' => 'integer',
        'login_at' => 'datetime',
    ];

    /**
     * 状态映射
     */
    public static function getStatusMap(): array
    {
        return [
            self::STATUS_FAIL => '失败',
            self::STATUS_SUCCESS => '成功',
        ];
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute(): string
    {
        return self::getStatusMap()[$this->status] ?? '未知';
    }

    /**
     * 记录登录成功日志
     */
    public static function success(string $username, ?string $ip = null, ?string $message = null): self
    {
        return static::record($username, $ip, self::STATUS_SUCCESS, $message ?? '登录成功');
    }

    /**
     * 记录登录失败日志
     */
    public static function fail(string $username, ?string $ip = null, ?string $message = null): self
    {
        return static::record($username, $ip, self::STATUS_FAIL, $message ?? '登录失败');
    }

    /**
     * 记录登录日志
     */
    public static function record(string $username, ?string $ip, int $status, ?string $message = null): self
    {
        $userAgent = request()->userAgent();
        $browser = static::parseBrowser($userAgent);
        $os = static::parseOs($userAgent);

        return static::create([
            'username' => $username,
            'ip' => $ip,
            'ip_location' => null, // 可以接入IP归属地服务
            'os' => $os,
            'browser' => $browser,
            'status' => $status,
            'message' => $message,
            'login_at' => now(),
        ]);
    }

    /**
     * 解析浏览器信息
     */
    protected static function parseBrowser(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Unknown';
        }

        $browsers = [
            'Edge' => '/Edg\/([0-9.]+)/',
            'Chrome' => '/Chrome\/([0-9.]+)/',
            'Firefox' => '/Firefox\/([0-9.]+)/',
            'Safari' => '/Safari\/([0-9.]+)/',
            'Opera' => '/OPR\/([0-9.]+)/',
            'IE' => '/MSIE ([0-9.]+)/',
        ];

        foreach ($browsers as $browser => $pattern) {
            if (preg_match($pattern, $userAgent, $matches)) {
                return $browser . ' ' . ($matches[1] ?? '');
            }
        }

        return 'Unknown';
    }

    /**
     * 解析操作系统信息
     */
    protected static function parseOs(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Unknown';
        }

        $osList = [
            'Windows 11' => '/Windows NT 10.0.*Win64/',
            'Windows 10' => '/Windows NT 10.0/',
            'Windows 8.1' => '/Windows NT 6.3/',
            'Windows 8' => '/Windows NT 6.2/',
            'Windows 7' => '/Windows NT 6.1/',
            'macOS' => '/Mac OS X ([0-9._]+)/',
            'Linux' => '/Linux/',
            'Android' => '/Android ([0-9.]+)/',
            'iOS' => '/iPhone OS ([0-9_]+)/',
        ];

        foreach ($osList as $os => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $os;
            }
        }

        return 'Unknown';
    }
}
