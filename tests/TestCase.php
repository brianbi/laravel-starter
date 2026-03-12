<?php

namespace Tests;

use Illuminate\Support\Facades\Redis;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 清理所有工作流任务锁 - 使用底层 Redis 客户端
        $redis = Redis::connection()->client();
        $keys = $redis->keys('laravel-database-workflow:task:lock:*');
        if (!empty($keys)) {
            $redis->del($keys);
        }
    }

    protected function tearDown(): void
    {
        // 再次清理，确保锁被释放
        $redis = Redis::connection()->client();
        $keys = $redis->keys('laravel-database-workflow:task:lock:*');
        if (!empty($keys)) {
            $redis->del($keys);
        }

        parent::tearDown();
    }
}
