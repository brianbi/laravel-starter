# 审批工作流模块设计文档

> 为后台管理系统提供完整的审批工作流能力

## 一、需求概述

### 1.1 功能目标

| 项目 | 说明 |
|------|------|
| 流程设计 | 可视化设计器（前端拖拽） |
| 审批模式 | 完整 BPMN 2.0（顺序签/会签/或签/并行网关/包容网关） |
| 条件分支 | 根据表单字段值走不同流程分支 |
| 字段权限 | 节点级别的字段可见/可编辑权限控制 |
| 时限处理 | 超时自动处理（通过/拒绝/转办）+ 提醒通知 |
| 单据来源 | 内置表单设计器 + 绑定业务模型 |
| 可扩展性 | 开发者可自定义节点类型 |

### 1.2 审批人配置类型

| 类型 | 说明 |
|------|------|
| `user` | 指定具体用户 |
| `role` | 指定角色（角色内用户都可审批） |
| `department` | 指定部门（部门内用户都可审批） |
| `superior` | 发起人上级（支持 N 级） |
| `dept_leader` | 部门负责人（发起人部门/指定部门） |
| `form_field` | 从表单字段获取审批人 |
| `self` | 发起人自己 |

### 1.3 审批操作

| 操作 | 说明 |
|------|------|
| `approve` | 通过 |
| `reject` | 拒绝（终止流程） |
| `return` | 退回（到任意节点或发起人） |
| `delegate` | 转办（转给他人处理，自己不再处理） |
| `add_sign` | 加签（前加签/后加签/并行加签） |
| `withdraw` | 撤回（发起人撤回） |

### 1.4 节点类型

| 类型 | 说明 |
|------|------|
| `start` | 开始节点 |
| `end` | 结束节点 |
| `approval` | 审批节点（顺序签） |
| `approval_all` | 会签节点（所有人通过才通过） |
| `approval_any` | 或签节点（一人通过即通过） |
| `condition` | 条件分支网关 |
| `parallel` | 并行网关 |
| `inclusive` | 包容网关 |
| `cc` | 抄送节点 |
| `custom` | 自定义节点（开发者扩展） |

---

## 二、架构设计

### 2.1 整体流程

```
┌─────────────────────────────────────────────────────────────┐
│                    可视化流程设计器（前端）                    │
│  - 拖拽节点、连线                                            │
│  - 配置节点属性（审批人、字段权限、时限）                       │
│  - 配置条件表达式                                            │
└─────────────────────────┬───────────────────────────────────┘
                          │ 保存流程定义 JSON
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                 流程定义 workflow_definitions                │
│  - 版本管理                                                   │
│  - 节点定义、连线定义                                         │
│  - 审批人规则、字段权限规则                                    │
└─────────────────────────┬───────────────────────────────────┘
                          │ 发起流程
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                 流程实例 workflow_instances                   │
│  - 关联表单数据或业务单据                                     │
│  - 当前节点、流程状态                                         │
└─────────────────────────┬───────────────────────────────────┘
                          │
          ┌───────────────┼───────────────┐
          ▼               ▼               ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│ 任务 tasks   │  │ 审批记录     │  │ 时限调度     │
│ - 待办任务   │  │ - 操作历史   │  │ - 超时处理   │
└──────────────┘  └──────────────┘  └──────────────┘
```

### 2.2 核心组件

| 组件 | 文件路径 | 职责 |
|------|----------|------|
| 配置文件 | `config/workflow.php` | 工作流配置 |
| WorkflowDefinition | `app/Models/WorkflowDefinition.php` | 流程定义模型 |
| WorkflowInstance | `app/Models/WorkflowInstance.php` | 流程实例模型 |
| WorkflowTask | `app/Models/WorkflowTask.php` | 任务模型 |
| WorkflowRecord | `app/Models/WorkflowRecord.php` | 审批记录模型 |
| WorkflowEngine | `app/Services/Workflow/WorkflowEngine.php` | 流程引擎核心 |
| NodeExecutor | `app/Services/Workflow/Executors/` | 节点执行器 |
| AssigneeResolver | `app/Services/Workflow/Resolvers/` | 审批人解析器 |
| WorkflowController | `app/Http/Controllers/Admin/WorkflowController.php` | API 控制器 |

