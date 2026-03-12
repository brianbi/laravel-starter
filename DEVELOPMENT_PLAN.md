# 后台管理系统开发计划

> 参考 MineAdmin 设计，基于 Laravel 12 + Vue3 + Tailwind CSS 构建

## 项目现状

| 项目 | 当前状态 |
|------|----------|
| 后端 | Laravel 12.0 + PHP 8.2 |
| 前端 | Vite 7.0 + Tailwind CSS 4.0 |
| 数据 | 仅基础 User 模型 |
| 功能 | 空白starter项目 |

---

## 阶段一：基础架构搭建

### 1.1 后端分层架构

参考 MineAdmin 的分层设计，建立 Laravel 适配的目录结构：

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Admin/              # 后台控制器
│   ├── Middleware/             # 中间件
│   └── Requests/               # 表单验证
├── Services/                   # 业务逻辑层（新增）
├── Repositories/               # 数据访问层（新增）
├── Models/                     # 数据模型
│   └── Admin/                  # 后台相关模型
├── Enums/                      # 枚举类（新增）
├── Traits/                     # 公共Trait（新增）
└── Exceptions/                 # 自定义异常
```

**任务清单：**
- [x] 创建 Services 基类 `App\Services\BaseService`
- [x] 创建 Repositories 基类 `App\Repositories\BaseRepository`
- [x] 创建通用 Trait（HasCreator, HasDepartment 等）
- [x] 配置 API 路由分组 `routes/admin.php`

### 1.2 依赖包安装

```bash
# 权限管理
composer require spatie/laravel-permission

# API认证（JWT方案）
composer require php-open-source-saver/jwt-auth

# API资源转换
composer require spatie/laravel-query-builder

# 数据导出（可选）
composer require maatwebsite/excel
```

**任务清单：**
- [x] 安装 spatie/laravel-permission
- [x] 安装 php-open-source-saver/jwt-auth
- [x] 安装 spatie/laravel-query-builder
- [x] 发布配置文件并完成基础配置

---

## 阶段二：用户认证模块

### 2.1 数据表设计

**users 表扩展字段：**

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| username | string | 登录账号（新增） |
| name | string | 姓名 |
| email | string | 邮箱 |
| phone | string | 手机号（新增） |
| avatar | string | 头像（新增） |
| password | string | 密码 |
| department_id | bigint | 所属部门（新增） |
| status | tinyint | 状态 0禁用 1正常（新增） |
| login_ip | string | 最后登录IP（新增） |
| login_at | timestamp | 最后登录时间（新增） |
| created_by | bigint | 创建人（新增） |
| updated_by | bigint | 更新人（新增） |
| timestamps | - | 创建/更新时间 |
| deleted_at | timestamp | 软删除（新增） |

**任务清单：**
- [x] 创建用户表扩展迁移
- [x] 更新 User 模型，添加关联关系
- [x] 创建 UserService
- [x] 创建 UserRepository
- [x] 创建 UserController (CRUD + 状态切换)
- [x] 创建 UserRequest 表单验证

### 2.2 JWT 双Token认证

```
认证流程：
┌─────────┐      ┌─────────┐      ┌─────────┐
│  登录   │ ───→ │ 返回    │ ───→ │ 请求时  │
│  请求   │      │ access  │      │ 携带    │
│         │      │ refresh │      │ token   │
└─────────┘      └─────────┘      └─────────┘
                                       │
                      ┌────────────────┼────────────────┐
                      ↓                ↓                ↓
              access有效        access过期       refresh过期
              正常响应          用refresh刷新    重新登录
```

**任务清单：**
- [x] 配置 JWT 双Token机制
- [x] 创建 AuthController（login, logout, refresh, me）
- [x] 创建 AuthService
- [x] 创建认证中间件
- [x] 实现登录日志记录

---

## 阶段三：权限管理模块

### 3.1 RBAC 数据模型

```
┌────────┐     ┌─────────────┐     ┌────────┐
│  User  │────→│ model_has   │←────│  Role  │
│        │     │   _roles    │     │        │
└────────┘     └─────────────┘     └───┬────┘
                                       │
                               ┌───────┴───────┐
                               ↓               ↓
                        ┌──────────┐    ┌──────────┐
                        │role_has  │    │permissions│
                        │permissions│←──│  (菜单)  │
                        └──────────┘    └──────────┘
