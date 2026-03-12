# Laravel Starter 项目改进方案

> 本文档针对项目代码质量分析中发现的待改进项，提供详细的解决方案和实施指南。

---

## 目录

1. [异常处理改进](#1-异常处理改进)
2. [测试覆盖增强](#2-测试覆盖增强)
3. [SQL日志与审计](#3-sql日志与审计)
4. [分页统一性优化](#4-分页统一性优化)
5. [实施优先级](#5-实施优先级)

---

## 1. 异常处理改进

### 1.1 问题分析

**现状**:
- 无自定义异常处理器 (app/Exceptions 目录不存在)
- 控制器中直接使用 try-catch 返回错误信息
- 未区分业务异常和系统异常
- 异常信息可能泄露敏感系统信息

**影响**:
- 前端无法获得统一的错误响应格式
- 生产环境可能暴露系统内部信息
- 无法进行异常监控和告警

### 1.2 解决方案

#### 1.2.1 创建自定义异常类

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * 业务异常基类
 */
class BusinessException extends Exception
{
    protected int $errorCode;
    protected array $errors;

    public function __construct(
        string $message = '业务处理失败',
        int $errorCode = 400,
        array $errors = [],
        ?\Throwable $previous = null
    ) {
        $this->errorCode = $errorCode;
        $this->errors = $errors;
        parent::__construct($message, $errorCode, $previous);
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\BusinessException;

/**
 * 资源未找到异常
 */
class NotFoundException extends BusinessException
{
    public function __construct(
        string $resource = '资源',
        ?int $resourceId = null,
        ?\Throwable $previous = null
    ) {
        $message = $resourceId
            ? "{$resource}[ID:{$resourceId}]不存在"
            : "{$resource}不存在";
        parent::__construct($message, 404, [], $previous);
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\BusinessException;

/**
 * 权限拒绝异常
 */
class ForbiddenException extends BusinessException
{
    public function __construct(
        string $message = '没有权限执行此操作',
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 403, [], $previous);
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\BusinessException;

/**
 * 参数验证异常
 */
class ValidationException extends BusinessException
{
    protected array $errors;

    public function __construct(
        array $errors,
        string $message = '参数验证失败'
    ) {
        parent::__construct($message, 422, $errors);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
```

#### 1.2.2 创建统一异常处理器

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * 不记录日志的异常类型
     */
    protected $dontReport = [
        BusinessException::class,
        NotFoundException::class,
        ForbiddenException::class,
        ValidationException::class,
    ];

    /**
     * 报告异常
     */
    public function report(Throwable $e): void
    {
        // 业务异常记录到单独通道
        if ($e instanceof BusinessException && !($e instanceof NotFoundException)) {
            logger()->channel('business')->error('业务异常', [
                'message' => $e->getMessage(),
                'code' => $e->getErrorCode(),
                'trace' => $e->getTraceAsString(),
            ]);
            return;
        }

        parent::report($e);
    }

    /**
     * 渲染异常为 HTTP 响应
     */
    public function render($request, Throwable $e): JsonResponse
    {
        // Laravel 验证异常处理
        if ($e instanceof LaravelValidationException) {
            return $this->validationErrorResponse($e);
        }

        // 业务异常处理
        if ($e instanceof BusinessException) {
            return $this->businessErrorResponse($e);
        }

        // HTTP 异常处理
        if ($e instanceof HttpException) {
            return $this->httpErrorResponse($e, $request);
        }

        // 开发环境显示详细错误
        if (config('app.debug')) {
            return $this->debugResponse($e, $request);
        }

        // 生产环境隐藏详细错误
        return $this->productionErrorResponse($e, $request);
    }

    /**
     * 业务错误响应
     */
    protected function businessErrorResponse(BusinessException $e): JsonResponse
    {
        $response = [
            'code' => $e->getErrorCode(),
            'message' => $e->getMessage(),
            'data' => null,
        ];

        if ($e instanceof ValidationException) {
            $response['errors'] = $e->getErrors();
        }

        return response()->json($response, $e instanceof ValidationException ? 422 : $e->getErrorCode());
    }

    /**
     * 验证错误响应
     */
    protected function validationErrorResponse(LaravelValidationException $e): JsonResponse
    {
        return response()->json([
            'code' => 422,
            'message' => '参数验证失败',
            'errors' => $e->errors(),
        ], 422);
    }

    /**
     * HTTP 错误响应
     */
    protected function httpErrorResponse(HttpException $e, Request $request): JsonResponse
    {
        $message = match ($e->getStatusCode()) {
            401 => '未授权，请先登录',
            403 => '没有权限访问此资源',
            404 => '请求的资源不存在',
            405 => '请求方法不允许',
            419 => '页面已过期，请刷新后重试',
            429 => '请求过于频繁，请稍后重试',
            default => '请求处理失败',
        };

        return response()->json([
            'code' => $e->getStatusCode(),
            'message' => $message,
            'data' => null,
        ], $e->getStatusCode());
    }

    /**
     * 调试模式错误响应
     */
    protected function debugResponse(Throwable $e, Request $request): JsonResponse
    {
        return response()->json([
            'code' => 500,
            'message' => $e->getMessage(),
            'data' => [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->map(function ($item) {
                    return [
                        'file' => $item['file'] ?? null,
                        'line' => $item['line'] ?? null,
                        'function' => ($item['class'] ?? '') . '::' . ($item['function'] ?? ''),
                    ];
                })->take(10)->toArray(),
            ],
        ], 500);
    }

    /**
     * 生产模式错误响应
     */
    protected function productionErrorResponse(Throwable $e, Request $request): JsonResponse
    {
        // 记录详细错误用于排查
        logger()->error('系统异常', [
            'message' => $e->getMessage(),
            'request_id' => $request->header('X-Request-ID'),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);

        return response()->json([
            'code' => 500,
            'message' => '服务器内部错误，请稍后重试',
            'data' => null,
        ], 500);
    }
}
```

#### 1.2.3 更新 bootstrap/app.php

```php
// 修改前
->withExceptions(function (Exceptions $exceptions): void {
    //
})

// 修改后
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->handler(\App\Exceptions\Handler::class);
})
```

### 1.3 实施步骤

| 步骤 | 操作 | 文件路径 |
|------|------|----------|
| 1 | 创建目录 `app/Exceptions/` | - |
| 2 | 创建 `BusinessException.php` | `app/Exceptions/BusinessException.php` |
| 3 | 创建 `NotFoundException.php` | `app/Exceptions/NotFoundException.php` |
| 4 | 创建 `ForbiddenException.php` | `app/Exceptions/ForbiddenException.php` |
| 5 | 创建 `ValidationException.php` | `app/Exceptions/ValidationException.php` |
| 6 | 创建 `Handler.php` | `app/Exceptions/Handler.php` |
| 7 | 更新 `bootstrap/app.php` | 注册异常处理器 |

### 1.4 控制器改造示例

```php
// 修改前 - 手动处理异常
public function destroy(int $id): JsonResponse
{
    try {
        $this->roleService->delete($id);
        return $this->noContent('角色删除成功');
    } catch (\Exception $e) {
        return $this->error($e->getMessage());
    }
}

// 修改后 - 抛出业务异常
use App\Exceptions\NotFoundException;

public function destroy(int $id): JsonResponse
{
    $role = $this->roleService->findOrFail($id);
    
    if ($role->isSystem()) {
        throw new BusinessException('系统角色不可删除', 403);
    }
    
    $this->roleService->delete($id);
    return $this->noContent('角色删除成功');
}

// 或使用 findOrFail 自动抛出 NotFoundException
public function show(int $id): JsonResponse
{
    $role = $this->roleService->findOrFail($id);  // 未找到自动抛异常
    return $this->success($role);
}
```

---

## 2. 测试覆盖增强

### 2.1 问题分析

**现状**:
- 仅有 7 个测试文件
- Workflow 模块有完整测试，但 User、Role、Auth 等核心模块测试不足
- 缺少服务层和仓库层的单元测试
- 缺少边界条件和异常场景测试

**影响**:
- 重构风险高
- Bug 可能在生产环境才被发现
- 新功能难以保证向后兼容

### 2.2 解决方案

#### 2.2.1 测试文件结构规划

```
tests/
├── Unit/
│   ├── User/
│   │   ├── UserModelTest.php         # 模型测试
│   │   └── UserServiceTest.php       # 服务测试
│   ├── Role/
│   │   ├── RoleModelTest.php
│   │   └── RoleServiceTest.php
│   └── Auth/
│       └── AuthServiceTest.php       # JWT 认证测试
├── Feature/
│   ├── User/
│   │   ├── UserApiTest.php           # API 测试
│   │   └── UserExportTest.php        # 导出测试
│   ├── Role/
│   │   ├── RoleApiTest.php
│   │   └── RolePermissionTest.php
│   └── Auth/
│       └── LoginApiTest.php
└── Database/
    └── Factories/
        └── CustomFactoriesTest.php    # 工厂测试
```

#### 2.2.2 用户模型测试示例

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\User;

use App\Models\User;
use App\Traits\HasStatus;
use PHPUnit\Framework\TestCase;

class UserModelTest extends TestCase
{
    /**
     * 测试用户状态常量
     */
    public function test_user_status_constants(): void
    {
        $this->assertEquals(0, User::STATUS_DISABLED);
        $this->assertEquals(1, User::STATUS_ENABLED);
    }

    /**
     * 测试fillable属性
     */
    public function test_fillable_attributes(): void
    {
        $user = new User();
        $fillable = $user->getFillable();
        
        $this->assertContains('username', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);
        $this->assertContains('department_id', $fillable);
    }

    /**
     * 测试hidden属性
     */
    public function test_hidden_attributes(): void
    {
        $user = new User();
        $hidden = $user->getHidden();
        
        $this->assertContains('password', $hidden);
        $this->assertContains('remember_token', $hidden);
    }

    /**
     * 测试casts属性
     */
    public function test_casts_attributes(): void
    {
        $user = new User();
        $casts = $user->getCasts();
        
        $this->assertEquals('hashed', $casts['password']);
        $this->assertEquals('integer', $casts['status']);
        $this->assertEquals('datetime', $casts['login_at']);
    }

    /**
     * 测试JWT接口实现
     */
    public function test_jwt_subject_interface(): void
    {
        $user = new User();
        
        $this->assertInstanceOf(\PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject::class, $user);
        
        // 测试JWT标识符返回正确的主键
        $this->assertEquals($user->getKey(), $user->getJWTIdentifier());
        
        // 测试自定义claims返回空数组
        $this->assertEquals([], $user->getJWTCustomClaims());
    }

    /**
     * 测试是否超级管理员判断
     */
    public function test_is_super_admin(): void
    {
        $user = new User();
        $user->name = '测试用户';
        
        // 未分配角色时返回false
        $this->assertFalse($user->isSuperAdmin());
    }
}
```

#### 2.2.3 用户服务测试示例

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\User;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserService $service;
    protected UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserRepository(new User());
        $this->service = new UserService($this->repository);
    }

    /**
     * 测试创建用户
     */
    public function test_can_create_user(): void
    {
        $data = [
            'username' => 'testuser',
            'name' => '测试用户',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role_ids' => [1],
        ];

        $user = $this->service->create($data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('testuser', $user->username);
        $this->assertEquals('测试用户', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /**
     * 测试创建用户时密码会被加密
     */
    public function test_password_is_hashed_on_create(): void
    {
        $plainPassword = 'plain_password';
        $data = [
            'username' => 'testuser2',
            'name' => '测试用户2',
            'email' => 'test2@example.com',
            'password' => $plainPassword,
        ];

        $user = $this->service->create($data);

        $this->assertNotEquals($plainPassword, $user->password);
        $this->assertTrue(Hash::check($plainPassword, $user->password));
    }

    /**
     * 测试创建用户时分配角色
     */
    public function test_can_assign_roles_on_create(): void
    {
        $data = [
            'username' => 'testuser3',
            'name' => '测试用户3',
            'email' => 'test3@example.com',
            'password' => 'password',
            'role_ids' => [],
        ];

        $user = $this->service->create($data);

        $this->assertCount(0, $user->roles);
    }

    /**
     * 测试更新用户
     */
    public function test_can_update_user(): void
    {
        $user = User::factory()->create([
            'username' => 'original',
            'name' => '原名称',
        ]);

        $updatedUser = $this->service->update($user->id, [
            'name' => '新名称',
        ]);

        $this->assertEquals('新名称', $updatedUser->name);
        $this->assertEquals('original', $updatedUser->username);
    }

    /**
     * 测试更新用户时密码会被加密
     */
    public function test_password_is_hashed_on_update(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old_password'),
        ]);

        $this->service->update($user->id, [
            'password' => 'new_password',
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check('new_password', $user->password));
    }

    /**
     * 测试删除用户
     */
    public function test_can_delete_user(): void
    {
        $user = User::factory()->create();

        $result = $this->service->delete($user->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted($user);
    }

    /**
     * 测试重置密码
     */
    public function test_can_reset_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old_password'),
        ]);

        $this->service->resetPassword($user->id, 'new_password');

        $user->refresh();
        $this->assertTrue(Hash::check('new_password', $user->password));
    }

    /**
     * 测试用户名存在性检查
     */
    public function test_can_check_username_exists(): void
    {
        User::factory()->create(['username' => 'existing']);

        $this->assertTrue($this->service->usernameExists('existing'));
        $this->assertFalse($this->service->usernameExists('nonexistent'));
    }

    /**
     * 测试排除指定ID的用户名检查
     */
    public function test_can_check_username_exists_excluding_id(): void
    {
        $user = User::factory()->create(['username' => 'unique_user']);

        // 排除自己，应该返回 false
        $this->assertFalse($this->service->usernameExists('unique_user', $user->id));

        // 不排除自己，应该返回 true
        $this->assertTrue($this->service->usernameExists('unique_user'));
    }
}
```

#### 2.2.4 认证 API 测试示例

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 测试用户可以登录
     */
    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
            'status' => User::STATUS_ENABLED,
        ]);

        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'token',
                    'token_type',
                    'expires_in',
                    'user',
                ],
            ])
            ->assertJson([
                'code' => 200,
                'message' => '登录成功',
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    /**
     * 测试禁用用户无法登录
     */
    public function test_disabled_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'username' => 'disabled_user',
            'password' => bcrypt('password'),
            'status' => User::STATUS_DISABLED,
        ]);

        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'disabled_user',
            'password' => 'password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'code' => 401,
            ]);
    }

    /**
     * 测试错误密码无法登录
     */
    public function test_wrong_password_cannot_login(): void
    {
        User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('correct_password'),
        ]);

        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'testuser',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401);
    }

    /**
     * 测试不存在的用户无法登录
     */
    public function test_nonexistent_user_cannot_login(): void
    {
        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'nonexistent',
            'password' => 'password',
        ]);

        $response->assertStatus(401);
    }

    /**
     * 测试必填字段验证
     */
    public function test_login_requires_username_and_password(): void
    {
        $response = $this->postJson('/api/admin/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username', 'password']);
    }

    /**
     * 测试登录后可以获取当前用户信息
     */
    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password'),
        ]);

        $token = auth('api')->login($user);

        $response = $this->getJson('/api/admin/auth/me', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'data' => [
                    'id' => $user->id,
                    'username' => 'testuser',
                ],
            ]);
    }

    /**
     * 测试用户可以登出
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->postJson('/api/admin/auth/logout', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200);

        // Token 应该失效
        $this->assertTrue(auth('api')->check() === false);
    }

    /**
     * 测试Token可以刷新
     */
    public function test_user_can_refresh_token(): void
    {
        $user = User::factory()->create();
        $oldToken = auth('api')->login($user);

        $response = $this->postJson('/api/admin/auth/refresh', [
            'token' => $oldToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['token'],
            ]);

        $newToken = $response->json('data.token');
        $this->assertNotEquals($oldToken, $newToken);
    }
}
```

### 2.3 实施步骤

| 阶段 | 操作 | 预计文件数 |
|------|------|-----------|
| Phase 1 | User 模块测试 (Unit + Feature) | 5 个文件 |
| Phase 2 | Role 模块测试 | 4 个文件 |
| Phase 3 | Auth 模块测试 | 3 个文件 |
| Phase 4 | Department/Position 模块测试 | 4 个文件 |
| Phase 5 | 其他模块测试补充 | 5 个文件 |

---

## 3. SQL日志与审计

### 3.1 问题分析

**现状**:
- 无慢查询日志配置
- 无SQL审计日志
- 无法追踪生产环境问题
- 无法分析数据库性能瓶颈

**影响**:
- 性能问题难以定位
- 安全审计缺失
- 无法满足合规要求

### 3.2 解决方案

#### 3.2.1 慢查询日志配置

```php
<?php