### 2.3 可扩展节点架构

```
┌─────────────────────────────────────────────────────────────┐
│                    NodeExecutorInterface                     │
│  + execute(WorkflowInstance, Node): void                    │
│  + complete(WorkflowTask, action, data): void               │
│  + getType(): string                                         │
│  + getConfigSchema(): array                                  │
└─────────────────────────┬───────────────────────────────────┘
                          │
        ┌─────────────────┼─────────────────┐
        ▼                 ▼                 ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│ ApprovalNode │  │ ConditionNode│  │ CustomNode   │
│ - 审批逻辑   │  │ - 条件判断   │  │ - 开发者扩展 │
└──────────────┘  └──────────────┘  └──────────────┘
```

---

## 三、数据库设计

### 3.1 workflow_definitions（流程定义）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| code | string(50) | 流程编码（唯一） |
| name | string(100) | 流程名称 |
| description | string | 流程描述 |
| form_type | string(20) | 表单类型：builtin/model |
| form_id | bigint | 内置表单ID（form_type=builtin时） |
| model_class | string(200) | 业务模型类名（form_type=model时） |
| version | int | 版本号 |
| graph | json | 流程图定义（节点、连线） |
| status | tinyint | 状态：0禁用 1启用 |
| created_by | bigint | 创建人 |
| created_at | timestamp | 创建时间 |
| updated_at | timestamp | 更新时间 |

### 3.2 workflow_definition_nodes（流程节点定义）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| definition_id | bigint | 流程定义ID |
| node_id | string(50) | 节点标识（流程内唯一） |
| type | string(30) | 节点类型 |
| name | string(100) | 节点名称 |
| config | json | 节点配置（审批人规则等） |
| field_permissions | json | 字段权限配置 |
| timeout_config | json | 超时配置 |
| sort | int | 排序号 |

### 3.3 workflow_instances（流程实例）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| definition_id | bigint | 流程定义ID |
| definition_version | int | 使用的流程版本 |
| business_type | string(100) | 业务类型 |
| business_id | bigint | 业务ID |
| form_data | json | 表单数据（内置表单时） |
| initiator_id | bigint | 发起人ID |
| current_node_id | string(50) | 当前节点ID |
| status | tinyint | 状态：0草稿 1进行中 2已通过 3已拒绝 4已撤回 |
| started_at | timestamp | 发起时间 |
| completed_at | timestamp | 完成时间 |
| created_at | timestamp | 创建时间 |
| updated_at | timestamp | 更新时间 |

### 3.4 workflow_tasks（待办任务）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| instance_id | bigint | 流程实例ID |
| node_id | string(50) | 节点ID |
| node_type | string(30) | 节点类型 |
| assignee_id | bigint | 指派人ID |
| assignee_type | string(20) | 指派类型：user/role/department |
| status | tinyint | 状态：0待处理 1已处理 2已转办 3已取消 |
| action | string(20) | 处理动作 |
| comment | text | 审批意见 |
| delegate_from | bigint | 委托来源（代理时） |
| timeout_at | timestamp | 超时时间 |
| processed_at | timestamp | 处理时间 |
| created_at | timestamp | 创建时间 |

### 3.5 workflow_records（审批记录）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| instance_id | bigint | 流程实例ID |
| task_id | bigint | 任务ID |
| node_id | string(50) | 节点ID |
| node_name | string(100) | 节点名称 |
| user_id | bigint | 操作人ID |
| action | string(20) | 操作类型 |
| comment | text | 审批意见 |
| form_data | json | 修改的表单数据（如有） |
| created_at | timestamp | 创建时间 |

### 3.6 workflow_delegates（代理委托配置）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| user_id | bigint | 委托人ID |
| delegate_id | bigint | 代理人ID |
| definition_id | bigint | 指定流程（空=全部） |
| start_at | timestamp | 开始时间 |
| end_at | timestamp | 结束时间 |
| status | tinyint | 状态：0禁用 1启用 |
| created_at | timestamp | 创建时间 |

