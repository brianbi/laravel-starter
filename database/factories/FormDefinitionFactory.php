<?php

namespace Database\Factories;

use App\Models\FormDefinition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormDefinition>
 */
class FormDefinitionFactory extends Factory
{
    protected $model = FormDefinition::class;

    public function definition(): array
    {
        return [
            'code' => 'form_' . fake()->unique()->numerify('####'),
            'name' => fake()->words(2, true) . '表单',
            'description' => fake()->sentence(),
            'fields' => $this->getDefaultFields(),
            'layout' => [],
            'rules' => [],
            'status' => FormDefinition::STATUS_ENABLED,
            'created_by' => User::factory(),
        ];
    }

    /**
     * 默认字段配置
     */
    protected function getDefaultFields(): array
    {
        return [
            [
                'name' => 'title',
                'type' => 'input',
                'label' => '标题',
                'required' => true,
                'config' => ['max_length' => 100],
            ],
            [
                'name' => 'amount',
                'type' => 'number',
                'label' => '金额',
                'required' => true,
                'config' => ['min' => 0, 'max' => 999999],
            ],
            [
                'name' => 'reason',
                'type' => 'textarea',
                'label' => '申请理由',
                'required' => false,
                'config' => ['max_length' => 500],
            ],
        ];
    }

    /**
     * 禁用状态
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FormDefinition::STATUS_DISABLED,
        ]);
    }

    /**
     * 请假表单
     */
    public function leaveForm(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '请假申请表单',
            'fields' => [
                [
                    'name' => 'leave_type',
                    'type' => 'select',
                    'label' => '请假类型',
                    'required' => true,
                    'config' => [
                        'options' => [
                            ['label' => '年假', 'value' => 'annual'],
                            ['label' => '病假', 'value' => 'sick'],
                            ['label' => '事假', 'value' => 'personal'],
                        ],
                    ],
                ],
                [
                    'name' => 'start_date',
                    'type' => 'date',
                    'label' => '开始日期',
                    'required' => true,
                ],
                [
                    'name' => 'end_date',
                    'type' => 'date',
                    'label' => '结束日期',
                    'required' => true,
                ],
                [
                    'name' => 'days',
                    'type' => 'number',
                    'label' => '请假天数',
                    'required' => true,
                    'config' => ['min' => 0.5, 'max' => 30],
                ],
                [
                    'name' => 'reason',
                    'type' => 'textarea',
                    'label' => '请假原因',
                    'required' => true,
                ],
            ],
        ]);
    }

    /**
     * 报销表单
     */
    public function expenseForm(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '报销申请表单',
            'fields' => [
                [
                    'name' => 'expense_type',
                    'type' => 'select',
                    'label' => '报销类型',
                    'required' => true,
                    'config' => [
                        'options' => [
                            ['label' => '差旅费', 'value' => 'travel'],
                            ['label' => '办公用品', 'value' => 'office'],
                            ['label' => '招待费', 'value' => 'entertainment'],
                        ],
                    ],
                ],
                [
                    'name' => 'amount',
                    'type' => 'money',
                    'label' => '报销金额',
                    'required' => true,
                    'config' => ['min' => 0],
                ],
                [
                    'name' => 'attachments',
                    'type' => 'file',
                    'label' => '发票附件',
                    'required' => true,
                    'config' => ['multiple' => true],
                ],
                [
                    'name' => 'description',
                    'type' => 'textarea',
                    'label' => '费用说明',
                    'required' => false,
                ],
            ],
        ]);
    }
}
