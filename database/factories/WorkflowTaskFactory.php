<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowTask>
 */
class WorkflowTaskFactory extends Factory
{
    protected $model = WorkflowTask::class;

    public function definition(): array
    {
        return [
            'instance_id' => WorkflowInstance::factory(),
            'node_id' => 'approval_1',
            'node_type' => 'approval',
            'node_name' => '审批节点',
            'assignee_id' => User::factory(),
            'assignee_type' => 'user',
            'status' => WorkflowTask::STATUS_PENDING,
            'action' => null,
            'comment' => null,
            'delegate_from' => null,
            'timeout_at' => null,
            'processed_at' => null,
        ];
    }

    /**
     * 已完成状态
     */
    public function completed(string $action = 'approve'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkflowTask::STATUS_COMPLETED,
            'action' => $action,
            'processed_at' => now(),
        ]);
    }

    /**
     * 已转办状态
     */
    public function delegated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkflowTask::STATUS_DELEGATED,
            'action' => 'delegate',
            'processed_at' => now(),
        ]);
    }

    /**
     * 已取消状态
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkflowTask::STATUS_CANCELLED,
        ]);
    }

    /**
     * 带超时时间
     */
    public function withTimeout(int $hours = 24): static
    {
        return $this->state(fn (array $attributes) => [
            'timeout_at' => now()->addHours($hours),
        ]);
    }

    /**
     * 已超时
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'timeout_at' => now()->subHour(),
        ]);
    }
}