### 3.7 form_definitions（表单定义）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| code | string(50) | 表单编码 |
| name | string(100) | 表单名称 |
| fields | json | 字段定义 |
| rules | json | 验证规则 |
| status | tinyint | 状态 |
| created_at | timestamp | 创建时间 |
| updated_at | timestamp | 更新时间 |

---

## 四、API 设计

### 4.1 流程定义管理

```
GET    /api/admin/workflow/definitions              - 流程定义列表
POST   /api/admin/workflow/definitions              - 创建流程定义
GET    /api/admin/workflow/definitions/{id}         - 流程定义详情
PUT    /api/admin/workflow/definitions/{id}         - 更新流程定义
DELETE /api/admin/workflow/definitions/{id}         - 删除流程定义
POST   /api/admin/workflow/definitions/{id}/publish - 发布新版本
GET    /api/admin/workflow/definitions/{id}/versions - 版本历史
```

### 4.2 流程实例操作

```
POST   /api/admin/workflow/instances                - 发起流程
GET    /api/admin/workflow/instances/{id}           - 流程详情（含审批记录）
POST   /api/admin/workflow/instances/{id}/withdraw  - 撤回流程
GET    /api/admin/workflow/instances/{id}/timeline  - 流程时间线
```

### 4.3 任务处理

```
GET    /api/admin/workflow/tasks                    - 我的待办任务
GET    /api/admin/workflow/tasks/done               - 我的已办任务
GET    /api/admin/workflow/tasks/initiated          - 我发起的流程
POST   /api/admin/workflow/tasks/{id}/approve       - 通过
POST   /api/admin/workflow/tasks/{id}/reject        - 拒绝
POST   /api/admin/workflow/tasks/{id}/return        - 退回
POST   /api/admin/workflow/tasks/{id}/delegate      - 转办
POST   /api/admin/workflow/tasks/{id}/add-sign      - 加签
```

### 4.4 代理委托

```
GET    /api/admin/workflow/delegates                - 代理配置列表
POST   /api/admin/workflow/delegates                - 创建代理
PUT    /api/admin/workflow/delegates/{id}           - 更新代理
DELETE /api/admin/workflow/delegates/{id}           - 删除代理
```

### 4.5 表单定义（可选）

```
GET    /api/admin/workflow/forms                    - 表单列表
POST   /api/admin/workflow/forms                    - 创建表单
GET    /api/admin/workflow/forms/{id}               - 表单详情
PUT    /api/admin/workflow/forms/{id}               - 更新表单
DELETE /api/admin/workflow/forms/{id}               - 删除表单
```

---

## 五、节点执行器接口

### 5.1 接口定义

```php
interface NodeExecutorInterface
{
    /**
     * 获取节点类型标识
     */
    public function getType(): string;

    /**
     * 获取节点显示名称
     */
    public function getName(): string;

    /**
     * 获取节点配置 Schema（用于前端表单生成）
     */
    public function getConfigSchema(): array;

    /**
     * 执行节点（进入节点时调用）
     */
    public function execute(WorkflowInstance $instance, array $node): void;

    /**
     * 完成任务（处理任务时调用）
     */
    public function complete(WorkflowTask $task, string $action, array $data): void;

    /**
     * 判断节点是否可以自动完成
     */
    public function canAutoComplete(WorkflowInstance $instance, array $node): bool;
}
```

### 5.2 自定义节点示例

```php
namespace App\Workflow\Nodes;

use App\Services\Workflow\Contracts\NodeExecutorInterface;

class NotifyNode implements NodeExecutorInterface
{
    public function getType(): string
    {
        return 'notify';
    }

    public function getName(): string
    {
        return '通知节点';
    }

    public function getConfigSchema(): array
    {
        return [
            'notify_type' => [
                'type' => 'select',
                'label' => '通知方式',
                'options' => ['email' => '邮件', 'sms' => '短信', 'dingtalk' => '钉钉'],
            ],
            'template_id' => [
                'type' => 'select',
                'label' => '通知模板',
                'source' => '/api/admin/notification-templates',
            ],
            'recipients' => [
                'type' => 'assignee',
                'label' => '接收人',
            ],
        ];
    }

    public function execute(WorkflowInstance $instance, array $node): void
    {
        // 发送通知
        $config = $node['config'];
        // ... 发送逻辑
        
        // 自动流转到下一节点
        app(WorkflowEngine::class)->moveToNext($instance, $node['id']);
    }

    public function complete(WorkflowTask $task, string $action, array $data): void
    {
        // 通知节点无需人工处理，此方法不会被调用
    }

    public function canAutoComplete(WorkflowInstance $instance, array $node): bool
    {
        return true; // 通知节点自动完成
    }
}
```