```

### 3.2 菜单权限表设计

**menus 表：**

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| parent_id | bigint | 父级ID（树形结构） |
| name | string | 菜单名称 |
| code | string | 权限标识（如 system:user:list） |
| type | char | 类型：M菜单 B按钮 L外链 |
| icon | string | 图标 |
| route | string | 前端路由 |
| component | string | 前端组件路径 |
| redirect | string | 重定向 |
| permission | string | 权限标识 |
| sort | int | 排序 |
| status | tinyint | 状态 |
| is_hidden | tinyint | 是否隐藏 |
| is_cache | tinyint | 是否缓存 |
| timestamps | - | 时间戳 |

**任务清单：**
- [x] 运行 spatie/laravel-permission 迁移
- [x] 创建 menus 表迁移
- [x] 创建 Menu 模型（树形结构支持）
- [x] 创建 MenuService
- [x] 创建 MenuController（树形CRUD）
- [x] 创建 Role 模型扩展
- [x] 创建 RoleService
- [x] 创建 RoleController（CRUD + 权限分配）
- [x] 实现权限中间件检查

---

## 阶段四：部门组织模块

### 4.1 部门表设计

**departments 表：**

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| parent_id | bigint | 父级部门（树形） |
| name | string | 部门名称 |
| leader | string | 负责人 |
| phone | string | 联系电话 |
| email | string | 邮箱 |
| sort | int | 排序 |
| status | tinyint | 状态 |
| level | int | 层级深度 |
| path | string | 祖先路径（如 0,1,2） |
| timestamps | - | 时间戳 |
| deleted_at | timestamp | 软删除 |

### 4.2 数据权限设计

```
数据权限策略：
├── 1. 全部数据权限
├── 2. 自定义数据权限（指定部门）
├── 3. 本部门数据权限
├── 4. 本部门及以下数据权限
└── 5. 仅本人数据权限
```

**任务清单：**
- [x] 创建 departments 表迁移
- [x] 创建 Department 模型（树形Trait）
- [x] 创建 DepartmentService
- [x] 创建 DepartmentController（树形CRUD）
- [x] 实现数据权限 Scope（DataPermissionScope）
- [x] 角色表添加 data_scope 字段

---

## 阶段五：岗位管理模块

### 5.1 岗位表设计

**positions 表：**

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| name | string | 岗位名称 |
| code | string | 岗位编码 |
| sort | int | 排序 |
| status | tinyint | 状态 |
| remark | string | 备注 |
| timestamps | - | 时间戳 |
| deleted_at | timestamp | 软删除 |

**user_positions 中间表：**

| 字段 | 类型 | 说明 |
|------|------|------|
| user_id | bigint | 用户ID |
| position_id | bigint | 岗位ID |

**任务清单：**
- [x] 创建 positions 表迁移
- [x] 创建 user_positions 中间表迁移
- [x] 创建 Position 模型
- [x] 创建 PositionService
- [x] 创建 PositionController
- [x] User 模型添加岗位关联

---

## 阶段六：系统基础功能

### 6.1 操作日志

**operation_logs 表：**

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| user_id | bigint | 操作用户 |
| username | string | 用户名 |
| method | string | 请求方法 |
| router | string | 请求路由 |
| service_name | string | 业务名称 |
| ip | string | IP地址 |
| ip_location | string | IP归属地 |
| request_data | text | 请求参数 |
| response_code | int | 响应状态码 |
| response_data | text | 响应数据 |
| created_at | timestamp | 创建时间 |

### 6.2 登录日志

**login_logs 表：**

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| username | string | 登录账号 |
| ip | string | 登录IP |
| ip_location | string | IP归属地 |
| os | string | 操作系统 |
| browser | string | 浏览器 |
| status | tinyint | 状态 0失败 1成功 |
| message | string | 提示消息 |
| login_at | timestamp | 登录时间 |

**任务清单：**
- [x] 创建 operation_logs 表迁移
- [x] 创建 login_logs 表迁移
- [x] 创建 OperationLog 模型
- [x] 创建 LoginLog 模型
- [x] 创建操作日志中间件（自动记录）
- [x] 创建登录日志 Event/Listener
- [x] 创建 LogController（日志查询接口）

### 6.3 字典管理

**dictionaries 表：**

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| name | string | 字典名称 |
| code | string | 字典编码 |
| status | tinyint | 状态 |
| remark | string | 备注 |
| timestamps | - | 时间戳 |

**dictionary_items 表：**

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| dictionary_id | bigint | 字典ID |
| label | string | 显示标签 |
| value | string | 值 |
| sort | int | 排序 |
| status | tinyint | 状态 |
| remark | string | 备注 |

**任务清单：**
- [x] 创建 dictionaries 表迁移
- [x] 创建 dictionary_items 表迁移
- [x] 创建 Dictionary 模型
- [x] 创建 DictionaryItem 模型
- [x] 创建 DictionaryService
- [x] 创建 DictionaryController

---

## 阶段七：前端架构

### 7.1 技术选型

| 类别 | 选择 |
|------|------|
| UI框架 | Element Plus |
| 状态管理 | Pinia |
| 路由 | Vue Router |
| HTTP | Axios |
| 样式 | Tailwind CSS 4.0 |

### 7.2 前端目录结构

```
resources/js/
├── api/                  # API接口
│   ├── auth.js
│   ├── user.js
│   ├── role.js
│   ├── menu.js
│   ├── department.js
│   └── position.js
├── components/           # 公共组件
│   ├── Table/
│   ├── Form/
│   ├── Tree/
│   └── Layout/
├── composables/          # 组合式函数
├── layouts/              # 布局组件
│   └── AdminLayout.vue
├── router/               # 路由配置
├── stores/               # Pinia状态
├── utils/                # 工具函数
└── views/                # 页面
    └── admin/
        ├── dashboard/
        ├── system/
        │   ├── user/
        │   ├── role/
        │   ├── menu/
        │   ├── department/
        │   └── position/
        └── monitor/
            ├── operationLog/
            └── loginLog/
