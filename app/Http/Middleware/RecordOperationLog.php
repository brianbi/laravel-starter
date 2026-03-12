<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\OperationLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordOperationLog
{
    /**
     * 不记录日志的路由
     */
    protected array $except = [
        'api/admin/auth/login',
        'api/admin/auth/refresh',
        'api/admin/auth/me',
        'api/admin/auth/menus',
        'api/admin/logs/*',
    ];

    /**
     * 不记录日志的请求方法
     */
    protected array $exceptMethods = [
        'GET',
        'HEAD',
        'OPTIONS',
    ];

    /**
     * 业务名称映射
     */
    protected array $serviceNames = [
        'users' => '用户管理',
        'roles' => '角色管理',
        'menus' => '菜单管理',
        'departments' => '部门管理',
        'positions' => '岗位管理',
        'dictionaries' => '字典管理',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        // 异步记录日志，不阻塞响应
        $this->recordLog($request, $response, $startTime);

        return $response;
    }

    /**
     * 记录操作日志
     */
    protected function recordLog(Request $request, Response $response, float $startTime): void
    {
        // 检查是否需要记录
        if (!$this->shouldRecord($request)) {
            return;
        }

        $executionTime = (int) ((microtime(true) - $startTime) * 1000);

        try {
            OperationLog::record([
                'user_id' => auth('api')->id(),
                'username' => auth('api')->user()?->username,
                'method' => $request->method(),
                'router' => $request->path(),
                'service_name' => $this->getServiceName($request->path()),
                'ip' => $request->ip(),
                'request_data' => $this->getRequestData($request),
                'response_code' => $response->getStatusCode(),
                'response_data' => $this->getResponseData($response),
                'execution_time' => $executionTime,
            ]);
        } catch (\Throwable $e) {
            // 日志记录失败不影响业务
            report($e);
        }
    }

    /**
     * 是否需要记录日志
     */
    protected function shouldRecord(Request $request): bool
    {
        // 排除特定请求方法
        if (in_array($request->method(), $this->exceptMethods)) {
            return false;
        }

        // 排除特定路由
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 获取业务名称
     */
    protected function getServiceName(string $path): ?string
    {
        foreach ($this->serviceNames as $key => $name) {
            if (str_contains($path, $key)) {
                return $name;
            }
        }
        return null;
    }

    /**
     * 获取请求数据（过滤敏感信息）
     */
    protected function getRequestData(Request $request): array
    {
        $data = $request->except(['password', 'password_confirmation', 'token']);

        // 限制数据大小
        return array_map(function ($value) {
            if (is_string($value) && strlen($value) > 1000) {
                return substr($value, 0, 1000) . '...(truncated)';
            }
            return $value;
        }, $data);
    }

    /**
     * 获取响应数据
     */
    protected function getResponseData(Response $response): ?array
    {
        $content = $response->getContent();

        if (empty($content)) {
            return null;
        }

        // 限制响应数据大小
        if (strlen($content) > 2000) {
            return ['message' => '响应数据过大，已省略'];
        }

        $data = json_decode($content, true);

        // 如果不是JSON响应，不记录
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }
}
