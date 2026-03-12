<?php

declare(strict_types=1);

namespace App\Services\Workflow;

use Illuminate\Support\Facades\Redis;
use RuntimeException;

/**
 * 任务锁服务
 *
 * 提供工作流任务的分布式锁机制，防止并发处理
 */
class TaskLockService
{
    /**
     * 锁前缀
     */
    protected string $lockPrefix = 'workflow:task:lock:';

    /**
     * 锁超时时间（秒）
     */
    protected int $lockTimeout = 30;

    /**
     * 获取任务锁的键
     */
    public function getLockKey(int $taskId): string
    {
        return $this->lockPrefix . $taskId;
    }

    /**
     * 尝试获取任务锁
     *
     * @param int $taskId 任务ID
     * @param int|null $ownerId 锁持有者ID（通常是用户ID或进程ID）
     * @param int $ttl 锁过期时间（秒）
     * @return bool 成功获取返回 true
     */
    public function acquire(int $taskId, ?int $ownerId = null, int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->lockTimeout;
        $ownerId = $ownerId ?? gethostname() . ':' . getmypid();
        $lockKey = $this->getLockKey($taskId);
        $expiresAt = now()->addSeconds($ttl)->timestamp;

        // 使用 Redis SET NX PX 原子操作
        $result = Redis::set($lockKey, json_encode([
            'owner' => $ownerId,
            'expires_at' => $expiresAt,
            'acquired_at' => now()->timestamp,
        ]), 'EX', $ttl, 'NX');

        return $result === 'OK';
    }

    /**
     * 释放任务锁
     *
     * @param int $taskId 任务ID
     * @param int|null $ownerId 锁持有者ID
     * @return bool 成功释放返回 true
     */
    public function release(int $taskId, ?int $ownerId = null): bool
    {
        $ownerId = $ownerId ?? gethostname() . ':' . getmypid();
        $lockKey = $this->getLockKey($taskId);

        // 使用 Lua 脚本原子操作：只有持有者才能释放锁
        $script = <<<LUA
            local lockData = redis.call('GET', KEYS[1])
            if not lockData then
                return 0
            end
            local data = cjson.decode(lockData)
            if data.owner == ARGV[1] then
                return redis.call('DEL', KEYS[1])
            else
                return 0
            end
        LUA;

        $result = Redis::eval($script, 1, $lockKey, $ownerId);

        return $result == 1;
    }

    /**
     * 检查任务是否被锁定
     */
    public function isLocked(int $taskId): bool
    {
        $lockKey = $this->getLockKey($taskId);
        return Redis::exists($lockKey) > 0;
    }

    /**
     * 获取锁信息
     */
    public function getLockInfo(int $taskId): ?array
    {
        $lockKey = $this->getLockKey($taskId);
        $data = Redis::get($lockKey);

        if (!$data) {
            return null;
        }

        $info = json_decode($data, true);
        $info['remaining_ttl'] = Redis::ttl($lockKey);

        return $info;
    }

    /**
     * 强制释放锁（用于管理员操作或死锁恢复）
     */
    public function forceRelease(int $taskId): bool
    {
        $lockKey = $this->getLockKey($taskId);
        return Redis::del($lockKey) > 0;
    }

    /**
     * 使用回调执行加锁操作
     *
     * @param int $taskId 任务ID
     * @param callable $callback 回调函数
     * @param int|null $ownerId 锁持有者ID
     * @param int $ttl 锁过期时间
     * @return mixed 回调返回值
     * @throws RuntimeException 如果无法获取锁
     */
    public function executeWithLock(int $taskId, callable $callback, ?int $ownerId = null, int $ttl = 30): mixed
    {
        if (!$this->acquire($taskId, $ownerId, $ttl)) {
            throw new RuntimeException("任务[ID:{$taskId}]正在被其他用户处理，请稍后重试");
        }

        try {
            return $callback();
        } finally {
            $this->release($taskId, $ownerId);
        }
    }

    /**
     * 延长锁的过期时间
     *
     * @param int $taskId 任务ID
     * @param int $additionalSeconds 延长的秒数
     * @param int|null $ownerId 锁持有者ID
     * @return bool 成功延长返回 true
     */
    public function extend(int $taskId, int $additionalSeconds, ?int $ownerId = null): bool
    {
        $ownerId = $ownerId ?? gethostname() . ':' . getmypid();
        $lockKey = $this->getLockKey($taskId);
        $newExpiresAt = now()->addSeconds($additionalSeconds)->timestamp;

        // 使用 Lua 脚本原子操作：只有持有者才能延长锁
        $script = <<<LUA
            local lockData = redis.call('GET', KEYS[1])
            if not lockData then
                return 0
            end
            local data = cjson.decode(lockData)
            if data.owner == ARGV[1] then
                local newExpiresAt = ARGV[2]
                data.expires_at = newExpiresAt
                return redis.call('SET', KEYS[1], cjson.encode(data), 'EX', ARGV[3])
            else
                return 0
            end
        LUA;

        $result = Redis::eval($script, 1, $lockKey, $ownerId, $newExpiresAt, $additionalSeconds);

        return $result === 'OK';
    }

    /**
     * 清理所有过期的锁
     *
     * 由于 Redis 自动过期，此方法主要用于清理可能残留的锁
     */
    public function cleanup(): int
    {
        $pattern = $this->lockPrefix . '*';
        $keys = Redis::keys($pattern);
        $count = 0;

        foreach ($keys as $key) {
            if (!Redis::exists($key)) {
                Redis::del($key);
                $count++;
            }
        }

        return $count;
    }
}
