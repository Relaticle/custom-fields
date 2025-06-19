<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Relaticle\CustomFields\Data\CustomFieldSettingsData;
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
                'conditional_visibility' => [
                    'enabled' => true,
                    'logic' => 'all',
                    'conditions' => [
                        ['field' => 'status', 'operator' => '=', 'value' => 'active'],
                    ],
                ],
            ]),
        ]);

        $this->assertTrue($field->hasConditionalVisibility());

        $config = $field->getConditionalVisibilityConfig();
        $this->assertTrue($config['enabled']);
        $this->assertEquals('all', $config['logic']);
        $this->assertCount(1, $config['conditions']);
    }

    public function test_it_evaluates_field_visibility_from_settings(): void
    {
        $field = CustomField::factory()->create([
            'type' => CustomFieldType::TEXT,
            'settings' => CustomFieldSettingsData::from([
                'conditional_visibility' => [
                    'enabled' => true,
                    'logic' => 'all',
                    'conditions' => [
                        ['field' => 'priority', 'operator' => '>', 'value' => '5'],
                        ['field' => 'category', 'operator' => '=', 'value' => 'urgent'],
                    ],
                ],
            ]),
        ]);

        $formData = ['priority' => '7', 'category' => 'urgent'];
        $shouldShow = $this->service->shouldFieldBeVisible($field->getConditionalVisibilityConfig(), $formData);
        $this->assertTrue($shouldShow);

        $formData = ['priority' => '3', 'category' => 'urgent'];
        $shouldShow = $this->service->shouldFieldBeVisible($field->getConditionalVisibilityConfig(), $formData);
        $this->assertFalse($shouldShow);
    }

    public function test_it_works_with_multiple_field_types(): void
    {
        // Text field with simple condition
        $textField = CustomField::factory()->create([
            'code' => 'description',
            'type' => CustomFieldType::TEXT,
            'settings' => CustomFieldSettingsData::from([
                'conditional_visibility' => [
                    'enabled' => true,
                    'logic' => 'all',
                    'conditions' => [
                        ['field' => 'type', 'operator' => '=', 'value' => 'detailed'],
                    ],
                ],
            ]),
        ]);

        // Select field with multiple conditions
        $selectField = CustomField::factory()->create([
            'code' => 'priority_level',
            'type' => CustomFieldType::SELECT,
            'settings' => CustomFieldSettingsData::from([
                'conditional_visibility' => [
                    'enabled' => true,
                    'logic' => 'any',
                    'conditions' => [
                        ['field' => 'status', 'operator' => '=', 'value' => 'active'],
                        ['field' => 'category', 'operator' => '=', 'value' => 'urgent'],
                    ],
                ],
            ]),
        ]);

        $formData = [
            'type' => 'detailed',
            'status' => 'active',
            'category' => 'normal',
        ];

        $this->assertTrue($this->service->shouldFieldBeVisible($textField->getConditionalVisibilityConfig(), $formData));
        $this->assertTrue($this->service->shouldFieldBeVisible($selectField->getConditionalVisibilityConfig(), $formData));

        $formData['type'] = 'simple';
        $formData['status'] = 'inactive';

        $this->assertFalse($this->service->shouldFieldBeVisible($textField->getConditionalVisibilityConfig(), $formData));
        $this->assertFalse($this->service->shouldFieldBeVisible($selectField->getConditionalVisibilityConfig(), $formData));
    }

    public function test_it_supports_array_and_multi_value_fields(): void
    {
        $field = CustomField::factory()->create([
            'type' => CustomFieldType::MULTI_SELECT,
            'settings' => CustomFieldSettingsData::from([
                'conditional_visibility' => [
                    'enabled' => true,
                    'logic' => 'all',
                    'conditions' => [
                        ['field' => 'features', 'operator' => 'contains', 'value' => 'api_access'],
                    ],
                ],
            ]),
        ]);

        $formData = ['features' => ['basic', 'api_access', 'premium']];
        $shouldShow = $this->service->shouldFieldBeVisible($field->getConditionalVisibilityConfig(), $formData);
        $this->assertTrue($shouldShow);

        $formData = ['features' => ['basic', 'premium']];
        $shouldShow = $this->service->shouldFieldBeVisible($field->getConditionalVisibilityConfig(), $formData);
        $this->assertFalse($shouldShow);
    }

    public function test_it_handles_empty_and_null_checks(): void
    {
        $field = CustomField::factory()->create([
            'type' => CustomFieldType::TEXT,
            'settings' => CustomFieldSettingsData::from([
                'conditional_visibility' => [
                    'enabled' => true,
                    'logic' => 'all',
                    'conditions' => [
                        ['field' => 'notes', 'operator' => 'not_empty'],
                    ],
                ],
            ]),
        ]);

        $formData = ['notes' => 'Some notes here'];
        $shouldShow = $this->service->shouldFieldBeVisible($field->getConditionalVisibilityConfig(), $formData);
        $this->assertTrue($shouldShow);

        $formData = ['notes' => ''];
        $shouldShow = $this->service->shouldFieldBeVisible($field->getConditionalVisibilityConfig(), $formData);
        $this->assertFalse($shouldShow);

        $formData = ['notes' => null];
        $shouldShow = $this->service->shouldFieldBeVisible($field->getConditionalVisibilityConfig(), $formData);
        $this->assertFalse($shouldShow);
    }

    public function test_it_provides_configuration_for_frontend(): void
    {
        $field1 = CustomField::factory()->create([
            'code' => 'advanced_options',
            'type' => CustomFieldType::TEXT,
            'settings' => CustomFieldSettingsData::from([
                'conditional_visibility' => [
                    'enabled' => true,
                    'logic' => 'all',
                    'conditions' => [
                        ['field' => 'user_type', 'operator' => '=', 'value' => 'admin'],
                    ],
                ],
            ]),
        ]);

        $field2 = CustomField::factory()->create([
            'code' => 'premium_features',
            'type' => CustomFieldType::SELECT,
            'settings' => CustomFieldSettingsData::from([
                'conditional_visibility' => [
                    'enabled' => true,
                    'logic' => 'any',
                    'conditions' => [
                        ['field' => 'subscription', 'operator' => '=', 'value' => 'premium'],
                        ['field' => 'trial_active', 'operator' => '=', 'value' => 'true'],
                    ],
                ],
            ]),
        ]);

        $fields = collect([$field1, $field2]);

        // Generate configuration for frontend
        $jsConfig = [];
        foreach ($fields as $field) {
            if ($field->hasConditionalVisibility()) {
                $jsConfig[$field->code] = $field->getConditionalVisibilityConfig();
            }
        }

        $this->assertArrayHasKey('advanced_options', $jsConfig);
        $this->assertArrayHasKey('premium_features', $jsConfig);
        $this->assertTrue($jsConfig['advanced_options']['enabled']);
        $this->assertTrue($jsConfig['premium_features']['enabled']);
    }
}
