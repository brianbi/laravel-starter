# 通用列表导出功能设计文档

> 为后台管理系统提供统一的列表数据导出能力

## 一、需求概述

### 1.1 功能目标

| 项目 | 说明 |
|------|------|
| 导出格式 | Excel (.xlsx) + CSV |
| 导出模式 | 智能模式（自动切换同步/异步） |
| 导出范围 | 当前页 / 筛选条件 / 指定数据ID |
| 字段选择 | 用户可选择导出字段 |
| 数据阈值 | 可配置（默认5000条） |
| 异步通知 | 邮件通知 |
| 文件存储 | 可配置（本地/云存储） |
| 文件保留 | 24小时自动清理 |

---

## 二、架构设计

### 2.1 整体流程

```
┌─────────────────────────────────────────────────────────┐
│                    前端请求导出                          │
│  { scope, ids, filters, fields, format }               │
└─────────────────────┬───────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────┐
│                  ExportController                        │
│  - 统计数据量                                            │
│  - 判断同步/异步                                         │
└─────────────────────┬───────────────────────────────────┘
                      │
        ┌─────────────┴─────────────┐
        ▼                           ▼
┌───────────────┐          ┌───────────────────┐
│   同步导出    │          │    异步导出       │
│  (<= 阈值)    │          │   (> 阈值)        │
│  直接返回文件  │          │  返回任务ID       │
└───────────────┘          └───────┬───────────┘
                                   │
                                   ▼
                          ┌───────────────────┐
                          │   队列任务处理    │
                          │  ExportJob        │
                          └───────┬───────────┘
                                  │
                                  ▼
                          ┌───────────────────┐
                          │  生成文件并存储   │
                          │  发送邮件通知     │
                          └───────────────────┘
```

### 2.2 核心组件

| 组件 | 文件路径 | 职责 |
|------|----------|------|
| 配置文件 | `config/export.php` | 导出配置（阈值、存储、保留时间等） |
| 导出任务表 | `export_tasks` | 存储异步导出任务状态 |
| ExportTask 模型 | `app/Models/ExportTask.php` | 导出任务模型 |
| Exportable Trait | `app/Traits/Exportable.php` | 为模型提供可导出字段定义 |
| ExportService | `app/Services/ExportService.php` | 导出服务，处理通用逻辑 |
| ExportJob | `app/Jobs/ExportJob.php` | 异步导出队列任务 |
| ExportController | `app/Http/Controllers/Admin/ExportController.php` | 导出API控制器 |
| ExportCompleted 邮件 | `app/Mail/ExportCompleted.php` | 导出完成通知邮件 |
| CleanExpiredExports 命令 | `app/Console/Commands/CleanExpiredExports.php` | 清理过期文件命令 |

---

## 三、数据库设计

### 3.1 export_tasks 表

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| uuid | string(36) | 任务唯一标识 |
| user_id | bigint | 用户ID |
| resource | string(50) | 资源类型（users/roles等） |
| scope | string(20) | 导出范围：all/page/selected |
| filters | json | 筛选条件 |
| fields | json | 导出字段 |
| format | string(10) | 格式：xlsx/csv |
| total_count | int | 总记录数 |
| status | tinyint | 状态：0待处理 1处理中 2成功 3失败 |
| file_path | string | 生成的文件路径 |
| file_size | bigint | 文件大小(bytes) |
| error_message | string | 错误信息 |
| started_at | timestamp | 开始处理时间 |
| completed_at | timestamp | 完成时间 |
| expires_at | timestamp | 过期时间 |
| created_at | timestamp | 创建时间 |
| updated_at | timestamp | 更新时间 |

---

## 四、API 设计

### 4.1 发起导出请求

```
POST /api/admin/{resource}/export

请求体：
{
    "scope": "all|page|selected",     // 导出范围
    "ids": [1,2,3],                    // scope=selected 时必填
    "filters": {...},                  // scope=all 时的筛选条件
    "page": 1,                         // scope=page 时的页码
    "per_page": 15,                    // scope=page 时的每页数量
    "fields": ["id","name","email"],   // 要导出的字段（可选，默认全部）
    "format": "xlsx|csv"               // 导出格式，默认xlsx
}

响应 - 同步导出：
{
    "code": 200,
    "data": {
        "type": "sync",
        "download_url": "/api/admin/export/download/xxx.xlsx"
    }
}

响应 - 异步导出：
{
    "code": 200,
    "data": {
        "type": "async", 
        "task_id": "uuid",
        "message": "导出任务已提交，完成后将通过邮件通知您"
    }
}
```

### 4.2 查询任务状态

