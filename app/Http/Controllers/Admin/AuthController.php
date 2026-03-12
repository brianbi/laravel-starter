<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Events\UserLoginEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * 用户登录
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only(['username', 'password']);
        $inputUsername = $credentials['username'];

        // 支持用户名或邮箱登录
        $loginField = filter_var($inputUsername, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [
            $loginField => $inputUsername,
            'password' => $credentials['password']
        ];

        // 检查用户状态
        $user = User::where($loginField, $inputUsername)->first();
        if ($user && $user->isDisabled()) {
            // 记录登录失败日志
            UserLoginEvent::dispatch($inputUsername, false, $request->ip(), '账号已被禁用');
            return $this->error('账号已被禁用，请联系管理员', 403);
        }

        if (!$token = Auth::guard('api')->attempt($credentials)) {
            // 记录登录失败日志
            UserLoginEvent::dispatch($inputUsername, false, $request->ip(), '用户名或密码错误');
            return $this->error('用户名或密码错误', 401);
        }

        // 更新登录信息
        $user = Auth::guard('api')->user();
        $user->updateLoginInfo($request->ip());

        // 记录登录成功日志
        UserLoginEvent::dispatch($user->username, true, $request->ip(), '登录成功');

        return $this->respondWithToken((string)$token);
    }

    /**
     * 退出登录
     */
    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();
        return $this->success(null, '退出成功');
    }

    /**
     * 刷新 Token
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = Auth::guard('api')->refresh();
            return $this->respondWithToken($token);
        } catch (\Exception $e) {
            return $this->error('Token 刷新失败，请重新登录', 401);
        }
    }

    /**
     * 获取当前用户信息
     */
    public function me(): JsonResponse
    {
        $user = Auth::guard('api')->user();
        $user->load(['department', 'positions', 'roles']);

        return $this->success([
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'department' => $user->department?->only(['id', 'name']),
            'positions' => $user->positions->map->only(['id', 'name']),
            'roles' => $user->roles->pluck('name'),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    /**
     * 获取当前用户菜单
     */
    public function menus(): JsonResponse
    {
        $user = Auth::guard('api')->user();

        // 超级管理员获取所有菜单
        if ($user->isSuperAdmin()) {
            $menus = \App\Models\Menu::query()
                ->where('status', 1)
                ->where('type', '!=', 'B')
                ->orderBy('sort')
                ->orderBy('id')
                ->get();
        } else {
            // 普通用户根据角色获取菜单
            $menuIds = $user->roles()
                ->with('menus')
                ->get()
                ->pluck('menus')
                ->flatten()
                ->pluck('id')
                ->unique();

            $menus = \App\Models\Menu::query()
                ->whereIn('id', $menuIds)
                ->where('status', 1)
                ->where('type', '!=', 'B')
                ->orderBy('sort')
                ->orderBy('id')
                ->get();
        }

        return $this->success(\App\Models\Menu::buildTree($menus));
    }

    /**
     * 返回 Token 响应
     */
    protected function respondWithToken(string $token): JsonResponse
    {
        return $this->success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ]);
    }
}