### 5.3 注册自定义节点

```php
// config/workflow.php
return [
    'nodes' => [
        // 内置节点
        'start' => \App\Services\Workflow\Nodes\StartNode::class,
        'end' => \App\Services\Workflow\Nodes\EndNode::class,
        'approval' => \App\Services\Workflow\Nodes\ApprovalNode::class,
        // ...
        
        // 自定义节点
        'notify' => \App\Workflow\Nodes\NotifyNode::class,
        'http_call' => \App\Workflow\Nodes\HttpCallNode::class,
    ],
];

// 或在 ServiceProvider 中注册
app('workflow')->registerNode('notify', NotifyNode::class);
```

---

## 六、审批人解析器

### 6.1 解析器接口

```php
interface AssigneeResolverInterface
{
    public function getType(): string;
    
    public function resolve(WorkflowInstance $instance, array $config): array;
}
```

### 6.2 内置解析器

| 类型 | 类名 | 说明 |
|------|------|------|
| user | UserAssigneeResolver | 指定用户 |
| role | RoleAssigneeResolver | 指定角色 |
| department | DepartmentAssigneeResolver | 指定部门 |
| superior | SuperiorAssigneeResolver | 发起人上级 |
| dept_leader | DeptLeaderAssigneeResolver | 部门负责人 |
| form_field | FormFieldAssigneeResolver | 表单字段 |
| self | SelfAssigneeResolver | 发起人自己 |

---

## 七、流程图 JSON 结构

```json
{
  "nodes": [
    {
      "id": "start_1",
      "type": "start",
      "name": "开始",
      "x": 100,
      "y": 200
    },
    {
      "id": "approval_1",
      "type": "approval",
      "name": "部门经理审批",
      "x": 300,
      "y": 200,
      "config": {
        "assignee_type": "superior",
        "assignee_config": { "level": 1 },
        "multi_instance": "sequential"
      },
      "field_permissions": {
        "amount": { "visible": true, "editable": false },
        "reason": { "visible": true, "editable": true }
      },
      "timeout": {
        "enabled": true,
        "hours": 24,
        "action": "auto_approve",
        "notify": true
      }
    },
    {
      "id": "condition_1",
      "type": "condition",
      "name": "金额判断",
      "x": 500,
      "y": 200,
      "config": {
        "conditions": [
          { "expression": "amount > 10000", "target": "approval_2" },
          { "expression": "default", "target": "end_1" }
        ]
      }
    },
    {
      "id": "end_1",
      "type": "end",
      "name": "结束",
      "x": 700,
      "y": 200
    }
  ],
  "edges": [
    { "source": "start_1", "target": "approval_1" },
    { "source": "approval_1", "target": "condition_1" },
    { "source": "condition_1", "target": "approval_2", "condition": "amount > 10000" },
    { "source": "condition_1", "target": "end_1", "condition": "default" }
  ]
}
```

---

## 八、开发任务清单

### 8.1 阶段一：核心引擎

- [ ] 创建 config/workflow.php 配置文件
- [ ] 创建 workflow_definitions 表迁移
- [ ] 创建 workflow_definition_nodes 表迁移
- [ ] 创建 workflow_instances 表迁移
- [ ] 创建 workflow_tasks 表迁移
- [ ] 创建 workflow_records 表迁移
- [ ] 创建对应的 Model 类
- [ ] 创建 NodeExecutorInterface 接口
- [ ] 实现 StartNode、EndNode 节点执行器
- [ ] 实现 ApprovalNode（顺序签）节点执行器
- [ ] 创建 WorkflowEngine 核心引擎
- [ ] 创建 WorkflowService 服务类

### 8.2 阶段二：审批人解析

