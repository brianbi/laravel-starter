# Laravel Admin Starter

一个基于 Laravel 12.x 的现代化管理后台 starter kit，集成了 JWT 认证、RBAC 权限控制、工作流引擎和表单设计器等企业级功能。

[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4.svg)](https://www.php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## ✨ 功能特性

### 🔐 认证与授权
- **JWT 认证** - 基于 `php-open-source-saver/jwt-auth` 的无状态认证
- **RBAC 权限控制** - 集成 `spatie/laravel-permission` 实现角色权限管理
- **菜单权限** - 基于角色的动态菜单生成

### 🏢 基础管理模块
- **用户管理** - 用户增删改查、状态管理、密码重置
- **角色管理** - 角色 CRUD、权限分配、菜单分配
- **部门管理** - 树形结构部门管理
- **岗位管理** - 用户岗位多对多关系管理
- **菜单管理** - 动态菜单树形结构
- **字典管理** - 系统字典及字典项管理

### 📊 高级功能模块
- **工作流引擎** - 可视化流程定义、实例管理、任务处理、代理委托
- **表单设计器** - 动态表单定义、数据收集、表单验证
- **通知中心** - 消息模板、发送记录、用户通知
- **数据导出** - Excel 导出、字段配置、异步任务

### 📝 日志与监控
- **操作日志** - 记录所有管理操作
- **登录日志** - 记录用户登录信息

## 🚀 快速开始

### 环境要求

- PHP >= 8.2
- Composer
- Node.js & NPM
- MySQL 5.7+ 或 SQLite (测试环境)

### 安装步骤

```bash
# 1. 克隆项目
git clone <repository-url>
cd laravel-starter

# 2. 安装依赖
composer install

# 3. 配置环境
cp .env.example .env
php artisan key:generate

# 4. 数据库迁移
php artisan migrate --force

# 5. 安装前端依赖
npm install

# 6. 构建前端资源
npm run build

# 7. 启动开发服务器
composer run dev
```

### 开发环境

项目使用 `concurrently` 同时启动多个服务：

```bash
composer run dev
# 等同于：
# php artisan serve
# php artisan queue:listen --tries=1
# npm run dev
```

### 测试

```bash
# 运行所有测试
composer test

# 运行特定测试类
php artisan test --filter=UserTest

# 运行特定测试方法
php artisan test --filter=test_user_can_login

# 生成测试覆盖率报告
php artisan test --coverage
```

## 📁 项目结构

```
app/
├── Http/
│   ├── Controllers/          # 控制器
│   │   └── Admin/           # 后台管理控制器
│   ├── Requests/            # 表单验证请求
│   └── Middleware/          # 中间件
├── Models/                  # Eloquent 模型
├── Services/                # 业务逻辑层
├── Repositories/            # 数据访问层
├── Traits/                  # 通用特性
└── Enums/                   # 枚举类

routes/
├── admin.php                # 后台 API 路由
└── web.php                  # Web 路由

tests/
├── Unit/                    # 单元测试
└── Feature/                 # 功能测试

database/
├── factories/               # 模型工厂
├── seeders/                 # 数据填充
└── migrations/              # 数据库迁移
```

## 🛠️ 开发指南

### 代码规范

项目使用 Laravel Pint 进行代码格式化：

```bash
# 格式化代码
./vendor/bin/pint

# 检查代码规范（不修改）
./vendor/bin/pint --test
```

### API 响应格式

所有 API 响应统一格式：

```json
{
    "code": 200,
    "message": "success",
    "data": { ... }
}
```

### 控制器示例

```php
class UserController extends Controller
{
    use ApiResponse;

    public function __construct(protected UserService $userService) {}

    public function index(Request $request): JsonResponse
    {
        $params = $request->only(['username', 'name', 'page', 'per_page']);
        $perPage = (int) ($params['per_page'] ?? 15);
        $users = $this->userService->paginate($params, $perPage);
        $users->load(['department', 'roles']);
        return $this->success($users);
    }
}
```

### 服务层示例

```php
class UserService extends BaseService
{
    public function create(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $user = $this->repository->create($data);
            if (!empty($data['role_ids'])) {
                $user->syncRoles($data['role_ids']);
            }
            return $user;
        });
    }
}
```

## 🔧 常用命令

```bash
# 生成控制器
php artisan make:controller Admin/UserController --api

# 生成模型
php artisan make:model User -mfs

# 生成服务
php artisan make:service UserService

# 生成仓库
php artisan make:repository UserRepository

# 生成请求
php artisan make:request Admin/UserRequest

# 运行队列
php artisan queue:work

# 清理缓存
php artisan optimize:clear
```

## 📦 主要依赖

- **Laravel 12.x** - PHP 框架
- **JWT Auth** - 无状态认证
- **Spatie Permission** - RBAC 权限控制
- **Laravel Query Builder** - 查询构建器
- **PhpSpreadsheet** - Excel 导出
- **Vite** - 前端构建工具
- **TailwindCSS 4.x** - CSS 框架

## 🤝 贡献指南

1. Fork 项目
2. 创建功能分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 开启 Pull Request

## 📄 许可证

本项目基于 MIT 许可证开源 - 详见 [LICENSE](LICENSE) 文件。

## 🙏 致谢

- [Laravel](https://laravel.com) - 优雅的 PHP 框架
- [Spatie](https://spatie.be) - 优秀的 Laravel 包
- [JWT Auth](https://github.com/php-open-source-saver/jwt-auth) - JWT 认证实现