declare(strict_types=1);

return [
    // 慢查询阈值 (毫秒)
    'slow_query_threshold' => 1000,  // 1秒以上记录

    // 是否记录慢查询
    'log_slow_queries' => env('LOG_SLOW_QUERIES', true),

    // 忽略的慢查询语句 (正则)
    'ignore_patterns' => [
        '/^SELECT \* FROM .*WHERE id = ?$/i',
        '/^SHOW (FULL )?TABLES$/i',
    ],
];
```

#### 3.2.2 自定义查询日志中间件

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class QueryLogMiddleware
{
    protected array $logs = [];

    public function handle(Request $request, Closure $next): Response
    {
        // 开发环境记录所有查询
        if (config('app.debug')) {
            DB::enableQueryLog();
        }

        $startTime = microtime(true);
        $response = $next($request);
        $endTime = microtime(true);

        // 开发环境：记录所有查询
        if (config('app.debug')) {
            $this->logAllQueries($request);
        }

        // 生产环境：只记录慢查询
        $this->logSlowQueries($endTime - $startTime, $request);

        return $response;
    }

    /**
     * 记录所有查询 (开发环境)
     */
    protected function logAllQueries(Request $request): void
    {
        $queries = DB::getQueryLog();
        $slowThreshold = config('query.slow_query_threshold', 1000) / 1000;

        $formattedLogs = collect($queries)->map(function ($query) {
            return [
                'sql' => $query['query'],
                'bindings' => $query['bindings'],
                'time' => $query['time'] . 'ms',
            ];
        })->toArray();

        Log::channel('query')->info('SQL Queries', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'user_id' => auth()->id(),
            'queries' => $formattedLogs,
            'total_time' => array_sum(array_column($queries, 'time')) . 'ms',
        ]);
    }

    /**
     * 记录慢查询 (生产环境)
     */
    protected function logSlowQueries(float $responseTime, Request $request): void
    {
        $queries = DB::getQueryLog();
        $slowThreshold = config('query.slow_query_threshold', 1000);

        $slowQueries = collect($queries)->filter(function ($query) use ($slowThreshold) {
            return $query['time'] >= $slowThreshold;
        });

        if ($slowQueries->isNotEmpty()) {
            Log::channel('slow_query')->warning('Slow SQL Queries Detected', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'user_id' => auth()->id(),
                'response_time' => round($responseTime * 1000, 2) . 'ms',
                'slow_queries' => $slowQueries->map(function ($query) {
                    return [
                        'sql' => $query['query'],
                        'time' => $query['time'] . 'ms',
                    ];
                })->toArray(),
                'request_id' => $request->header('X-Request-ID'),
            ]);
        }
    }
}
```

