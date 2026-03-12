# 通知中心模块设计文档

> 为后台管理系统提供统一的通知管理能力

## 一、需求概述

### 1.1 功能目标

| 项目 | 说明 |
|------|------|
| 系统通知 | 站内消息通知（导出完成、审批提醒等） |
| 通知渠道 | 支持多渠道：站内信、邮件、钉钉、企业微信、短信等 |
| 通知记录 | 记录所有通知发送历史，便于追溯 |
| 模板管理 | 可配置的通知模板 |
| 用户偏好 | 用户可设置接收偏好 |
| 已读状态 | 站内通知支持已读/未读状态 |
| 批量操作 | 支持批量已读、批量删除 |

### 1.2 通知类型

| 类型 | 说明 | 渠道 |
|------|------|------|
| 系统通知 | 系统级别的通知（公告、维护等） | 站内信 |
| 业务通知 | 业务相关通知（导出完成、审批等） | 站内信 + 邮件 |
| 告警通知 | 系统告警（异常、错误等） | 邮件 + 钉钉 |

---

## 二、架构设计

### 2.1 整体流程

```
┌─────────────────────────────────────────────────────────┐
│                    业务触发通知                          │
│  NotificationService::send($user, $notification)        │
└─────────────────────┬───────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────┐
│                NotificationService                       │
│  - 解析通知类型                                          │
│  - 确定发送渠道                                          │
│  - 获取用户偏好                                          │
└─────────────────────┬───────────────────────────────────┘
                      │
        ┌─────────────┼─────────────┐
        ▼             ▼             ▼
┌───────────┐  ┌───────────┐  ┌───────────┐
│  站内信   │  │   邮件    │  │  钉钉等   │
│  Channel  │  │  Channel  │  │  Channel  │
└─────┬─────┘  └─────┬─────┘  └─────┬─────┘
      │              │              │
      ▼              ▼              ▼
┌─────────────────────────────────────────────────────────┐
│              notification_records 表                     │
│            记录所有通知发送历史                           │
└─────────────────────────────────────────────────────────┘
```

### 2.2 核心组件

| 组件 | 文件路径 | 职责 |
|------|----------|------|
| 配置文件 | `config/notification.php` | 通知渠道配置 |
| 通知表 | `notifications` | 站内通知（使用 Laravel 内置） |
| 通知记录表 | `notification_records` | 所有渠道的发送记录 |
| 通知模板表 | `notification_templates` | 通知模板管理 |
| NotificationRecord 模型 | `app/Models/NotificationRecord.php` | 通知记录模型 |
| NotificationTemplate 模型 | `app/Models/NotificationTemplate.php` | 通知模板模型 |
| NotificationService | `app/Services/NotificationService.php` | 通知服务 |
| 渠道基类 | `app/Notifications/Channels/BaseChannel.php` | 渠道抽象类 |
| 邮件渠道 | `app/Notifications/Channels/MailChannel.php` | 邮件发送 |
| 钉钉渠道 | `app/Notifications/Channels/DingTalkChannel.php` | 钉钉发送 |
| NotificationController | `app/Http/Controllers/Admin/NotificationController.php` | 通知API |

---

## 三、数据库设计

### 3.1 notification_records 表（通知发送记录）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| user_id | bigint | 接收用户ID（可为空，系统通知） |
| channel | string(20) | 渠道：database/mail/dingtalk/wechat/sms |
| type | string(50) | 通知类型类名 |
| title | string(200) | 通知标题 |
| content | text | 通知内容 |
| data | json | 附加数据 |
| status | tinyint | 状态：0待发送 1已发送 2发送失败 |
| error_message | string | 失败原因 |
| sent_at | timestamp | 发送时间 |
| created_at | timestamp | 创建时间 |

### 3.2 notification_templates 表（通知模板）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| code | string(50) | 模板编码（唯一） |
| name | string(100) | 模板名称 |
| channel | string(20) | 适用渠道 |
| title_template | string(200) | 标题模板 |
| content_template | text | 内容模板 |
| variables | json | 可用变量说明 |
| status | tinyint | 状态：0禁用 1启用 |
| remark | string | 备注 |
| created_at | timestamp | 创建时间 |
| updated_at | timestamp | 更新时间 |

### 3.3 使用 Laravel 内置 notifications 表

Laravel 自带的 `notifications` 表用于站内通知，包含已读状态管理。

---

## 四、API 设计

### 4.1 获取我的通知列表（站内信）

