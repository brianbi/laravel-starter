<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\OperationLog;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogController extends Controller
{
    use ApiResponse;

    /**
     * 操作日志列表
     */
    public function operationLogs(Request $request): JsonResponse
    {
        $query = OperationLog::query();

        // 用户名筛选
        if ($request->filled('username')) {
            $query->where('username', 'like', '%' . $request->input('username') . '%');
        }

        // 请求方法筛选
        if ($request->filled('method')) {
            $query->where('method', $request->input('method'));
        }

        // 业务名称筛选
        if ($request->filled('service_name')) {
            $query->where('service_name', 'like', '%' . $request->input('service_name') . '%');
        }

        // IP筛选
        if ($request->filled('ip')) {
            $query->where('ip', 'like', '%' . $request->input('ip') . '%');
        }

        // 时间范围筛选
        if ($request->filled('start_time')) {
            $query->where('created_at', '>=', $request->input('start_time'));
        }
        if ($request->filled('end_time')) {
            $query->where('created_at', '<=', $request->input('end_time'));
        }

        // 响应状态码筛选
        if ($request->filled('response_code')) {
            $query->where('response_code', $request->input('response_code'));
        }

        $perPage = (int) $request->input('per_page', 15);
        $logs = $query->orderByDesc('id')->paginate($perPage);

        return $this->success($logs);
    }

    /**
     * 登录日志列表
     */
    public function loginLogs(Request $request): JsonResponse
    {
        $query = LoginLog::query();

        // 用户名筛选
        if ($request->filled('username')) {
            $query->where('username', 'like', '%' . $request->input('username') . '%');
        }

        // IP筛选
        if ($request->filled('ip')) {
            $query->where('ip', 'like', '%' . $request->input('ip') . '%');
        }

        // 状态筛选
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // 时间范围筛选
        if ($request->filled('start_time')) {
            $query->where('login_at', '>=', $request->input('start_time'));
        }
        if ($request->filled('end_time')) {
            $query->where('login_at', '<=', $request->input('end_time'));
        }

        $perPage = (int) $request->input('per_page', 15);
        $logs = $query->orderByDesc('id')->paginate($perPage);

        return $this->success($logs);
    }

    /**
     * 删除操作日志（清理历史数据）
     */
    public function clearOperationLogs(Request $request): JsonResponse
    {
        $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $days = (int) $request->input('days');
        $date = now()->subDays($days);

        $count = OperationLog::where('created_at', '<', $date)->delete();

        return $this->success(['deleted' => $count], "成功清理 {$count} 条操作日志");
    }

    /**
     * 删除登录日志（清理历史数据）
     */
    public function clearLoginLogs(Request $request): JsonResponse
    {
        $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $days = (int) $request->input('days');
        $date = now()->subDays($days);

        $count = LoginLog::where('login_at', '<', $date)->delete();

        return $this->success(['deleted' => $count], "成功清理 {$count} 条登录日志");
    }
}