#### 3.2.3 注册中间件

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'query.log' => \App\Http\Middleware\QueryLogMiddleware::class,
    ]);
})
```

#### 3.2.4 数据库审计日志

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OperationLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * 审计日志服务
 */
class AuditService
{
    /**
     * 记录操作日志
     */
    public function log(
        string $action,
        string $model,
        ?int $modelId = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $description = null
    ): OperationLog {
        return OperationLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * 记录创建操作
     */
    public function created(
        string $model,
        int $modelId,
        array $attributes,
        ?string $description = null
    ): OperationLog {
        return $this->log('CREATE', $model, $modelId, [], $attributes, $description);
    }

    /**
     * 记录更新操作
     */
    public function updated(
        string $model,
        int $modelId,
        array $oldValues,
        array $newValues,
        ?string $description = null
    ): OperationLog {
        return $this->log('UPDATE', $model, $modelId, $oldValues, $newValues, $description);
    }

    /**
     * 记录删除操作
     */
    public function deleted(
        string $model,
        int $modelId,
        array $attributes,
        ?string $description = null
    ): OperationLog {
        return $this->log('DELETE', $model, $modelId, $attributes, [], $description);
    }

    /**
     * 记录查询操作
     */
    public function viewed(
        string $model,
        ?int $modelId = null,
        ?string $description = null
    ): OperationLog {
        return $this->log('VIEW', $model, $modelId, [], [], $description);
    }
}
```

