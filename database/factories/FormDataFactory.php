<?php

namespace Database\Factories;

use App\Models\FormData;
use App\Models\FormDefinition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormData>
 */
class FormDataFactory extends Factory
{
    protected $model = FormData::class;

    public function definition(): array
    {
        return [
            'form_id' => FormDefinition::factory(),
            'instance_id' => null,
            'data' => [
                'title' => fake()->sentence(),
                'amount' => fake()->numberBetween(100, 10000),
                'reason' => fake()->paragraph(),
            ],
            'created_by' => User::factory(),
        ];
    }

    /**
     * 关联流程实例
     */
    public function withInstance(int $instanceId): static
    {
        return $this->state(fn (array $attributes) => [
            'instance_id' => $instanceId,
        ]);
    }
}
