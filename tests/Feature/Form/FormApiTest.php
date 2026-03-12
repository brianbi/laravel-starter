<?php

namespace Tests\Feature\Form;

use App\Models\FormData;
use App\Models\FormDefinition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormApiTest extends TestCase
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

    // ==================== 表单定义 API 测试 ====================

    /**
     * 测试获取表单定义列表
     */
    public function test_can_list_form_definitions(): void
    {
        FormDefinition::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/forms', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'data',
                    'total',
                ],
            ]);
    }

    /**
     * 测试创建表单定义
     */
    public function test_can_create_form_definition(): void
    {
        $data = [
            'code' => 'leave_form',
            'name' => '请假表单',
            'description' => '员工请假申请表单',
            'fields' => [
                ['name' => 'title', 'type' => 'input', 'label' => '标题', 'required' => true],
                ['name' => 'days', 'type' => 'number', 'label' => '天数', 'required' => true],
            ],
        ];

        $response = $this->postJson('/api/admin/forms', $data, $this->authHeaders());

        $response->assertStatus(201)
            ->assertJson(['code' => 201]);

        $this->assertDatabaseHas('form_definitions', [
            'code' => 'leave_form',
            'name' => '请假表单',
        ]);
    }

    /**
     * 测试创建表单 - 编码重复
     */
    public function test_cannot_create_form_with_duplicate_code(): void
    {
        FormDefinition::factory()->create(['code' => 'existing_code']);

        $response = $this->postJson('/api/admin/forms', [
            'code' => 'existing_code',
            'name' => '测试表单',
            'fields' => [
                ['name' => 'title', 'type' => 'input', 'label' => '标题', 'required' => true],
            ],
        ], $this->authHeaders());

        $response->assertStatus(422);
    }

    /**
     * 测试获取表单定义详情
     */
    public function test_can_show_form_definition(): void
    {
        $form = FormDefinition::factory()->create();

        $response = $this->getJson("/api/admin/forms/{$form->id}", $this->authHeaders());

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'data' => [
                    'id' => $form->id,
                    'code' => $form->code,
                ],
            ]);
    }

    /**
     * 测试更新表单定义
     */
    public function test_can_update_form_definition(): void
    {
        $form = FormDefinition::factory()->create();

        $response = $this->putJson("/api/admin/forms/{$form->id}", [
            'name' => '更新后的表单名称',
        ], $this->authHeaders());

        $response->assertStatus(200);

        $this->assertDatabaseHas('form_definitions', [
            'id' => $form->id,
            'name' => '更新后的表单名称',
        ]);
    }

    /**
     * 测试删除表单定义
     */
    public function test_can_delete_form_definition(): void
    {
        $form = FormDefinition::factory()->create();

        $response = $this->deleteJson("/api/admin/forms/{$form->id}", [], $this->authHeaders());

        $response->assertStatus(200);

        $this->assertDatabaseMissing('form_definitions', [
            'id' => $form->id,
        ]);
    }

    /**
     * 测试删除已有数据的表单
     */
    public function test_cannot_delete_form_with_data(): void
    {
        $form = FormDefinition::factory()->create();

        FormData::create([
            'form_id' => $form->id,
            'data' => ['title' => '测试'],
            'created_by' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/admin/forms/{$form->id}", [], $this->authHeaders());

        $response->assertStatus(400);

        $this->assertDatabaseHas('form_definitions', [
            'id' => $form->id,
        ]);
    }

    /**
     * 测试启用表单
     */
    public function test_can_enable_form(): void
    {
        $form = FormDefinition::factory()->disabled()->create();

        $response = $this->postJson("/api/admin/forms/{$form->id}/enable", [], $this->authHeaders());

        $response->assertStatus(200);

        $form->refresh();
        $this->assertEquals(FormDefinition::STATUS_ENABLED, $form->status);
    }

    /**
     * 测试禁用表单
     */
    public function test_can_disable_form(): void
    {
        $form = FormDefinition::factory()->create(['status' => FormDefinition::STATUS_ENABLED]);

        $response = $this->postJson("/api/admin/forms/{$form->id}/disable", [], $this->authHeaders());

        $response->assertStatus(200);

        $form->refresh();
        $this->assertEquals(FormDefinition::STATUS_DISABLED, $form->status);
    }

    /**
     * 测试复制表单
     */
    public function test_can_copy_form(): void
    {
        $form = FormDefinition::factory()->create();

        $response = $this->postJson("/api/admin/forms/{$form->id}/copy", [
            'code' => 'copied_form',
            'name' => '复制的表单',
        ], $this->authHeaders());

        $response->assertStatus(200);

        $this->assertDatabaseHas('form_definitions', [
            'code' => 'copied_form',
            'name' => '复制的表单',
        ]);
    }

    /**
     * 测试获取字段类型列表
     */
    public function test_can_get_field_types(): void
    {
        $response = $this->getJson('/api/admin/forms/field-types', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'data' => [
                    'input',
                    'textarea',
                    'number',
                    'select',
                ],
            ]);
    }

    /**
     * 测试获取启用的表单列表
     */
    public function test_can_get_enabled_forms(): void
    {
        FormDefinition::factory()->count(3)->create(['status' => FormDefinition::STATUS_ENABLED]);
        FormDefinition::factory()->count(2)->disabled()->create();

        $response = $this->getJson('/api/admin/forms/enabled', $this->authHeaders());

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(3, $data);
    }

    /**
     * 测试验证表单数据
     */
    public function test_can_validate_form_data(): void
    {
        $form = FormDefinition::factory()->create([
            'fields' => [
                ['name' => 'title', 'type' => 'input', 'label' => '标题', 'required' => true],
                ['name' => 'amount', 'type' => 'number', 'label' => '金额', 'required' => true],
            ],
        ]);

        $response = $this->postJson("/api/admin/forms/{$form->id}/validate", [
            'title' => '测试标题',
            'amount' => 1000,
        ], $this->authHeaders());

        $response->assertStatus(200)
            ->assertJson(['code' => 200]);
    }

    /**
     * 测试验证表单数据 - 验证失败
     */
    public function test_validate_form_data_fails(): void
    {
        $form = FormDefinition::factory()->create([
            'fields' => [
                ['name' => 'title', 'type' => 'input', 'label' => '标题', 'required' => true],
            ],
        ]);

        $response = $this->postJson("/api/admin/forms/{$form->id}/validate", [
            'title' => '', // 空值
        ], $this->authHeaders());

        $response->assertStatus(422)
            ->assertJson(['code' => 422]);
    }

    // ==================== 表单数据 API 测试 ====================

    /**
     * 测试提交表单数据
     */
    public function test_can_submit_form_data(): void
    {
        $form = FormDefinition::factory()->create([
            'status' => FormDefinition::STATUS_ENABLED,
            'fields' => [
                ['name' => 'title', 'type' => 'input', 'label' => '标题', 'required' => true],
            ],
        ]);

        $response = $this->postJson("/api/admin/forms/{$form->id}/data", [
            'data' => ['title' => '测试数据'],
        ], $this->authHeaders());

        $response->assertStatus(201);

        $this->assertDatabaseHas('form_data', [
            'form_id' => $form->id,
            'created_by' => $this->user->id,
        ]);
    }

    /**
     * 测试向禁用表单提交数据
     */
    public function test_cannot_submit_to_disabled_form(): void
    {
        $form = FormDefinition::factory()->disabled()->create();

        $response = $this->postJson("/api/admin/forms/{$form->id}/data", [
            'data' => ['title' => '测试'],
        ], $this->authHeaders());

        $response->assertStatus(400);
    }

    /**
     * 测试获取表单数据列表
     */
    public function test_can_list_form_data(): void
    {
        $form = FormDefinition::factory()->create();

        FormData::factory()->count(3)->create([
            'form_id' => $form->id,
        ]);

        $response = $this->getJson("/api/admin/forms/{$form->id}/data", $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'data' => [
                    'data',
                    'total',
                ],
            ]);
    }

    /**
     * 测试获取表单数据详情
     */
    public function test_can_show_form_data(): void
    {
        $form = FormDefinition::factory()->create();

        $formData = FormData::create([
            'form_id' => $form->id,
            'data' => ['title' => '测试'],
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson("/api/admin/forms/{$form->id}/data/{$formData->id}", $this->authHeaders());

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'data' => [
                    'id' => $formData->id,
                ],
            ]);
    }

    /**
     * 测试更新表单数据
     */
    public function test_can_update_form_data(): void
    {
        $form = FormDefinition::factory()->create([
            'fields' => [
                ['name' => 'title', 'type' => 'input', 'label' => '标题', 'required' => true],
            ],
        ]);

        $formData = FormData::create([
            'form_id' => $form->id,
            'data' => ['title' => '原标题'],
            'created_by' => $this->user->id,
        ]);

        $response = $this->putJson("/api/admin/forms/{$form->id}/data/{$formData->id}", [
            'data' => ['title' => '新标题'],
        ], $this->authHeaders());

        $response->assertStatus(200);

        $formData->refresh();
        $this->assertEquals('新标题', $formData->data['title']);
    }

    /**
     * 测试删除表单数据
     */
    public function test_can_delete_form_data(): void
    {
        $form = FormDefinition::factory()->create();

        $formData = FormData::create([
            'form_id' => $form->id,
            'data' => ['title' => '测试'],
            'created_by' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/admin/forms/{$form->id}/data/{$formData->id}", [], $this->authHeaders());

        $response->assertStatus(200);

        $this->assertDatabaseMissing('form_data', [
            'id' => $formData->id,
        ]);
    }

    /**
     * 测试获取表单统计
     */
    public function test_can_get_form_statistics(): void
    {
        $form = FormDefinition::factory()->create();

        FormData::factory()->count(5)->create([
            'form_id' => $form->id,
        ]);

        $response = $this->getJson("/api/admin/forms/{$form->id}/statistics", $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'data' => [
                    'total',
                    'today',
                    'this_week',
                    'this_month',
                ],
            ]);
    }
}