#### 3.2.5 模型观察者自动记录审计

```php
<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\AuditService;

class ModelObserver
{
    protected AuditService $audit;

    public function __construct(AuditService $audit)
    {
        $this->audit = $audit;
    }

    /**
     * 创建前记录原始值
     */
    public function creating($model): void
    {
        $model->created_by = auth()->id();
    }

    /**
     * 创建后记录日志
     */
    public function created($model): void
    {
        $this->audit->created(
            $model::class,
            $model->id,
            $model->getAttributes(),
            "创建{$model->getLogName()}"
        );
    }

    /**
     * 更新前记录旧值
     */
    public function updating($model): void
    {
        $model->updated_by = auth()->id();
        $model->setRawAttributes($model->getOriginal());
    }

    /**
     * 更新后记录日志
     */
    public function updated($model): void
    {
        $this->audit->updated(
            $model::class,
            $model->id,
            $model->getOriginal(),
            $model->getChanges(),
            "更新{$model->getLogName()}"
        );
    }

    /**
     * 删除前记录值
     */
    public function deleting($model): void
    {
        $model->deleted_by = auth()->id();
    }

    /**
     * 删除后记录日志
     */
    public function deleted($model): void
    {
        $this->audit->deleted(
            $model::class,
            $model->id,
            $model->getAttributes(),
            "删除{$model->getLogName()}"
        );
    }

    /**
     * 获取模型日志名称
     */
    protected function getLogName($model): string
    {
        return $model->getLogName() ?? '记录';
    }
}
```

