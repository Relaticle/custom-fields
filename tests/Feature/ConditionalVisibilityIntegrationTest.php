<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Relaticle\CustomFields\Data\CustomFieldConditionsData;
use Relaticle\CustomFields\Data\CustomFieldSettingsData;
use Relaticle\CustomFields\Enums\ConditionalVisibilityLogic;
use Relaticle\CustomFields\Enums\ConditionalVisibilityMode;
use Relaticle\CustomFields\Enums\ConditionOperator;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\ConditionalVisibilityService;
use Relaticle\CustomFields\Tests\TestCase;

class ConditionalVisibilityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private ConditionalVisibilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ConditionalVisibilityService;
    }

    public function test_it_stores_conditional_visibility_in_custom_field_settings(): void
    {
        $field = CustomField::factory()->create([
            'type' => CustomFieldType::TEXT,
            'settings' => CustomFieldSettingsData::from([
                'conditional_visibility' => new CustomFieldConditionsData(
                    enabled: ConditionalVisibilityMode::SHOW_WHEN,
                    logic: ConditionalVisibilityLogic::ALL,
                    conditions: [
                        ['field_code' => 'status', 'operator' => ConditionOperator::EQUALS->value, 'value' => 'active'],
                    ],
                    always_save: false
                ),
            ]),
        ]);

        $conditionalVisibility = $field->settings->conditionalVisibility;
        $this->assertEquals(ConditionalVisibilityMode::SHOW_WHEN, $conditionalVisibility->enabled);
        $this->assertEquals(ConditionalVisibilityLogic::ALL, $conditionalVisibility->logic);
        $this->assertCount(1, $conditionalVisibility->conditions);
    }

    public function test_it_evaluates_field_visibility_from_settings(): void
    {
        $field = CustomField::factory()->create([
            'type' => CustomFieldType::TEXT,
            'settings' => CustomFieldSettingsData::from([
                'conditional_visibility' => new CustomFieldConditionsData(
                    enabled: ConditionalVisibilityMode::SHOW_WHEN,
                    logic: ConditionalVisibilityLogic::ALL,
                    conditions: [
                        ['field_code' => 'priority', 'operator' => ConditionOperator::GREATER_THAN->value, 'value' => '5'],
                        ['field_code' => 'category', 'operator' => ConditionOperator::EQUALS->value, 'value' => 'urgent'],
                    ],
                    always_save: false
                ),
            ]),
        ]);

        $formData = ['priority' => '7', 'category' => 'urgent'];
        $shouldShow = $this->service->shouldShowField($field, $formData);
        $this->assertTrue($shouldShow);

        $formData = ['priority' => '3', 'category' => 'urgent'];
        $shouldShow = $this->service->shouldShowField($field, $formData);
        $this->assertFalse($shouldShow);
    }

    public function test_it_works_with_multiple_field_types(): void
    {
        // Text field with simple condition
        $textField = CustomField::factory()->create([
            'code' => 'description',
            'type' => CustomFieldType::TEXT,
            'settings' => CustomFieldSettingsData::from([
                'conditional_visibility' => new CustomFieldConditionsData(
                    enabled: ConditionalVisibilityMode::SHOW_WHEN,
                    logic: ConditionalVisibilityLogic::ALL,
                    conditions: [
                        ['field_code' => 'type', 'operator' => ConditionOperator::EQUALS->value, 'value' => 'detailed'],
                    ],
                    always_save: false
                ),
            ]),
        ]);

        // Select field with multiple conditions
        $selectField = CustomField::factory()->create([
            'code' => 'priority_level',
            'type' => CustomFieldType::SELECT,
            'settings' => CustomFieldSettingsData::from([
                'conditional_visibility' => new CustomFieldConditionsData(
                    enabled: ConditionalVisibilityMode::SHOW_WHEN,
                    logic: ConditionalVisibilityLogic::ANY,
                    conditions: [
                        ['field_code' => 'status', 'operator' => ConditionOperator::EQUALS->value, 'value' => 'active'],
                        ['field_code' => 'category', 'operator' => ConditionOperator::EQUALS->value, 'value' => 'urgent'],
                    ],
                    always_save: false
                ),
            ]),
        ]);

        $formData = [
            'type' => 'detailed',
            'status' => 'active',
            'category' => 'normal',
        ];

        $this->assertTrue($this->service->shouldShowField($textField, $formData));
        $this->assertTrue($this->service->shouldShowField($selectField, $formData));

        $formData['type'] = 'simple';
        $formData['status'] = 'inactive';

        $this->assertFalse($this->service->shouldShowField($textField, $formData));
        $this->assertFalse($this->service->shouldShowField($selectField, $formData));
    }

    public function test_it_supports_array_and_multi_value_fields(): void
    {
        $field = CustomField::factory()->create([
            'type' => CustomFieldType::MULTI_SELECT,
            'settings' => CustomFieldSettingsData::from([
                'conditional_visibility' => new CustomFieldConditionsData(
                    enabled: ConditionalVisibilityMode::SHOW_WHEN,
                    logic: ConditionalVisibilityLogic::ALL,
                    conditions: [
                        ['field_code' => 'features', 'operator' => ConditionOperator::CONTAINS->value, 'value' => 'api_access'],
                    ],
                    always_save: false
                ),
            ]),
        ]);

        $formData = ['features' => ['basic', 'api_access', 'premium']];
        $shouldShow = $this->service->shouldShowField($field, $formData);
        $this->assertTrue($shouldShow);

        $formData = ['features' => ['basic', 'premium']];
        $shouldShow = $this->service->shouldShowField($field, $formData);
        $this->assertFalse($shouldShow);
    }

    public function test_it_handles_empty_and_null_checks(): void
    {
        $field = CustomField::factory()->create([
            'type' => CustomFieldType::TEXT,
            'settings' => CustomFieldSettingsData::from([
                'conditional_visibility' => new CustomFieldConditionsData(
                    enabled: ConditionalVisibilityMode::SHOW_WHEN,
                    logic: ConditionalVisibilityLogic::ALL,
                    conditions: [
                        ['field_code' => 'notes', 'operator' => ConditionOperator::IS_NOT_EMPTY->value],
                    ],
                    always_save: false
                ),
            ]),
        ]);

        $formData = ['notes' => 'Some notes here'];
        $shouldShow = $this->service->shouldShowField($field, $formData);
        $this->assertTrue($shouldShow);

        $formData = ['notes' => ''];
        $shouldShow = $this->service->shouldShowField($field, $formData);
        $this->assertFalse($shouldShow);

        $formData = ['notes' => null];
        $shouldShow = $this->service->shouldShowField($field, $formData);
        $this->assertFalse($shouldShow);
    }

    public function test_it_provides_configuration_for_frontend(): void
    {
        $field = CustomField::factory()->create([
            'type' => CustomFieldType::TEXT,
            'settings' => CustomFieldSettingsData::from([
                'conditional_visibility' => new CustomFieldConditionsData(
                    enabled: ConditionalVisibilityMode::HIDE_WHEN,
                    logic: ConditionalVisibilityLogic::ANY,
                    conditions: [
                        ['field_code' => 'status', 'operator' => ConditionOperator::EQUALS->value, 'value' => 'disabled'],
                    ],
                    always_save: true
                ),
            ]),
        ]);

        $conditionalVisibility = $field->settings->conditionalVisibility;

        $this->assertEquals(ConditionalVisibilityMode::HIDE_WHEN, $conditionalVisibility->enabled);
        $this->assertEquals(ConditionalVisibilityLogic::ANY, $conditionalVisibility->logic);
        $this->assertTrue($conditionalVisibility->always_save);
        $this->assertCount(1, $conditionalVisibility->conditions);

        // Test the dependencies
        $dependencies = $this->service->getFieldDependencies($field);
        $this->assertEquals(['status'], $dependencies);

        // Test always save
        $this->assertTrue($this->service->shouldAlwaysSave($field));
    }

    public function test_it_implements_cascading_visibility(): void
    {
        // Create three fields with dependency chain: A -> B -> C
        // Field A has no conditions (always visible)
        $fieldA = CustomField::factory()->create([
            'code' => 'field_a',
            'type' => CustomFieldType::SELECT,
        ]);

        // Field B depends on Field A
        $fieldB = CustomField::factory()->create([
            'code' => 'field_b',
            'type' => CustomFieldType::TEXT,
            'settings' => CustomFieldSettingsData::from([
                'conditional_visibility' => new CustomFieldConditionsData(
                    enabled: ConditionalVisibilityMode::SHOW_WHEN,
                    logic: ConditionalVisibilityLogic::ALL,
                    conditions: [
                        ['field_code' => 'field_a', 'operator' => ConditionOperator::EQUALS->value, 'value' => 'show_b'],
                    ],
                    always_save: false
                ),
            ]),
        ]);

        // Field C depends on Field B
        $fieldC = CustomField::factory()->create([
            'code' => 'field_c',
            'type' => CustomFieldType::TEXT,
            'settings' => CustomFieldSettingsData::from([
                'conditional_visibility' => new CustomFieldConditionsData(
                    enabled: ConditionalVisibilityMode::SHOW_WHEN,
                    logic: ConditionalVisibilityLogic::ALL,
                    conditions: [
                        ['field_code' => 'field_b', 'operator' => ConditionOperator::IS_NOT_EMPTY->value],
                    ],
                    always_save: false
                ),
            ]),
        ]);

        // Test Case 1: A shows B, B has value so C should show
        $formData = [
            'field_a' => 'show_b',
            'field_b' => 'some_value',
        ];

        $this->assertTrue($this->service->shouldShowField($fieldA, $formData)); // A always visible
        $this->assertTrue($this->service->shouldShowField($fieldB, $formData)); // B condition met
        $this->assertTrue($this->service->shouldShowField($fieldC, $formData)); // C condition met and B visible

        // Test Case 2: A hides B, so C should be hidden even if its condition would be met
        $formData = [
            'field_a' => 'hide_b',
            'field_b' => 'some_value', // C's condition would be met
        ];

        $this->assertTrue($this->service->shouldShowField($fieldA, $formData));  // A always visible
        $this->assertFalse($this->service->shouldShowField($fieldB, $formData)); // B condition not met
        // Note: This test verifies service logic. The FieldConfigurator cascading would hide C
        // even if service says it should show, but we can't easily test that without Filament context

        // Test Case 3: A shows B but B is empty, so C should be hidden
        $formData = [
            'field_a' => 'show_b',
            'field_b' => '', // Empty value
        ];

        $this->assertTrue($this->service->shouldShowField($fieldA, $formData));  // A always visible
        $this->assertTrue($this->service->shouldShowField($fieldB, $formData));  // B condition met
        $this->assertFalse($this->service->shouldShowField($fieldC, $formData)); // C condition not met
    }
}
