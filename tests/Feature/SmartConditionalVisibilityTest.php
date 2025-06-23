<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Relaticle\CustomFields\Data\VisibilityData;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Enums\Logic;
use Relaticle\CustomFields\Enums\Mode;
use Relaticle\CustomFields\Enums\Operator;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldOption;
use Relaticle\CustomFields\Services\VisibilityService;
use Relaticle\CustomFields\Tests\TestCase;

class SmartConditionalVisibilityTest extends TestCase
{
    private VisibilityService $visibilityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->visibilityService = app(VisibilityService::class);
    }

    #[Test]
    public function it_can_get_field_options_for_optionable_fields(): void
    {
        $selectField = CustomField::factory()->create([
            'type' => CustomFieldType::SELECT,
            'code' => 'priority',
            'entity_type' => 'Relaticle\\CustomFields\\Tests\\Models\\User',
        ]);

        CustomFieldOption::factory()->create([
            'custom_field_id' => $selectField->id,
            'name' => 'High',
            'sort_order' => 1,
        ]);

        CustomFieldOption::factory()->create([
            'custom_field_id' => $selectField->id,
            'name' => 'Medium',
            'sort_order' => 2,
        ]);

        CustomFieldOption::factory()->create([
            'custom_field_id' => $selectField->id,
            'name' => 'Low',
            'sort_order' => 3,
        ]);

        $options = $this->visibilityService->getFieldOptions('priority', 'Relaticle\\CustomFields\\Tests\\Models\\User');

        $this->assertIsArray($options);
        $this->assertCount(3, $options);
        $this->assertArrayHasKey('High', $options);
        $this->assertArrayHasKey('Medium', $options);
        $this->assertArrayHasKey('Low', $options);
        $this->assertEquals('High', $options['High']);
        $this->assertEquals('Medium', $options['Medium']);
        $this->assertEquals('Low', $options['Low']);
    }

    #[Test]
    public function it_returns_empty_options_for_non_optionable_fields(): void
    {
        $textField = CustomField::factory()->create([
            'type' => CustomFieldType::TEXT,
            'code' => 'description',
            'entity_type' => 'Relaticle\\CustomFields\\Tests\\Models\\User',
        ]);

        $options = $this->visibilityService->getFieldOptions('description', 'Relaticle\\CustomFields\\Tests\\Models\\User');

        $this->assertIsArray($options);
        $this->assertEmpty($options);
    }

    #[Test]
    public function it_can_get_field_metadata_for_visibility_enhancement(): void
    {
        $multiSelectField = CustomField::factory()->create([
            'type' => CustomFieldType::MULTI_SELECT,
            'code' => 'tags',
            'entity_type' => 'Relaticle\\CustomFields\\Tests\\Models\\User',
        ]);

        $metadata = $this->visibilityService->getFieldMetadata('tags', 'Relaticle\\CustomFields\\Tests\\Models\\User');

        $this->assertIsArray($metadata);
        $this->assertEquals(CustomFieldType::MULTI_SELECT, $metadata['type']);
        $this->assertTrue($metadata['is_optionable']);
        $this->assertTrue($metadata['has_multiple_values']);
        $this->assertIsArray($metadata['compatible_operators']);
        $this->assertContains(Operator::CONTAINS, $metadata['compatible_operators']);
        $this->assertContains(Operator::NOT_CONTAINS, $metadata['compatible_operators']);
    }

    #[Test]
    public function it_returns_null_metadata_for_non_existent_fields(): void
    {
        $metadata = $this->visibilityService->getFieldMetadata('nonexistent', 'Relaticle\\CustomFields\\Tests\\Models\\User');

        $this->assertNull($metadata);
    }

    #[Test]
    public function it_evaluates_single_option_field_visibility_correctly(): void
    {
        $priorityField = CustomField::factory()->create([
            'type' => CustomFieldType::SELECT,
            'code' => 'priority',
            'entity_type' => 'Relaticle\\CustomFields\\Tests\\Models\\User',
        ]);

        $highOption = CustomFieldOption::factory()->create([
            'custom_field_id' => $priorityField->id,
            'name' => 'High',
        ]);

        $mediumOption = CustomFieldOption::factory()->create([
            'custom_field_id' => $priorityField->id,
            'name' => 'Medium',
        ]);

        $dependentField = CustomField::factory()->create([
            'type' => CustomFieldType::TEXT,
            'code' => 'urgent_notes',
            'entity_type' => 'Relaticle\\CustomFields\\Tests\\Models\\User',
            'settings' => [
                'visibility' => [
                    'mode' => Mode::SHOW_WHEN->value,
                    'logic' => Logic::ALL->value,
                    'conditions' => [
                        [
                            'field' => 'priority',
                            'operator' => Operator::EQUALS->value,
                            'value' => 'High',
                        ],
                    ],
                    'always_save' => false,
                ],
            ],
        ]);

        // Test with high priority - should be visible
        $fieldValues = ['priority' => 'High'];
        $normalizedValues = $this->visibilityService->normalizeFieldValues(['priority'], $fieldValues);
        $isVisible = $this->visibilityService->shouldShowField($dependentField, $normalizedValues);
        $this->assertTrue($isVisible);

        // Test with medium priority - should not be visible
        $fieldValues = ['priority' => 'Medium'];
        $normalizedValues = $this->visibilityService->normalizeFieldValues(['priority'], $fieldValues);
        $isVisible = $this->visibilityService->shouldShowField($dependentField, $normalizedValues);
        $this->assertFalse($isVisible);
    }

    #[Test]
    public function it_evaluates_multi_option_field_visibility_correctly(): void
    {
        $tagsField = CustomField::factory()->create([
            'type' => CustomFieldType::MULTI_SELECT,
            'code' => 'user_tags',
            'entity_type' => 'Relaticle\\CustomFields\\Tests\\Models\\User',
        ]);

        CustomFieldOption::factory()->create([
            'custom_field_id' => $tagsField->id,
            'name' => 'VIP',
        ]);

        CustomFieldOption::factory()->create([
            'custom_field_id' => $tagsField->id,
            'name' => 'Premium',
        ]);

        CustomFieldOption::factory()->create([
            'custom_field_id' => $tagsField->id,
            'name' => 'Standard',
        ]);

        $dependentField = CustomField::factory()->create([
            'type' => CustomFieldType::TEXT,
            'code' => 'special_notes',
            'entity_type' => 'Relaticle\\CustomFields\\Tests\\Models\\User',
            'settings' => [
                'visibility' => [
                    'mode' => Mode::SHOW_WHEN->value,
                    'logic' => Logic::ALL->value,
                    'conditions' => [
                        [
                            'field' => 'user_tags',
                            'operator' => Operator::CONTAINS->value,
                            'value' => 'VIP',
                        ],
                    ],
                    'always_save' => false,
                ],
            ],
        ]);

        // Test with VIP tag - should be visible
        $fieldValues = ['user_tags' => ['VIP', 'Premium']];
        $normalizedValues = $this->visibilityService->normalizeFieldValues(['user_tags'], $fieldValues);
        $isVisible = $this->visibilityService->shouldShowField($dependentField, $normalizedValues);
        $this->assertTrue($isVisible);

        // Test without VIP tag - should not be visible
        $fieldValues = ['user_tags' => ['Premium', 'Standard']];
        $normalizedValues = $this->visibilityService->normalizeFieldValues(['user_tags'], $fieldValues);
        $isVisible = $this->visibilityService->shouldShowField($dependentField, $normalizedValues);
        $this->assertFalse($isVisible);

        // Test with no tags - should not be visible
        $fieldValues = ['user_tags' => []];
        $normalizedValues = $this->visibilityService->normalizeFieldValues(['user_tags'], $fieldValues);
        $isVisible = $this->visibilityService->shouldShowField($dependentField, $normalizedValues);
        $this->assertFalse($isVisible);
    }

    #[Test]
    public function it_handles_complex_multi_condition_visibility_with_optionable_fields(): void
    {
        $statusField = CustomField::factory()->create([
            'type' => CustomFieldType::SELECT,
            'code' => 'status',
            'entity_type' => 'Relaticle\\CustomFields\\Tests\\Models\\User',
        ]);

        CustomFieldOption::factory()->create([
            'custom_field_id' => $statusField->id,
            'name' => 'Active',
        ]);

        CustomFieldOption::factory()->create([
            'custom_field_id' => $statusField->id,
            'name' => 'Inactive',
        ]);

        $rolesField = CustomField::factory()->create([
            'type' => CustomFieldType::MULTI_SELECT,
            'code' => 'roles',
            'entity_type' => 'Relaticle\\CustomFields\\Tests\\Models\\User',
        ]);

        CustomFieldOption::factory()->create([
            'custom_field_id' => $rolesField->id,
            'name' => 'Admin',
        ]);

        CustomFieldOption::factory()->create([
            'custom_field_id' => $rolesField->id,
            'name' => 'Editor',
        ]);

        CustomFieldOption::factory()->create([
            'custom_field_id' => $rolesField->id,
            'name' => 'Viewer',
        ]);

        $dependentField = CustomField::factory()->create([
            'type' => CustomFieldType::TEXT,
            'code' => 'admin_notes',
            'entity_type' => 'Relaticle\\CustomFields\\Tests\\Models\\User',
            'settings' => [
                'visibility' => [
                    'mode' => Mode::SHOW_WHEN->value,
                    'logic' => Logic::ALL->value,
                    'conditions' => [
                        [
                            'field' => 'status',
                            'operator' => Operator::EQUALS->value,
                            'value' => 'Active',
                        ],
                        [
                            'field' => 'roles',
                            'operator' => Operator::CONTAINS->value,
                            'value' => 'Admin',
                        ],
                    ],
                    'always_save' => false,
                ],
            ],
        ]);

        // Test with active status and admin role - should be visible
        $fieldValues = [
            'status' => 'Active',
            'roles' => ['Admin', 'Editor'],
        ];
        $normalizedValues = $this->visibilityService->normalizeFieldValues(['status', 'roles'], $fieldValues);
        $isVisible = $this->visibilityService->shouldShowField($dependentField, $normalizedValues);
        $this->assertTrue($isVisible);

        // Test with active status but no admin role - should not be visible
        $fieldValues = [
            'status' => 'Active',
            'roles' => ['Editor', 'Viewer'],
        ];
        $normalizedValues = $this->visibilityService->normalizeFieldValues(['status', 'roles'], $fieldValues);
        $isVisible = $this->visibilityService->shouldShowField($dependentField, $normalizedValues);
        $this->assertFalse($isVisible);

        // Test with inactive status and admin role - should not be visible
        $fieldValues = [
            'status' => 'Inactive',
            'roles' => ['Admin'],
        ];
        $normalizedValues = $this->visibilityService->normalizeFieldValues(['status', 'roles'], $fieldValues);
        $isVisible = $this->visibilityService->shouldShowField($dependentField, $normalizedValues);
        $this->assertFalse($isVisible);
    }

    #[Test]
    public function it_handles_or_logic_with_optionable_fields(): void
    {
        $priorityField = CustomField::factory()->create([
            'type' => CustomFieldType::SELECT,
            'code' => 'priority',
            'entity_type' => 'Relaticle\\CustomFields\\Tests\\Models\\User',
        ]);

        CustomFieldOption::factory()->create([
            'custom_field_id' => $priorityField->id,
            'name' => 'High',
        ]);

        CustomFieldOption::factory()->create([
            'custom_field_id' => $priorityField->id,
            'name' => 'Critical',
        ]);

        $dependentField = CustomField::factory()->create([
            'type' => CustomFieldType::TEXT,
            'code' => 'urgent_action',
            'entity_type' => 'Relaticle\\CustomFields\\Tests\\Models\\User',
            'settings' => [
                'visibility' => [
                    'mode' => Mode::SHOW_WHEN->value,
                    'logic' => Logic::ANY->value,
                    'conditions' => [
                        [
                            'field' => 'priority',
                            'operator' => Operator::EQUALS->value,
                            'value' => 'High',
                        ],
                        [
                            'field' => 'priority',
                            'operator' => Operator::EQUALS->value,
                            'value' => 'Critical',
                        ],
                    ],
                    'always_save' => false,
                ],
            ],
        ]);

        // Test with High priority - should be visible
        $fieldValues = ['priority' => 'High'];
        $normalizedValues = $this->visibilityService->normalizeFieldValues(['priority'], $fieldValues);
        $isVisible = $this->visibilityService->shouldShowField($dependentField, $normalizedValues);
        $this->assertTrue($isVisible);

        // Test with Critical priority - should be visible
        $fieldValues = ['priority' => 'Critical'];
        $normalizedValues = $this->visibilityService->normalizeFieldValues(['priority'], $fieldValues);
        $isVisible = $this->visibilityService->shouldShowField($dependentField, $normalizedValues);
        $this->assertTrue($isVisible);

        // Test with Medium priority - should not be visible
        $fieldValues = ['priority' => 'Medium'];
        $normalizedValues = $this->visibilityService->normalizeFieldValues(['priority'], $fieldValues);
        $isVisible = $this->visibilityService->shouldShowField($dependentField, $normalizedValues);
        $this->assertFalse($isVisible);
    }

    #[Test]
    public function it_correctly_normalizes_option_id_values_to_names(): void
    {
        $categoryField = CustomField::factory()->create([
            'type' => CustomFieldType::SELECT,
            'code' => 'category',
            'entity_type' => 'Relaticle\\CustomFields\\Tests\\Models\\User',
        ]);

        $businessOption = CustomFieldOption::factory()->create([
            'custom_field_id' => $categoryField->id,
            'name' => 'Business',
        ]);

        $personalOption = CustomFieldOption::factory()->create([
            'custom_field_id' => $categoryField->id,
            'name' => 'Personal',
        ]);

        // Test normalization with option ID
        $rawValues = ['category' => (string) $businessOption->id];
        $normalizedValues = $this->visibilityService->normalizeFieldValues(['category'], $rawValues);

        $this->assertEquals('Business', $normalizedValues['category']);

        // Test normalization with option name (should remain unchanged)
        $rawValues = ['category' => 'Personal'];
        $normalizedValues = $this->visibilityService->normalizeFieldValues(['category'], $rawValues);

        $this->assertEquals('Personal', $normalizedValues['category']);
    }

    #[Test]
    public function it_correctly_normalizes_multi_value_option_fields(): void
    {
        $skillsField = CustomField::factory()->create([
            'type' => CustomFieldType::MULTI_SELECT,
            'code' => 'skills',
            'entity_type' => 'Relaticle\\CustomFields\\Tests\\Models\\User',
        ]);

        $phpOption = CustomFieldOption::factory()->create([
            'custom_field_id' => $skillsField->id,
            'name' => 'PHP',
        ]);

        $jsOption = CustomFieldOption::factory()->create([
            'custom_field_id' => $skillsField->id,
            'name' => 'JavaScript',
        ]);

        $pythonOption = CustomFieldOption::factory()->create([
            'custom_field_id' => $skillsField->id,
            'name' => 'Python',
        ]);

        // Test normalization with mixed IDs and names
        $rawValues = ['skills' => [(string) $phpOption->id, 'JavaScript', (string) $pythonOption->id]];
        $normalizedValues = $this->visibilityService->normalizeFieldValues(['skills'], $rawValues);

        $this->assertIsArray($normalizedValues['skills']);
        $this->assertContains('PHP', $normalizedValues['skills']);
        $this->assertContains('JavaScript', $normalizedValues['skills']);
        $this->assertContains('Python', $normalizedValues['skills']);
    }

    #[Test]
    public function it_caches_field_options_and_metadata_for_performance(): void
    {
        $selectField = CustomField::factory()->create([
            'type' => CustomFieldType::SELECT,
            'code' => 'test_field',
            'entity_type' => 'Relaticle\\CustomFields\\Tests\\Models\\User',
        ]);

        CustomFieldOption::factory()->create([
            'custom_field_id' => $selectField->id,
            'name' => 'Option1',
        ]);

        // First call - should hit the database
        $options1 = $this->visibilityService->getFieldOptions('test_field', 'Relaticle\\CustomFields\\Tests\\Models\\User');
        $metadata1 = $this->visibilityService->getFieldMetadata('test_field', 'Relaticle\\CustomFields\\Tests\\Models\\User');

        // Second call - should use cache
        $options2 = $this->visibilityService->getFieldOptions('test_field', 'Relaticle\\CustomFields\\Tests\\Models\\User');
        $metadata2 = $this->visibilityService->getFieldMetadata('test_field', 'Relaticle\\CustomFields\\Tests\\Models\\User');

        $this->assertEquals($options1, $options2);
        $this->assertEquals($metadata1, $metadata2);
    }
}