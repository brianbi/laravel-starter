<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowInstance>
 */
class WorkflowInstanceFactory extends Factory
{
    protected $model = WorkflowInstance::class;

    public function definition(): array
    {
        return [
            'definition_id' => WorkflowDefinition::factory(),
            'definition_version' => 1,
            'business_type' => 'test',
            'business_id' => null,
            'form_data' => [
                'title' => fake()->sentence(),
                'amount' => fake()->numberBetween(100, 10000),
                'reason' => fake()->paragraph(),
            ],
            'initiator_id' => User::factory(),
            'current_node_id' => 'start_1',
            'status' => WorkflowInstance::STATUS_PENDING,
            'started_at' => now(),
        ];
    }

    /**
     * 进行中状态
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkflowInstance::STATUS_PENDING,
            'current_node_id' => 'approval_1',
        ]);
    }

    /**
     * 已通过状态
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkflowInstance::STATUS_APPROVED,
            'current_node_id' => 'end_1',
            'completed_at' => now(),
        ]);
    }

    /**
     * 已拒绝状态
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkflowInstance::STATUS_REJECTED,
            'completed_at' => now(),
        ]);
    }

    /**
     * 已撤回状态
     */
    public function withdrawn(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkflowInstance::STATUS_WITHDRAWN,
            'completed_at' => now(),
        ]);
    }
}
