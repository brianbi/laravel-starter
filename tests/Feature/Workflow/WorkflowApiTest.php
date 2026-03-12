<?php

namespace Tests\Feature\Workflow;

use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Models\WorkflowTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = auth('api')->login($this->user);
    }

    protected function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    // ==================== 流程定义 API 测试 ====================

    /**
     * 测试获取流程定义列表
     */
    public function test_can_list_workflow_definitions(): void
    {
        WorkflowDefinition::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/workflow/definitions', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'data',
                    'total',
                    'current_page',
                ],
            ]);
    }

    /**
     * 测试创建流程定义
     */
    public function test_can_create_workflow_definition(): void
    {
        $data = [
            'code' => 'leave_approval',
            'name' => '请假审批流程',
            'description' => '员工请假审批',
            'form_type' => 'model',
            'model_class' => 'App\\Models\\LeaveRequest',
            'graph' => [
                'nodes' => [
                    ['id' => 'start_1', 'type' => 'start', 'name' => '开始'],
                    ['id' => 'end_1', 'type' => 'end', 'name' => '结束'],
                ],
                'edges' => [
                    ['source' => 'start_1', 'target' => 'end_1'],
                ],
            ],
        ];

        $response = $this->postJson('/api/admin/workflow/definitions', $data, $this->authHeaders());

        $response->assertStatus(201)
            ->assertJson(['code' => 201]);

        $this->assertDatabaseHas('workflow_definitions', [
            'code' => 'leave_approval',
            'name' => '请假审批流程',
        ]);
    }

    /**
     * 测试获取流程定义详情
     */
    public function test_can_show_workflow_definition(): void
    {
        $definition = WorkflowDefinition::factory()->create();

        $response = $this->getJson("/api/admin/workflow/definitions/{$definition->id}", $this->authHeaders());

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'data' => [
                    'id' => $definition->id,
                    'code' => $definition->code,
                ],
            ]);
    }

    /**
     * 测试更新流程定义
     */
    public function test_can_update_workflow_definition(): void
    {
        $definition = WorkflowDefinition::factory()->create();

        $response = $this->putJson("/api/admin/workflow/definitions/{$definition->id}", [
            'name' => '更新后的名称',
            'form_type' => $definition->form_type,
            'model_class' => $definition->model_class,
            'graph' => $definition->graph,
        ], $this->authHeaders());

        $response->assertStatus(200);

        $this->assertDatabaseHas('workflow_definitions', [
            'id' => $definition->id,
            'name' => '更新后的名称',
        ]);
    }

    /**
     * 测试删除流程定义
     */
    public function test_can_delete_workflow_definition(): void
    {
        $definition = WorkflowDefinition::factory()->create();

        $response = $this->deleteJson("/api/admin/workflow/definitions/{$definition->id}", [], $this->authHeaders());

        $response->assertStatus(200);

        $this->assertDatabaseMissing('workflow_definitions', [
            'id' => $definition->id,
        ]);
    }

    /**
     * 测试获取节点类型列表
     */
    public function test_can_get_node_types(): void
    {
        $response = $this->getJson('/api/admin/workflow/definitions/node-types', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'data',
            ]);
    }

    // ==================== 流程实例 API 测试 ====================

    /**
     * 测试发起流程
     */
    public function test_can_start_workflow_instance(): void
    {
        $approver = User::factory()->create();
        $definition = WorkflowDefinition::factory()
            ->withApprover($approver->id)
            ->create();

        $response = $this->postJson('/api/admin/workflow/instances', [
            'definition_code' => $definition->code,
            'form_data' => [
                'title' => '测试申请',
                'amount' => 1000,
            ],
        ], $this->authHeaders());

        $response->assertStatus(201)
            ->assertJson(['code' => 201]);

        $this->assertDatabaseHas('workflow_instances', [
            'definition_id' => $definition->id,
            'initiator_id' => $this->user->id,
        ]);
    }

    /**
     * 测试获取流程实例详情
     */
    public function test_can_show_workflow_instance(): void
    {
        $instance = WorkflowInstance::factory()->create([
            'initiator_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/admin/workflow/instances/{$instance->id}", $this->authHeaders());

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'data' => [
                    'id' => $instance->id,
                ],
            ]);
    }

    /**
     * 测试撤回流程
     */
    public function test_can_withdraw_workflow(): void
    {
        $approver = User::factory()->create();
        $definition = WorkflowDefinition::factory()
            ->withApprover($approver->id)
            ->create();

        $instance = WorkflowInstance::factory()->create([
            'definition_id' => $definition->id,
            'initiator_id' => $this->user->id,
            'status' => WorkflowInstance::STATUS_RUNNING,
        ]);

        // 创建一个待办任务
        WorkflowTask::create([
            'instance_id' => $instance->id,
            'node_id' => 'approval_1',
            'node_type' => 'approval',
            'node_name' => '审批节点',
            'assignee_id' => $approver->id,
            'status' => WorkflowTask::STATUS_PENDING,
        ]);

        $response = $this->postJson("/api/admin/workflow/instances/{$instance->id}/withdraw", [
            'reason' => '撤回原因',
        ], $this->authHeaders());

        $response->assertStatus(200);

        $instance->refresh();
        $this->assertEquals(WorkflowInstance::STATUS_WITHDRAWN, $instance->status);
    }

    /**
     * 测试获取我发起的流程
     */
    public function test_can_list_initiated_workflows(): void
    {
        WorkflowInstance::factory()->count(3)->create([
            'initiator_id' => $this->user->id,
        ]);

        // 其他用户发起的
        WorkflowInstance::factory()->count(2)->create();

        $response = $this->getJson('/api/admin/workflow/instances/initiated', $this->authHeaders());

        $response->assertStatus(200);

        $data = $response->json('data.data');
        $this->assertCount(3, $data);
    }

    // ==================== 任务 API 测试 ====================

    /**
     * 测试获取我的待办任务
     */
    public function test_can_list_pending_tasks(): void
    {
        $instance = WorkflowInstance::factory()->create();

        WorkflowTask::factory()->count(3)->create([
            'instance_id' => $instance->id,
            'assignee_id' => $this->user->id,
            'status' => WorkflowTask::STATUS_PENDING,
        ]);

        // 其他用户的任务
        WorkflowTask::factory()->count(2)->create([
            'instance_id' => $instance->id,
            'status' => WorkflowTask::STATUS_PENDING,
        ]);

        $response = $this->getJson('/api/admin/workflow/tasks', $this->authHeaders());

        $response->assertStatus(200);

        $data = $response->json('data.data');
        $this->assertCount(3, $data);
    }

    /**
     * 测试审批通过
     */
    public function test_can_approve_task(): void
    {
        $definition = WorkflowDefinition::factory()
            ->withApprover($this->user->id)
            ->create();

        $instance = WorkflowInstance::factory()->create([
            'definition_id' => $definition->id,
            'status' => WorkflowInstance::STATUS_RUNNING,
            'current_node_id' => 'approval_1',
        ]);

        $task = WorkflowTask::create([
            'instance_id' => $instance->id,
            'node_id' => 'approval_1',
            'node_type' => 'approval',
            'node_name' => '审批节点',
            'assignee_id' => $this->user->id,
            'status' => WorkflowTask::STATUS_PENDING,
        ]);

        $response = $this->postJson("/api/admin/workflow/tasks/{$task->id}/approve", [
            'comment' => '同意',
        ], $this->authHeaders());

        $response->assertStatus(200);

        $task->refresh();
        $this->assertEquals(WorkflowTask::STATUS_COMPLETED, $task->status);
        $this->assertEquals('approve', $task->action);
    }

    /**
     * 测试审批拒绝
     */
    public function test_can_reject_task(): void
    {
        $definition = WorkflowDefinition::factory()
            ->withApprover($this->user->id)
            ->create();

        $instance = WorkflowInstance::factory()->create([
            'definition_id' => $definition->id,
            'status' => WorkflowInstance::STATUS_RUNNING,
        ]);

        $task = WorkflowTask::create([
            'instance_id' => $instance->id,
            'node_id' => 'approval_1',
            'node_type' => 'approval',
            'node_name' => '审批节点',
            'assignee_id' => $this->user->id,
            'status' => WorkflowTask::STATUS_PENDING,
        ]);

        $response = $this->postJson("/api/admin/workflow/tasks/{$task->id}/reject", [
            'comment' => '不同意',
        ], $this->authHeaders());

        $response->assertStatus(200);

        $task->refresh();
        $instance->refresh();

        $this->assertEquals(WorkflowTask::STATUS_COMPLETED, $task->status);
        $this->assertEquals('reject', $task->action);
        $this->assertEquals(WorkflowInstance::STATUS_REJECTED, $instance->status);
    }

    /**
     * 测试转办任务
     */
    public function test_can_delegate_task(): void
    {
        $delegateTo = User::factory()->create();

        $instance = WorkflowInstance::factory()->create([
            'status' => WorkflowInstance::STATUS_RUNNING,
        ]);

        $task = WorkflowTask::create([
            'instance_id' => $instance->id,
            'node_id' => 'approval_1',
            'node_type' => 'approval',
            'node_name' => '审批节点',
            'assignee_id' => $this->user->id,
            'status' => WorkflowTask::STATUS_PENDING,
        ]);

        $response = $this->postJson("/api/admin/workflow/tasks/{$task->id}/delegate", [
            'target_user' => $delegateTo->id,
            'comment' => '请帮忙处理',
        ], $this->authHeaders());

        $response->assertStatus(200);

        $task->refresh();
        $this->assertEquals(WorkflowTask::STATUS_DELEGATED, $task->status);

        // 检查新任务是否创建
        $newTask = WorkflowTask::where('instance_id', $instance->id)
            ->where('assignee_id', $delegateTo->id)
            ->where('status', WorkflowTask::STATUS_PENDING)
            ->first();

        $this->assertNotNull($newTask);
    }

    /**
     * 测试获取待办任务数量
     */
    public function test_can_get_task_count(): void
    {
        $instance = WorkflowInstance::factory()->create();

        WorkflowTask::factory()->count(5)->create([
            'instance_id' => $instance->id,
            'assignee_id' => $this->user->id,
            'status' => WorkflowTask::STATUS_PENDING,
        ]);

        $response = $this->getJson('/api/admin/workflow/tasks/count', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'data' => [
                    'count' => 5,
                ],
            ]);
    }
}