#### 3.2.6 模型 trait 支持审计

```php
<?php

declare(strict_types=1);

namespace App\Traits;

use App\Observers\ModelObserver;

trait Auditable
{
    /**
     * 获取日志名称
     */
    public function getLogName(): string
    {
        return $this->logName ?? $this->getTable();
    }

    /**
     * 启动模型观察者
     */
    public static function bootAuditable(): void
    {
        static::observe(ModelObserver::class);
    }
}
```

### 3.3 配置日志通道

```php
// config/logging.php 新增

'slow_query' => [
    'driver' => 'daily',
    'path' => storage_path('logs/slow-queries.log'),
    'level' => 'warning',
    'days' => 14,
],

'query' => [
    'driver' => 'daily',
    'path' => storage_path('logs/queries.log'),
    'level' => 'debug',
    'days' => 7,
],

'audit' => [
    'driver' => 'daily',
    'path' => storage_path('logs/audit.log'),
    'level' => 'info',
    'days' => 30,
],
```

---

## 4. 分页统一性优化

### 4.1 问题分析

**现状**:
- 部分控制器使用 `LengthAwarePaginator`
- 部分控制器使用手动分页
- `per_page` 参数处理不一致
- 关联数据加载未统一

**影响**:
- 代码风格不统一
- 响应格式不一致
- 维护成本增加

