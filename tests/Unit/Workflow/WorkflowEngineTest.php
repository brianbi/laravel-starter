<?php

namespace Tests\Unit\Workflow;

use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Models\WorkflowTask;
use App\Services\Workflow\WorkflowEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowEngineTest extends TestCase
{
    use RefreshDatabase;

    protected WorkflowEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = app(WorkflowEngine::class);
    }

    /**
     * 测试启动简单审批流程
     */
    public function test_can_start_simple_workflow(): void
    {
        $user = User::factory()->create();
        $approver = User::factory()->create();

        $definition = WorkflowDefinition::factory()
            ->withApprover($approver->id)
            ->create();

        $formData = [
            'title' => '测试申请',
            'amount' => 1000,
            'reason' => '测试原因',
        ];

        $instance = $this->engine->start($definition, $formData, $user->id);

        $this->assertInstanceOf(WorkflowInstance::class, $instance);
        $this->assertEquals(WorkflowInstance::STATUS_RUNNING, $instance->status);
        $this->assertEquals($user->id, $instance->initiator_id);
        $this->assertEquals($formData, $instance->form_data);
    }

    /**
     * 测试任务创建
     */
    public function test_creates_task_for_approver(): void
    {
        $user = User::factory()->create();
        $approver = User::factory()->create();

        $definition = WorkflowDefinition::factory()
            ->withApprover($approver->id)
            ->create();

        $instance = $this->engine->start($definition, ['title' => '测试'], $user->id);

        $task = WorkflowTask::where('instance_id', $instance->id)
            ->where('assignee_id', $approver->id)
            ->first();

        $this->assertNotNull($task);
        $this->assertEquals(WorkflowTask::STATUS_PENDING, $task->status);
        $this->assertEquals('approval_1', $task->node_id);
    }

    /**
     * 测试审批通过
     */
    public function test_can_approve_task(): void
    {
        $user = User::factory()->create();
        $approver = User::factory()->create();

        $definition = WorkflowDefinition::factory()
            ->withApprover($approver->id)
            ->create();

        $instance = $this->engine->start($definition, ['title' => '测试'], $user->id);

        $task = WorkflowTask::where('instance_id', $instance->id)
            ->where('status', WorkflowTask::STATUS_PENDING)
            ->first();

        $this->engine->completeTask($task, 'approve', [
            'comment' => '同意',
            'user_id' => $approver->id,
        ]);

        $instance->refresh();
        $task->refresh();

        $this->assertEquals(WorkflowInstance::STATUS_APPROVED, $instance->status);
        $this->assertEquals(WorkflowTask::STATUS_COMPLETED, $task->status);
        $this->assertEquals('approve', $task->action);
    }

    /**
     * 测试审批拒绝
     */
    public function test_can_reject_task(): void
    {
        $user = User::factory()->create();
        $approver = User::factory()->create();

        $definition = WorkflowDefinition::factory()
            ->withApprover($approver->id)
            ->create();

        $instance = $this->engine->start($definition, ['title' => '测试'], $user->id);

        $task = WorkflowTask::where('instance_id', $instance->id)
            ->where('status', WorkflowTask::STATUS_PENDING)
            ->first();

        $this->engine->completeTask($task, 'reject', [
            'comment' => '不同意',
            'user_id' => $approver->id,
        ]);

        $instance->refresh();
        $task->refresh();

        $this->assertEquals(WorkflowInstance::STATUS_REJECTED, $instance->status);
        $this->assertEquals(WorkflowTask::STATUS_COMPLETED, $task->status);
        $this->assertEquals('reject', $task->action);
    }

    /**
     * 测试撤回流程
     */
    public function test_can_withdraw_workflow(): void
    {
        $user = User::factory()->create();
        $approver = User::factory()->create();

        $definition = WorkflowDefinition::factory()
            ->withApprover($approver->id)
            ->create();

        $instance = $this->engine->start($definition, ['title' => '测试'], $user->id);

        $this->engine->withdraw($instance, $user->id, ['reason' => '撤回原因']);

        $instance->refresh();

        $this->assertEquals(WorkflowInstance::STATUS_WITHDRAWN, $instance->status);

        // 检查待办任务已取消
        $pendingTasks = WorkflowTask::where('instance_id', $instance->id)
            ->where('status', WorkflowTask::STATUS_PENDING)
            ->count();

        $this->assertEquals(0, $pendingTasks);
    }

    /**
     * 测试条件分支 - 金额大于阈值走审批
     */
    public function test_condition_node_routes_to_approval(): void
    {
        $user = User::factory()->create();
        $approver = User::factory()->create();

        $definition = WorkflowDefinition::factory()
            ->withCondition()
            ->withApprover($approver->id)
            ->create();

        // 金额大于1000应该走审批
        $instance = $this->engine->start($definition, ['amount' => 5000], $user->id);

        $task = WorkflowTask::where('instance_id', $instance->id)
            ->where('status', WorkflowTask::STATUS_PENDING)
            ->first();

        $this->assertNotNull($task);
        $this->assertEquals('approval_1', $task->node_id);
    }

    /**
     * 测试条件分支 - 金额小于阈值直接结束
     */
    public function test_condition_node_routes_to_end(): void
    {
        $user = User::factory()->create();

        $definition = WorkflowDefinition::factory()
            ->withCondition()
            ->create();

        // 金额小于等于1000应该直接结束
        $instance = $this->engine->start($definition, ['amount' => 500], $user->id);

        $instance->refresh();

        $this->assertEquals(WorkflowInstance::STATUS_APPROVED, $instance->status);
        $this->assertEquals('end_1', $instance->current_node_id);
    }
}
