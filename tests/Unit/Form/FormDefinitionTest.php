<?php

namespace Tests\Unit\Form;

use App\Models\FormDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FormDefinitionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 测试生成验证规则 - 必填字段
     */
    public function test_generates_required_validation_rules(): void
    {
        $form = FormDefinition::factory()->create([
            'fields' => [
                ['name' => 'title', 'type' => 'input', 'label' => '标题', 'required' => true],
                ['name' => 'note', 'type' => 'textarea', 'label' => '备注', 'required' => false],
            ],
        ]);

        $rules = $form->generateValidationRules();

        $this->assertContains('required', $rules['title']);
        $this->assertContains('nullable', $rules['note']);
    }

    /**
     * 测试生成验证规则 - 字符串长度限制
     */
    public function test_generates_string_max_length_rules(): void
    {
        $form = FormDefinition::factory()->create([
            'fields' => [
                [
                    'name' => 'title',
                    'type' => 'input',
                    'label' => '标题',
                    'required' => true,
                    'config' => ['max_length' => 100],
                ],
            ],
        ]);

        $rules = $form->generateValidationRules();

        $this->assertContains('string', $rules['title']);
        $this->assertContains('max:100', $rules['title']);
    }

    /**
     * 测试生成验证规则 - 数字范围
     */
    public function test_generates_numeric_range_rules(): void
    {
        $form = FormDefinition::factory()->create([
            'fields' => [
                [
                    'name' => 'amount',
                    'type' => 'number',
                    'label' => '金额',
                    'required' => true,
                    'config' => ['min' => 0, 'max' => 10000],
                ],
            ],
        ]);

        $rules = $form->generateValidationRules();

        $this->assertContains('numeric', $rules['amount']);
        $this->assertContains('min:0', $rules['amount']);
        $this->assertContains('max:10000', $rules['amount']);
    }

    /**
     * 测试生成验证规则 - 下拉选择
     */
    public function test_generates_select_in_rules(): void
    {
        $form = FormDefinition::factory()->create([
            'fields' => [
                [
                    'name' => 'type',
                    'type' => 'select',
                    'label' => '类型',
                    'required' => true,
                    'config' => [
                        'options' => [
                            ['label' => '选项A', 'value' => 'a'],
                            ['label' => '选项B', 'value' => 'b'],
                            ['label' => '选项C', 'value' => 'c'],
                        ],
                    ],
                ],
            ],
        ]);

        $rules = $form->generateValidationRules();

        $this->assertContains('in:a,b,c', $rules['type']);
    }

    /**
     * 测试生成验证规则 - 日期类型
     */
    public function test_generates_date_rules(): void
    {
        $form = FormDefinition::factory()->create([
            'fields' => [
                ['name' => 'start_date', 'type' => 'date', 'label' => '开始日期', 'required' => true],
                ['name' => 'start_time', 'type' => 'datetime', 'label' => '开始时间', 'required' => false],
            ],
        ]);

        $rules = $form->generateValidationRules();

        $this->assertContains('date', $rules['start_date']);
        $this->assertContains('date', $rules['start_time']);
    }

    /**
     * 测试生成验证规则 - 布尔类型
     */
    public function test_generates_boolean_rules(): void
    {
        $form = FormDefinition::factory()->create([
            'fields' => [
                ['name' => 'is_urgent', 'type' => 'switch', 'label' => '是否紧急', 'required' => false],
            ],
        ]);

        $rules = $form->generateValidationRules();

        $this->assertContains('boolean', $rules['is_urgent']);
    }

    /**
     * 测试验证数据 - 通过
     */
    public function test_validate_data_passes(): void
    {
        $form = FormDefinition::factory()->create([
            'fields' => [
                ['name' => 'title', 'type' => 'input', 'label' => '标题', 'required' => true, 'config' => ['max_length' => 100]],
                ['name' => 'amount', 'type' => 'number', 'label' => '金额', 'required' => true, 'config' => ['min' => 0]],
            ],
        ]);

        $validated = $form->validateData([
            'title' => '测试标题',
            'amount' => 1000,
        ]);

        $this->assertEquals('测试标题', $validated['title']);
        $this->assertEquals(1000, $validated['amount']);
    }

    /**
     * 测试验证数据 - 失败（缺少必填字段）
     */
    public function test_validate_data_fails_for_missing_required(): void
    {
        $form = FormDefinition::factory()->create([
            'fields' => [
                ['name' => 'title', 'type' => 'input', 'label' => '标题', 'required' => true],
            ],
        ]);

        $this->expectException(ValidationException::class);

        $form->validateData([
            'title' => '',
        ]);
    }

    /**
     * 测试验证数据 - 失败（超出最大长度）
     */
    public function test_validate_data_fails_for_exceeding_max_length(): void
    {
        $form = FormDefinition::factory()->create([
            'fields' => [
                ['name' => 'title', 'type' => 'input', 'label' => '标题', 'required' => true, 'config' => ['max_length' => 10]],
            ],
        ]);

        $this->expectException(ValidationException::class);

        $form->validateData([
            'title' => '这是一个超过十个字符的标题',
        ]);
    }

    /**
     * 测试获取字段类型列表
     */
    public function test_get_field_types(): void
    {
        $types = FormDefinition::getFieldTypes();

        $this->assertArrayHasKey('input', $types);
        $this->assertArrayHasKey('textarea', $types);
        $this->assertArrayHasKey('number', $types);
        $this->assertArrayHasKey('select', $types);
        $this->assertArrayHasKey('date', $types);
        $this->assertArrayHasKey('file', $types);
        $this->assertArrayHasKey('user_select', $types);

        $this->assertEquals('单行文本', $types['input']['name']);
    }

    /**
     * 测试获取指定字段
     */
    public function test_get_field(): void
    {
        $form = FormDefinition::factory()->create([
            'fields' => [
                ['name' => 'title', 'type' => 'input', 'label' => '标题'],
                ['name' => 'amount', 'type' => 'number', 'label' => '金额'],
            ],
        ]);

        $field = $form->getField('amount');

        $this->assertNotNull($field);
        $this->assertEquals('number', $field['type']);
        $this->assertEquals('金额', $field['label']);
    }

    /**
     * 测试获取字段名称列表
     */
    public function test_get_field_names(): void
    {
        $form = FormDefinition::factory()->create([
            'fields' => [
                ['name' => 'title', 'type' => 'input', 'label' => '标题'],
                ['name' => 'amount', 'type' => 'number', 'label' => '金额'],
                ['name' => 'reason', 'type' => 'textarea', 'label' => '原因'],
            ],
        ]);

        $names = $form->getFieldNames();

        $this->assertEquals(['title', 'amount', 'reason'], $names);
    }

    /**
     * 测试状态文本
     */
    public function test_status_text_attribute(): void
    {
        $enabledForm = FormDefinition::factory()->create(['status' => FormDefinition::STATUS_ENABLED]);
        $disabledForm = FormDefinition::factory()->create(['status' => FormDefinition::STATUS_DISABLED]);

        $this->assertEquals('启用', $enabledForm->status_text);
        $this->assertEquals('禁用', $disabledForm->status_text);
    }

    /**
     * 测试是否启用
     */
    public function test_is_enabled(): void
    {
        $enabledForm = FormDefinition::factory()->create(['status' => FormDefinition::STATUS_ENABLED]);
        $disabledForm = FormDefinition::factory()->disabled()->create();

        $this->assertTrue($enabledForm->isEnabled());
        $this->assertFalse($disabledForm->isEnabled());
    }

    /**
     * 测试作用域 - 启用的表单
     */
    public function test_enabled_scope(): void
    {
        FormDefinition::factory()->count(3)->create(['status' => FormDefinition::STATUS_ENABLED]);
        FormDefinition::factory()->count(2)->disabled()->create();

        $enabledForms = FormDefinition::enabled()->count();

        $this->assertEquals(3, $enabledForms);
    }
}