### 4.2 解决方案

#### 4.2.1 创建统一分页响应

```php
<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

trait PaginatedResponse
{
    /**
     * 返回统一分页响应
     */
    protected function paginated(
        LengthAwarePaginator $paginator,
        ?callable $formatter = null
    ): JsonResponse {
        $data = $paginator->toArray();

        $responseData = [
            'data' => $formatter ? $paginator->getCollection()->map($formatter) : $data['data'],
            'pagination' => [
                'total' => $data['total'],
                'per_page' => $data['per_page'],
                'current_page' => $data['current_page'],
                'last_page' => $data['last_page'],
                'from' => $data['from'],
                'to' => $data['to'],
            ],
        ];

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $responseData,
        ]);
    }

    /**
     * 返回简单分页响应 (无完整分页信息)
     */
    protected function simplePaginated(
        LengthAwarePaginator $paginator,
        ?callable $formatter = null
    ): JsonResponse {
        $responseData = [
            'data' => $formatter ? $paginator->getCollection()->map($formatter) : $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
            ],
        ];

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $responseData,
        ]);
    }

    /**
     * 返回集合响应
     */
    protected function collection(Collection $collection, ?callable $formatter = null): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'data' => $formatter ? $collection->map($formatter) : $collection->toArray(),
                'total' => $collection->count(),
            ],
        ]);
    }
}
```

#### 4.2.2 创建通用分页参数解析器

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;