- [ ] 创建 AssigneeResolverInterface 接口
- [ ] 实现 UserAssigneeResolver（指定用户）
- [ ] 实现 RoleAssigneeResolver（指定角色）
- [ ] 实现 DepartmentAssigneeResolver（指定部门）
- [ ] 实现 SuperiorAssigneeResolver（上级）
- [ ] 实现 DeptLeaderAssigneeResolver（部门负责人）
- [ ] 实现 FormFieldAssigneeResolver（表单字段）
- [ ] 实现 SelfAssigneeResolver（发起人）

### 8.3 阶段三：高级节点

- [ ] 实现 ApprovalAllNode（会签）
- [ ] 实现 ApprovalAnyNode（或签）
- [ ] 实现 ConditionNode（条件分支）
- [ ] 实现 ParallelNode（并行网关）
- [ ] 实现 InclusiveNode（包容网关）
- [ ] 实现 CcNode（抄送）

### 8.4 阶段四：高级操作

- [ ] 实现退回功能（return）
- [ ] 实现转办功能（delegate）
- [ ] 实现加签功能（add_sign）
- [ ] 实现撤回功能（withdraw）
- [ ] 创建 workflow_delegates 表迁移
- [ ] 实现代理委托功能

### 8.5 阶段五：时限与通知

- [ ] 实现超时配置解析
- [ ] 创建 WorkflowTimeoutJob 任务
- [ ] 实现超时自动处理
- [ ] 集成通知中心发送审批通知
- [ ] 创建定时任务调度

### 8.6 阶段六：API 接口

- [ ] 创建 WorkflowDefinitionController
- [ ] 创建 WorkflowInstanceController
- [ ] 创建 WorkflowTaskController
- [ ] 创建 WorkflowDelegateController
- [ ] 添加工作流相关路由
- [ ] 创建请求验证类

### 8.7 阶段七：表单设计器（可选）

- [ ] 创建 form_definitions 表迁移
- [ ] 创建 FormDefinition 模型
- [ ] 创建 FormController 控制器
- [ ] 实现表单数据存储与验证

---

## 九、配置文件结构

```php
// config/workflow.php
return [
    // 节点执行器
    'nodes' => [
        'start' => \App\Services\Workflow\Nodes\StartNode::class,
        'end' => \App\Services\Workflow\Nodes\EndNode::class,
        'approval' => \App\Services\Workflow\Nodes\ApprovalNode::class,
        'approval_all' => \App\Services\Workflow\Nodes\ApprovalAllNode::class,
        'approval_any' => \App\Services\Workflow\Nodes\ApprovalAnyNode::class,
        'condition' => \App\Services\Workflow\Nodes\ConditionNode::class,
        'parallel' => \App\Services\Workflow\Nodes\ParallelNode::class,
        'inclusive' => \App\Services\Workflow\Nodes\InclusiveNode::class,
        'cc' => \App\Services\Workflow\Nodes\CcNode::class,
    ],

    // 审批人解析器
    'assignee_resolvers' => [
        'user' => \App\Services\Workflow\Resolvers\UserAssigneeResolver::class,
        'role' => \App\Services\Workflow\Resolvers\RoleAssigneeResolver::class,
        'department' => \App\Services\Workflow\Resolvers\DepartmentAssigneeResolver::class,
        'superior' => \App\Services\Workflow\Resolvers\SuperiorAssigneeResolver::class,
        'dept_leader' => \App\Services\Workflow\Resolvers\DeptLeaderAssigneeResolver::class,
        'form_field' => \App\Services\Workflow\Resolvers\FormFieldAssigneeResolver::class,
        'self' => \App\Services\Workflow\Resolvers\SelfAssigneeResolver::class,
    ],

    // 队列配置
    'queue' => [
        'connection' => env('WORKFLOW_QUEUE_CONNECTION', 'database'),
        'name' => env('WORKFLOW_QUEUE_NAME', 'workflow'),
    ],

    // 超时检查间隔（分钟）
    'timeout_check_interval' => 5,
];
```

---

## 十、更新记录

| 日期 | 内容 |
|------|------|
| 2026-01-30 | 初始化设计文档 |
