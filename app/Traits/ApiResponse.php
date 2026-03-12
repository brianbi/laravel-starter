<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * API 响应格式化
 */
trait ApiResponse
{
    /**
     * 成功响应
     */
    protected function success(mixed $data = null, string $message = 'success'): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * 创建成功响应
     */
    protected function created(mixed $data = null, string $message = '创建成功'): JsonResponse
    {
        return response()->json([
            'code' => 201,
            'message' => $message,
            'data' => $data,
        ], 201);
    }

    /**
     * 无内容响应
     */
    protected function noContent(string $message = '操作成功'): JsonResponse
    {
        return response()->json([
            'code' => 204,
            'message' => $message,
            'data' => null,
        ]);
    }

    /**
     * 错误响应
     */
    protected function error(string $message = 'error', int $code = 400, mixed $data = null): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], $code >= 100 && $code < 600 ? $code : 400);
    }

    /**
     * 未授权响应
     */
    protected function unauthorized(string $message = '未授权，请先登录'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * 禁止访问响应
     */
    protected function forbidden(string $message = '没有权限执行此操作'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * 未找到响应
     */
    protected function notFound(string $message = '资源不存在'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * 验证错误响应
     */
    protected function validationError(mixed $errors, string $message = '验证失败'): JsonResponse
    {
        return response()->json([
            'code' => 422,
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }

    /**
     * 服务器错误响应
     */
    protected function serverError(string $message = '服务器内部错误'): JsonResponse
    {
        return $this->error($message, 500);
    }
}