class PaginationService
{
    /**
     * 解析分页参数
     */
    public function parse(Request $request): array
    {
        return [
            'page' => (int) ($request->input('page', 1)),
            'per_page' => $this->parsePerPage($request),
            'order_by' => $request->input('order_by', 'id'),
            'order_dir' => strtoupper($request->input('order_dir', 'DESC')),
        ];
    }

    /**
     * 解析每页数量
     */
    protected function parsePerPage(Request $request): int
    {
        $perPage = (int) ($request->input('per_page', 15));
        
        // 限制范围 1-100
        return min(max($perPage, 1), 100);
    }

    /**
     * 构建过滤参数
     */
    public function filters(Request $request, array $allowed): array
    {
        $filters = [];
        
        foreach ($allowed as $key) {
            $value = $request->input($key);
            if ($value !== null && $value !== '') {
                $filters[$key] = $value;
            }
        }
        
        return $filters;
    }
}
```

#### 4.2.3 控制器优化示例

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Services\PaginationService;
use App\Services\UserService;
use App\Traits\ApiResponse;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse, PaginatedResponse;

    public function __construct(
        protected UserService $userService,
        protected PaginationService $paginationService
    ) {}

    /**
     * 用户列表
     */
    public function index(Request $request): JsonResponse
    {
        $params = $this->paginationService->parse($request);
        $filters = $this->paginationService->filters($request, [
            'username',
            'name',
            'phone',
            'email',
            'department_id',
            'status',
        ]);

        $users = $this->userService->paginate(
            array_merge($params, $filters),
            $params['per_page']
        );

        return $this->paginated($users, function ($user) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'department' => $user->department?->name,
                'status' => $user->status,
                'status_text' => $user->status === 1 ? '正常' : '禁用',
                'created_at' => $user->created_at->toDateTimeString(),
            ];
        });
    }

    /**
     * 创建用户
     */
    public function store(UserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $this->userService->create($data);

        return $this->created([
            'user' => $this->formatUser($user),
        ], '用户创建成功');
    }

    /**
     * 格式化用户数据
     */
    protected function formatUser($user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'department' => $user->department?->name,
        ];
    }
}
```

---

## 5. 实施优先级

### 5.1 推荐实施顺序

| 优先级 | 改进项 | 工作量 | 影响范围 | 建议实施时间 |
|--------|--------|--------|----------|--------------|
| P0 | 异常处理改进 | 中 | 全局 | 立即 |
| P1 | 测试覆盖增强 | 高 | 核心模块 | 1-2周 |
| P2 | SQL日志配置 | 低 | 生产环境 | 1周 |
| P3 | 分页统一性 | 中 | 控制器 | 1周 |

### 5.2 里程碑规划

| 里程碑 | 内容 | 预计时间 |
|--------|------|----------|
| M1 | 完成异常处理框架搭建 | 3天 |
| M2 | 完成 User/Role/Auth 测试 | 2周 |
| M3 | 完成 SQL 日志与审计 | 1周 |
| M4 | 完成分页统一性改造 | 1周 |

---

## 附录

### A. 相关文件位置

```
app/
├── Exceptions/
│   ├── BusinessException.php
│   ├── NotFoundException.php
│   ├── ForbiddenException.php
│   ├── ValidationException.php
│   └── Handler.php
├── Http/
│   └── Middleware/
│       └── QueryLogMiddleware.php
├── Services/
│   ├── AuditService.php
│   └── PaginationService.php
├── Observers/
│   └── ModelObserver.php
└── Traits/
    └── PaginatedResponse.php

tests/
├── Unit/
│   └── User/
│       ├── UserModelTest.php
│       └── UserServiceTest.php
└── Feature/
    └── Auth/
        └── LoginApiTest.php
```

### B. 配置更新

```env
# .env 新增配置
LOG_SLOW_QUERIES=true
SLOW_QUERY_THRESHOLD=1000
```

### C. 验证清单

- [ ] 异常处理器正确注册
- [ ] 所有控制器使用统一异常处理
- [ ] 核心模块测试覆盖 > 80%
- [ ] 慢查询日志正常工作
- [ ] 审计日志正确记录
- [ ] 分页响应格式统一

---

> 文档版本: 1.0  
> 创建日期: 2026-02-04  
> 适用版本: Laravel 12.x