```
GET /api/admin/notifications

参数：
- type: 通知类型筛选
- read: 0未读 / 1已读 / 不传全部

响应：
{
    "code": 200,
    "data": {
        "items": [...],
        "unread_count": 5
    }
}
```

### 4.2 标记为已读

```
PUT /api/admin/notifications/{id}/read

批量：
PUT /api/admin/notifications/read
Body: { "ids": [1,2,3] }  // 不传ids则全部已读
```

### 4.3 删除通知

```
DELETE /api/admin/notifications/{id}

批量：
DELETE /api/admin/notifications
Body: { "ids": [1,2,3] }
```

### 4.4 获取未读数量

```
GET /api/admin/notifications/unread-count

响应：
{
    "code": 200,
    "data": { "count": 5 }
}
```

### 4.5 通知发送记录（管理员）

```
GET /api/admin/notification-records

参数：
- channel: 渠道筛选
- status: 状态筛选
- user_id: 用户筛选
```

### 4.6 通知模板管理

```
GET    /api/admin/notification-templates          - 列表
POST   /api/admin/notification-templates          - 创建
PUT    /api/admin/notification-templates/{id}     - 更新
DELETE /api/admin/notification-templates/{id}     - 删除
```

---

## 五、通知类实现

### 5.1 基础通知类

```php
namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ExportCompletedNotification extends Notification
{
    public function __construct(
        public ExportTask $task
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => '导出任务完成',
            'content' => "您的导出任务已完成，共导出 {$this->task->total_count} 条数据",
            'type' => 'export_completed',
            'data' => [
                'task_id' => $this->task->uuid,
                'download_url' => route('admin.export.download', $this->task->uuid),
            ],
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('导出任务完成通知')
            ->view('emails.export-completed', ['task' => $this->task]);
    }
}
```

### 5.2 发送通知

```php
// 使用 Laravel Notification
$user->notify(new ExportCompletedNotification($task));

// 或使用 NotificationService（带记录）
app(NotificationService::class)->send(
    $user,
    new ExportCompletedNotification($task)
);
```

---

## 六、配置文件

### 6.1 config/notification.php

```php
return [
    // 可用渠道
    'channels' => [
        'database' => [
            'enabled' => true,
        ],
        'mail' => [
            'enabled' => env('NOTIFICATION_MAIL_ENABLED', true),
        ],
        'dingtalk' => [
            'enabled' => env('NOTIFICATION_DINGTALK_ENABLED', false),
            'webhook' => env('DINGTALK_WEBHOOK'),
            'secret' => env('DINGTALK_SECRET'),
        ],
        'wechat' => [
            'enabled' => env('NOTIFICATION_WECHAT_ENABLED', false),
            'corp_id' => env('WECHAT_CORP_ID'),
            'agent_id' => env('WECHAT_AGENT_ID'),
            'secret' => env('WECHAT_SECRET'),
        ],
    ],

    // 默认渠道（按通知类型）
    'defaults' => [
        'system' => ['database'],
        'business' => ['database', 'mail'],
        'alert' => ['mail', 'dingtalk'],
    ],

    // 记录保留天数
    'record_retention_days' => 90,
];
```

---

## 七、开发任务清单

### 7.1 基础设施

- [x] 创建 config/notification.php 配置文件
- [x] 创建 Laravel notifications 表迁移
- [x] 创建 notification_records 表迁移
- [x] 创建 notification_templates 表迁移
- [x] 创建 NotificationRecord 模型
- [x] 创建 NotificationTemplate 模型

### 7.2 核心服务

- [x] 创建 NotificationService 服务类
- [x] 创建 BaseNotification 基础通知类
- [x] 创建 ExportCompletedNotification 通知类
- [x] 修改 ExportJob 使用新通知系统

### 7.3 渠道实现

- [x] 实现 DatabaseChannel（Laravel 内置）
- [x] 实现 DingTalkChannel 钉钉渠道
- [ ] 实现 WeChatChannel 企业微信渠道（可选）

### 7.4 API 接口

- [x] 创建 NotificationController 控制器
- [x] 创建 NotificationTemplateController 控制器
- [x] 创建 NotificationRecordController 控制器
- [x] 添加通知相关路由

### 7.5 定时任务

- [x] 创建 CleanNotificationRecords 命令
- [x] 注册定时任务

---

## 八、更新记录

| 日期 | 内容 |
|------|------|
| 2026-01-30 | 初始化设计文档 |
| 2026-01-30 | 完成核心功能开发 |
