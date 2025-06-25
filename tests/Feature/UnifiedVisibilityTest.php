<?php

declare(strict_types=1);

use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Enums\Logic;
use Relaticle\CustomFields\Enums\Mode;
use Relaticle\CustomFields\Enums\Operator;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Services\CustomFieldVisibilityService;
use Relaticle\CustomFields\Tests\Models\User;

beforeEach(function () {
    $this->section = CustomFieldSection::factory()->create([
        'name' => 'Test Section',
        'entity_type' => User::class,
        'active' => true,
    ]);

    // Create a trigger field
    $this->triggerField = CustomField::factory()->create([
        'custom_field_section_id' => $this->section->id,
        'name' => 'Status',
        'code' => 'status',
        'type' => CustomFieldType::SELECT,
    ]);

    // Create a conditional field that shows when status equals "active"
    $this->conditionalField = CustomField::factory()->create([
        'custom_field_section_id' => $this->section->id,
        'name' => 'Details',
        'code' => 'details',
        'type' => CustomFieldType::TEXT,
        'settings' => [
            'visibility' => [
                'mode' => Mode::SHOW_WHEN,
                'logic' => Logic::ALL,
                'conditions' => [
                    [
                        'field_code' => 'status',
                        'operator' => Operator::EQUALS,
                        'value' => 'active',
                    ],
                ],
                'always_save' => false,
            ],
        ],
    ]);

    // Always visible field for comparison
    $this->alwaysVisibleField = CustomField::factory()->create([
        'custom_field_section_id' => $this->section->id,
        'name' => 'Name',
        'code' => 'name',
        'type' => CustomFieldType::TEXT,
    ]);

    $this->user = User::factory()->create();
    $this->visibilityService = app(CustomFieldVisibilityService::class);
});

test('it extracts field values correctly', function () {
    // Set field values
    $this->user->saveCustomFieldValue($this->triggerField, 'active');
    $this->user->saveCustomFieldValue($this->conditionalField, 'Some details');
    $this->user->saveCustomFieldValue($this->alwaysVisibleField, 'John Doe');

    $fields = collect([$this->triggerField, $this->conditionalField, $this->alwaysVisibleField]);
    $fieldValues = $this->visibilityService->extractFieldValues($this->user, $fields);

    expect($fieldValues)->toHaveCount(3)
        ->and($fieldValues['status'])->toBe('active')
        ->and($fieldValues['details'])->toBe('Some details')
        ->and($fieldValues['name'])->toBe('John Doe');
});

test('it filters visible fields correctly when condition is met', function () {
    // Set trigger field to "active" - should make conditional field visible
    $this->user->saveCustomFieldValue($this->triggerField, 'active');

    $fields = collect([$this->triggerField, $this->conditionalField, $this->alwaysVisibleField]);
    $visibleFields = $this->visibilityService->getVisibleFields($this->user, $fields);

    // All fields should be visible
    expect($visibleFields)->toHaveCount(3)
        ->and($visibleFields->pluck('code')->toArray())->toContain('status', 'details', 'name');
});

test('it filters visible fields correctly when condition is not met', function () {
    // Set trigger field to "inactive" - should hide conditional field
    $this->user->saveCustomFieldValue($this->triggerField, 'inactive');

    $fields = collect([$this->triggerField, $this->conditionalField, $this->alwaysVisibleField]);
    $visibleFields = $this->visibilityService->getVisibleFields($this->user, $fields);

    // Only trigger and always visible fields should be visible
    expect($visibleFields)->toHaveCount(2)
        ->and($visibleFields->pluck('code')->toArray())->toContain('status', 'name')
        ->and($visibleFields->pluck('code')->toArray())->not()->toContain('details');
});

test('it checks individual field visibility correctly', function () {
    // Test when condition is met
    $this->user->saveCustomFieldValue($this->triggerField, 'active');
    $fields = collect([$this->triggerField, $this->conditionalField, $this->alwaysVisibleField]);

    expect($this->visibilityService->isFieldVisible($this->user, $this->conditionalField, $fields))
        ->toBeTrue();

    // Test when condition is not met
    $this->user->saveCustomFieldValue($this->triggerField, 'inactive');

    expect($this->visibilityService->isFieldVisible($this->user, $this->conditionalField, $fields))
        ->toBeFalse();

    // Always visible field should always be visible
    expect($this->visibilityService->isFieldVisible($this->user, $this->alwaysVisibleField, $fields))
        ->toBeTrue();
});

test('it normalizes field values correctly', function () {
    $this->user->saveCustomFieldValue($this->triggerField, 'active');
    $this->user->saveCustomFieldValue($this->alwaysVisibleField, 'John Doe');

    $fields = collect([$this->triggerField, $this->alwaysVisibleField]);
    $normalizedValues = $this->visibilityService->getNormalizedFieldValues($this->user, $fields);

    expect($normalizedValues)->toBeArray()
        ->and($normalizedValues['status'])->toBe('active')
        ->and($normalizedValues['name'])->toBe('John Doe');
});

test('it exports visibility logic to javascript format', function () {
    $fields = collect([$this->triggerField, $this->conditionalField, $this->alwaysVisibleField]);
    $jsData = $this->visibilityService->exportVisibilityLogicToJs($fields);

    expect($jsData)->toHaveKeys(['fields', 'dependencies'])
        ->and($jsData['fields'])->toHaveCount(3)
        ->and($jsData['fields']['details']['has_visibility'])->toBeTrue()
        ->and($jsData['fields']['details']['visibility_mode'])->toBe('show_when')
        ->and($jsData['fields']['name']['has_visibility'])->toBeFalse();
});

test('it validates visibility consistency', function () {
    $this->user->saveCustomFieldValue($this->triggerField, 'active');
    $this->user->saveCustomFieldValue($this->conditionalField, 'Some details');

    $fields = collect([$this->triggerField, $this->conditionalField, $this->alwaysVisibleField]);
    $validation = $this->visibilityService->validateVisibilityConsistency($this->user, $fields);

    expect($validation)->toHaveKeys([
        'total_fields',
        'visible_fields',
        'hidden_fields',
        'field_values_extracted',
        'normalized_values',
        'has_visibility_conditions',
        'visible_field_codes',
    ])
        ->and($validation['total_fields'])->toBe(3)
        ->and($validation['visible_fields'])->toBe(3)
        ->and($validation['hidden_fields'])->toBe(0)
        ->and($validation['has_visibility_conditions'])->toBe(1);
});

test('it handles empty field values gracefully', function () {
    // Don't set any field values
    $fields = collect([$this->triggerField, $this->conditionalField, $this->alwaysVisibleField]);

    // Should not crash and should handle null values
    $fieldValues = $this->visibilityService->extractFieldValues($this->user, $fields);
    $visibleFields = $this->visibilityService->getVisibleFields($this->user, $fields);

    expect($fieldValues)->toBeArray()
        ->and($visibleFields)->toBeInstanceOf(\Illuminate\Support\Collection::class);
});
