<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkflowDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowDefinition>
 */
class WorkflowDefinitionFactory extends Factory
{
    protected $model = WorkflowDefinition::class;

    public function definition(): array
    {
        return [
            'code' => 'wf_' . fake()->unique()->numerify('####'),
            'name' => fake()->words(3, true) . '审批流程',
            'description' => fake()->sentence(),
            'form_type' => 'model',
            'form_id' => null,
            'model_class' => 'App\\Models\\TestModel',
            'version' => 1,
            'graph' => $this->getDefaultGraph(),
            'status' => WorkflowDefinition::STATUS_ENABLED,
            'created_by' => User::factory(),
        ];
    }

    /**
     * 默认流程图结构
     */
    protected function getDefaultGraph(): array
    {
        return [
            'nodes' => [
                [
                    'id' => 'start_1',
                    'type' => 'start',
                    'name' => '开始',
                    'x' => 100,
                    'y' => 200,
                ],
                [
                    'id' => 'approval_1',
                    'type' => 'approval',
                    'name' => '审批节点',
                    'x' => 300,
                    'y' => 200,
                    'config' => [
                        'assignee' => [
                            'type' => 'user',
                            'user_ids' => [],
                        ],
                    ],
                ],
                [
                    'id' => 'end_1',
                    'type' => 'end',
                    'name' => '结束',
                    'x' => 500,
                    'y' => 200,
                ],
            ],
            'edges' => [
                ['source' => 'start_1', 'target' => 'approval_1'],
                ['source' => 'approval_1', 'target' => 'end_1'],
            ],
        ];
    }

    /**
     * 禁用状态
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkflowDefinition::STATUS_DISABLED,
        ]);
    }

    /**
     * 指定审批人
     */
    public function withApprover(int $userId): static
    {
        return $this->state(function (array $attributes) use ($userId) {
            $graph = $attributes['graph'];
            foreach ($graph['nodes'] as &$node) {
                if ($node['type'] === 'approval' || $node['type'] === 'approval_all' || $node['type'] === 'approval_any') {
                    $node['config']['assignee'] = [
                        'type' => 'user',
                        'user_ids' => [$userId],
                    ];
                }
            }
            return ['graph' => $graph];
        });
    }

    /**
     * 带条件分支的流程
     */
    public function withCondition(): static
    {
        return $this->state(fn (array $attributes) => [
            'graph' => [
                'nodes' => [
                    ['id' => 'start_1', 'type' => 'start', 'name' => '开始', 'x' => 100, 'y' => 200],
                    [
                        'id' => 'condition_1',
                        'type' => 'condition',
                        'name' => '金额判断',
                        'x' => 300,
                        'y' => 200,
                        'config' => [
                            'conditions' => [
                                ['expression' => 'amount > 1000', 'target' => 'approval_1'],
                                ['expression' => 'default', 'target' => 'end_1'],
                            ],
                        ],
                    ],
                    [
                        'id' => 'approval_1',
                        'type' => 'approval',
                        'name' => '经理审批',
                        'x' => 500,
                        'y' => 100,
                        'config' => [
                            'assignee' => ['type' => 'user', 'user_ids' => []],
                        ],
                    ],
                    ['id' => 'end_1', 'type' => 'end', 'name' => '结束', 'x' => 700, 'y' => 200],
                ],
                'edges' => [
                    ['source' => 'start_1', 'target' => 'condition_1'],
                    ['source' => 'condition_1', 'target' => 'approval_1', 'condition' => 'amount > 1000'],
                    ['source' => 'condition_1', 'target' => 'end_1', 'condition' => 'default'],
                    ['source' => 'approval_1', 'target' => 'end_1'],
                ],
            ],
        ]);
    }

    /**
     * 会签流程
     */
    public function withApprovalAll(): static
    {
        return $this->state(fn (array $attributes) => [
            'graph' => [
                'nodes' => [
                    ['id' => 'start_1', 'type' => 'start', 'name' => '开始', 'x' => 100, 'y' => 200],
                    [
                        'id' => 'approval_all_1',
                        'type' => 'approval_all',
                        'name' => '会签节点',
                        'x' => 300,
                        'y' => 200,
                        'config' => [
                            'assignee' => ['type' => 'user', 'user_ids' => []],
                            'pass_ratio' => 100,
                        ],
                    ],
                    ['id' => 'end_1', 'type' => 'end', 'name' => '结束', 'x' => 500, 'y' => 200],
                ],
                'edges' => [
                    ['source' => 'start_1', 'target' => 'approval_all_1'],
                    ['source' => 'approval_all_1', 'target' => 'end_1'],
                ],
            ],
        ]);
    }
}
