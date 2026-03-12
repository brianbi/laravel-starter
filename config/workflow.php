<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 节点执行器
    |--------------------------------------------------------------------------
    |
    | 注册工作流节点类型及其对应的执行器类。
    | 开发者可以添加自定义节点类型到此配置。
    |
    */
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

    /*
    |--------------------------------------------------------------------------
    | 审批人解析器
    |--------------------------------------------------------------------------
    |
    | 注册审批人解析类型及其对应的解析器类。
    | 用于根据配置规则解析出具体的审批人。
    |
    */
    'assignee_resolvers' => [
        'user' => \App\Services\Workflow\Resolvers\UserAssigneeResolver::class,
        'role' => \App\Services\Workflow\Resolvers\RoleAssigneeResolver::class,
        'department' => \App\Services\Workflow\Resolvers\DepartmentAssigneeResolver::class,
        'superior' => \App\Services\Workflow\Resolvers\SuperiorAssigneeResolver::class,
        'dept_leader' => \App\Services\Workflow\Resolvers\DeptLeaderAssigneeResolver::class,
        'form_field' => \App\Services\Workflow\Resolvers\FormFieldAssigneeResolver::class,
        'self' => \App\Services\Workflow\Resolvers\SelfAssigneeResolver::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | 队列配置
    |--------------------------------------------------------------------------
    |
    | 工作流相关任务的队列配置。
    |
    */
    'queue' => [
        'connection' => env('WORKFLOW_QUEUE_CONNECTION', 'database'),
        'name' => env('WORKFLOW_QUEUE_NAME', 'workflow'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 超时检查间隔
    |--------------------------------------------------------------------------
    |
    | 超时任务检查的间隔时间（分钟）。
    |
    */
    'timeout_check_interval' => env('WORKFLOW_TIMEOUT_CHECK_INTERVAL', 5),

    /*
    |--------------------------------------------------------------------------
    | 流程实例状态
    |--------------------------------------------------------------------------
    */
    'instance_status' => [
        'draft' => 0,       // 草稿
        'running' => 1,     // 进行中
        'approved' => 2,    // 已通过
        'rejected' => 3,    // 已拒绝
        'withdrawn' => 4,   // 已撤回
    ],

    /*
    |--------------------------------------------------------------------------
    | 任务状态
    |--------------------------------------------------------------------------
    */
    'task_status' => [
        'pending' => 0,     // 待处理
        'processed' => 1,   // 已处理
        'delegated' => 2,   // 已转办
        'cancelled' => 3,   // 已取消
    ],

    /*
    |--------------------------------------------------------------------------
    | 审批操作
    |--------------------------------------------------------------------------
    */
    'actions' => [
        'submit' => 'submit',       // 提交
        'approve' => 'approve',     // 通过
        'reject' => 'reject',       // 拒绝
        'return' => 'return',       // 退回
        'delegate' => 'delegate',   // 转办
        'add_sign' => 'add_sign',   // 加签
        'withdraw' => 'withdraw',   // 撤回
        'cc' => 'cc',               // 抄送
    ],

    /*
    |--------------------------------------------------------------------------
    | 加签类型
    |--------------------------------------------------------------------------
    */
    'add_sign_types' => [
        'before' => 'before',       // 前加签
        'after' => 'after',         // 后加签
        'parallel' => 'parallel',   // 并行加签
    ],
];