```
GET /api/admin/export/tasks/{uuid}

响应：
{
    "code": 200,
    "data": {
        "uuid": "xxx",
        "status": 2,
        "status_text": "成功",
        "total_count": 10000,
        "file_size": 1024000,
        "download_url": "/api/admin/export/download/xxx",
        "expires_at": "2026-01-31 12:00:00"
    }
}
```

### 4.3 下载导出文件

```
GET /api/admin/export/download/{uuid}

响应：文件流
```

### 4.4 获取任务列表（当前用户）

```
GET /api/admin/export/tasks

响应：
{
    "code": 200,
    "data": [...]
}
```

---

## 五、Exportable Trait 使用

### 5.1 模型中定义可导出字段

```php
class User extends Model
{
    use Exportable;

    /**
     * 定义可导出的字段
     */
    public function getExportableFields(): array
    {
        return [
            'id' => ['label' => 'ID', 'width' => 10],
            'username' => ['label' => '用户名', 'width' => 20],
            'name' => ['label' => '姓名', 'width' => 20],
            'email' => ['label' => '邮箱', 'width' => 30],
            'phone' => ['label' => '手机号', 'width' => 15],
            'department.name' => ['label' => '部门', 'width' => 20],
            'status' => ['label' => '状态', 'width' => 10, 'formatter' => 'formatStatus'],
            'created_at' => ['label' => '创建时间', 'width' => 20],
        ];
    }

    /**
     * 格式化状态字段
     */
    public function formatStatus($value): string
    {
        return $value == 1 ? '正常' : '禁用';
    }
}
```

### 5.2 控制器中添加导出方法

```php
class UserController extends Controller
{
    public function export(ExportRequest $request): JsonResponse
    {
        return $this->exportService->export(
            resource: 'users',
            modelClass: User::class,
            request: $request,
            queryBuilder: fn($query) => $this->applyFilters($query, $request)
        );
    }
}
```

---

## 六、配置文件

### 6.1 config/export.php

```php
return [
    // 同步/异步切换阈值
    'async_threshold' => env('EXPORT_ASYNC_THRESHOLD', 5000),

    // 单次最大导出数量限制
    'max_export_count' => env('EXPORT_MAX_COUNT', 100000),

    // 文件存储配置
    'storage' => [
        'disk' => env('EXPORT_DISK', 'local'),  // local, s3, oss
        'path' => 'exports',                     // 存储路径
    ],

    // 文件保留时间（小时）
    'retention_hours' => env('EXPORT_RETENTION_HOURS', 24),

    // 队列配置
    'queue' => [
        'connection' => env('EXPORT_QUEUE_CONNECTION', 'database'),
        'name' => env('EXPORT_QUEUE_NAME', 'exports'),
    ],

    // 并发限制（每用户）
    'concurrent_limit' => env('EXPORT_CONCURRENT_LIMIT', 3),

    // 分块大小（用于大数据量导出）
    'chunk_size' => env('EXPORT_CHUNK_SIZE', 1000),
];
```

---

## 七、性能优化策略

| 策略 | 实现方式 |
|------|----------|
| 分块查询 | 使用 `chunk()` 或 `cursor()` 避免内存溢出 |
| 流式写入 | 使用 `maatwebsite/excel` 的 `FromQuery` + `ShouldQueue` |
| 队列隔离 | 导出任务使用独立队列 `exports` |
| 文件清理 | 定时任务 `export:clean` 清理过期文件 |
| 并发限制 | 限制单用户同时进行的导出任务数为3 |

---

## 八、开发任务清单

### 8.1 基础设施

- [x] 安装 phpoffice/phpspreadsheet 包 (maatwebsite/excel 不兼容 Laravel 12)
- [x] 创建 config/export.php 配置文件
- [x] 创建 export_tasks 表迁移
- [x] 创建 ExportTask 模型

### 8.2 核心服务

- [x] 创建 Exportable Trait
- [x] 创建 ExportService 服务类
- [x] 创建 ExportJob 队列任务
- [x] 创建 ExportCompleted 邮件模板

### 8.3 API 接口

- [x] 创建 ExportController 控制器
- [x] 创建 ExportRequest 验证类
- [x] 添加导出相关路由

### 8.4 业务集成

- [x] User 模型添加 Exportable
- [x] UserController 添加 export 方法
- [ ] 其他模型添加 Exportable（Role, Menu, Department, Position）

### 8.5 定时任务

- [x] 创建 CleanExpiredExports 命令
- [x] 注册定时任务调度

---

## 九、更新记录

| 日期 | 内容 |
|------|------|
| 2026-01-30 | 初始化设计文档 |
| 2026-01-30 | 完成核心功能开发（使用 phpspreadsheet 替代 maatwebsite/excel） |