```

**任务清单：**
- [ ] 安装前端依赖（Vue3, Pinia, Vue Router, Element Plus）
- [ ] 搭建基础布局（侧边栏+顶栏+内容区）
- [ ] 实现动态路由（基于权限菜单）
- [ ] 封装 Axios 请求工具
- [ ] 封装通用CRUD表格组件
- [ ] 封装树形选择组件
- [ ] 实现登录页面
- [ ] 实现各模块页面

---

## 开发优先级与依赖关系

```
阶段1 基础架构
    │
    ↓
阶段2 用户认证 ←── 最高优先级
    │
    ↓
阶段3 权限管理 ←── 依赖用户认证
    │
    ↓
阶段4 部门组织 ←── 依赖权限管理
    │
    ↓
阶段5 岗位管理 ←── 可与阶段4并行
    │
    ↓
阶段6 系统功能 ←── 可独立开发
    │
    ↓
阶段7 前端开发 ←── 可与后端并行
```

---

## API 路由规划

```php
// routes/admin.php
Route::prefix('admin')->group(function () {
    // 认证（无需登录）
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/refresh', [AuthController::class, 'refresh']);
    
    // 需要认证的路由
    Route::middleware(['auth:api'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        
        // 用户管理
        Route::apiResource('users', UserController::class);
        Route::put('users/{user}/status', [UserController::class, 'updateStatus']);
        
        // 角色管理
        Route::apiResource('roles', RoleController::class);
        Route::put('roles/{role}/permissions', [RoleController::class, 'updatePermissions']);
        
        // 菜单管理
        Route::apiResource('menus', MenuController::class);
        Route::get('menus/tree', [MenuController::class, 'tree']);
        
        // 部门管理
        Route::apiResource('departments', DepartmentController::class);
        Route::get('departments/tree', [DepartmentController::class, 'tree']);
        
        // 岗位管理
        Route::apiResource('positions', PositionController::class);
        
        // 字典管理
        Route::apiResource('dictionaries', DictionaryController::class);
        Route::apiResource('dictionaries.items', DictionaryItemController::class);
        
        // 日志查询
        Route::get('logs/operation', [LogController::class, 'operationLogs']);
        Route::get('logs/login', [LogController::class, 'loginLogs']);
    });
});
```

---

## 更新记录

| 日期 | 内容 |
|------|------|
| 2026-01-30 | 初始化开发计划 |
| 2026-01-30 | 完成阶段1-5后端基础模块开发 |
| 2026-01-30 | 完成阶段6系统基础功能（日志、字典） |
