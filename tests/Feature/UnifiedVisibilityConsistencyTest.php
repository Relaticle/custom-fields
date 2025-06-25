<?php

declare(strict_types=1);

use Relaticle\CustomFields\Data\VisibilityData;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Enums\Logic;
use Relaticle\CustomFields\Enums\Mode;
use Relaticle\CustomFields\Enums\Operator;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Services\Visibility\BackendVisibilityService;
use Relaticle\CustomFields\Services\Visibility\CoreVisibilityLogicService;
use Relaticle\CustomFields\Services\Visibility\FrontendVisibilityService;
use Relaticle\CustomFields\Tests\Models\User;

beforeEach(function () {
    $this->section = CustomFieldSection::factory()->create([
        'name' => 'Unified Test Section',
        'entity_type' => User::class,
        'active' => true,
    ]);

    // Create trigger field (select type)
    $this->triggerField = CustomField::factory()->create([
        'custom_field_section_id' => $this->section->id,
        'name' => 'Status',
        'code' => 'status',
        'type' => CustomFieldType::SELECT,
    ]);

    // Create conditional field that shows when status equals "active"
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

    // Create hide_when field that hides when status equals "disabled"
    $this->hideWhenField = CustomField::factory()->create([
        'custom_field_section_id' => $this->section->id,
        'name' => 'Actions',
        'code' => 'actions',
        'type' => CustomFieldType::TEXT,
        'settings' => [
            'visibility' => [
                'mode' => Mode::HIDE_WHEN,
                'logic' => Logic::ALL,
                'conditions' => [
                    [
                        'field_code' => 'status',
                        'operator' => Operator::EQUALS,
                        'value' => 'disabled',
                    ],
                ],
                'always_save' => false,
            ],
        ],
    ]);

    // Create multi-condition field (OR logic)
    $this->multiConditionField = CustomField::factory()->create([
        'custom_field_section_id' => $this->section->id,
        'name' => 'Advanced',
        'code' => 'advanced',
        'type' => CustomFieldType::TEXT,
        'settings' => [
            'visibility' => [
                'mode' => Mode::SHOW_WHEN,
                'logic' => Logic::ANY,
                'conditions' => [
                    [
                        'field_code' => 'status',
                        'operator' => Operator::EQUALS,
                        'value' => 'active',
                    ],
                    [
                        'field_code' => 'status',
                        'operator' => Operator::EQUALS,
                        'value' => 'pending',
                    ],
                ],
                'always_save' => false,
            ],
        ],
    ]);

    // Always visible field for control
    $this->alwaysVisibleField = CustomField::factory()->create([
        'custom_field_section_id' => $this->section->id,
        'name' => 'Name',
        'code' => 'name',
        'type' => CustomFieldType::TEXT,
    ]);

    $this->user = User::factory()->create();
    $this->coreLogic = app(CoreVisibilityLogicService::class);
    $this->backendService = app(BackendVisibilityService::class);
    $this->frontendService = app(FrontendVisibilityService::class);
});

test('core logic service extracts visibility data consistently', function () {
    // Test visibility data extraction
    $conditionalVisibility = $this->coreLogic->getVisibilityData($this->conditionalField);
    $alwaysVisibleData = $this->coreLogic->getVisibilityData($this->alwaysVisibleField);

    expect($conditionalVisibility)->toBeInstanceOf(VisibilityData::class)
        ->and($conditionalVisibility->mode)->toBe(Mode::SHOW_WHEN)
        ->and($conditionalVisibility->logic)->toBe(Logic::ALL)
        ->and($conditionalVisibility->conditions)->toHaveCount(1)
        ->and($alwaysVisibleData)->toBeNull();

    // Test visibility condition extraction
    expect($this->coreLogic->hasVisibilityConditions($this->conditionalField))->toBeTrue()
        ->and($this->coreLogic->hasVisibilityConditions($this->alwaysVisibleField))->toBeFalse();

    // Test dependent fields
    $dependentFields = $this->coreLogic->getDependentFields($this->conditionalField);
    expect($dependentFields)->toBe(['status']);
});

test('backend and frontend services use identical core logic', function () {
    $fields = collect([
        $this->triggerField,
        $this->conditionalField,
        $this->hideWhenField,
        $this->multiConditionField,
        $this->alwaysVisibleField,
    ]);

    // Test scenario 1: status = "active"
    $this->user->saveCustomFieldValue($this->triggerField, 'active');

    $fieldValues = $this->backendService->extractFieldValues($this->user, $fields);
    $visibleFields = $this->backendService->getVisibleFields($this->user, $fields);

    // Should show: trigger, conditional (show_when active), hideWhen (not disabled), multiCondition (active), always
    expect($visibleFields)->toHaveCount(5)
        ->and($visibleFields->pluck('code')->toArray())
        ->toContain('status', 'details', 'actions', 'advanced', 'name');

    // Test scenario 2: status = "disabled"
    $this->user->saveCustomFieldValue($this->triggerField, 'disabled');

    $visibleFields = $this->backendService->getVisibleFields($this->user, $fields);

    // Should show: trigger, always (conditional hidden, hideWhen hidden, multiCondition hidden)
    expect($visibleFields)->toHaveCount(2)
        ->and($visibleFields->pluck('code')->toArray())
        ->toContain('status', 'name')
        ->and($visibleFields->pluck('code')->toArray())
        ->not()->toContain('details', 'actions', 'advanced');

    // Test scenario 3: status = "pending"
    $this->user->saveCustomFieldValue($this->triggerField, 'pending');

    $visibleFields = $this->backendService->getVisibleFields($this->user, $fields);

    // Should show: trigger, hideWhen (not disabled), multiCondition (pending), always
    expect($visibleFields)->toHaveCount(4)
        ->and($visibleFields->pluck('code')->toArray())
        ->toContain('status', 'actions', 'advanced', 'name')
        ->and($visibleFields->pluck('code')->toArray())
        ->not()->toContain('details');
});

test('frontend service generates valid JavaScript expressions', function () {
    $fields = collect([$this->triggerField, $this->conditionalField, $this->alwaysVisibleField]);

    // Test JavaScript expression generation
    $jsExpression = $this->frontendService->buildVisibilityExpression($this->conditionalField, $fields);

    expect($jsExpression)->toBeString()
        ->and($jsExpression)->toContain("\$get('custom_fields.status')")
        ->and($jsExpression)->toContain("'active'");

    // Test always visible field returns null (no expression needed)
    $alwaysVisibleExpression = $this->frontendService->buildVisibilityExpression($this->alwaysVisibleField, $fields);
    expect($alwaysVisibleExpression)->toBeNull();

    // Test export to JavaScript format
    $jsData = $this->frontendService->exportVisibilityLogicToJs($fields);

    expect($jsData)->toHaveKeys(['fields', 'dependencies'])
        ->and($jsData['fields'])->toHaveKey('details')
        ->and($jsData['fields']['details']['has_visibility_conditions'])->toBeTrue()
        ->and($jsData['fields']['name']['has_visibility_conditions'])->toBeFalse();
});

test('complex conditions work identically in backend and frontend', function () {
    // Create a more complex scenario with nested dependencies
    $dependentField = CustomField::factory()->create([
        'custom_field_section_id' => $this->section->id,
        'name' => 'Dependent',
        'code' => 'dependent',
        'type' => CustomFieldType::TEXT,
        'settings' => [
            'visibility' => [
                'mode' => Mode::SHOW_WHEN,
                'logic' => Logic::ALL,
                'conditions' => [
                    [
                        'field_code' => 'details',
                        'operator' => Operator::IS_NOT_EMPTY,
                        'value' => null,
                    ],
                ],
                'always_save' => false,
            ],
        ],
    ]);

    $fields = collect([
        $this->triggerField,
        $this->conditionalField,
        $dependentField,
        $this->alwaysVisibleField,
    ]);

    // Scenario: status = "active", details filled
    $this->user->saveCustomFieldValue($this->triggerField, 'active');
    $this->user->saveCustomFieldValue($this->conditionalField, 'Some details');

    // Backend evaluation
    $backendVisible = $this->backendService->getVisibleFields($this->user, $fields);

    // Should show all fields (cascading visibility works)
    expect($backendVisible)->toHaveCount(4)
        ->and($backendVisible->pluck('code')->toArray())
        ->toContain('status', 'details', 'dependent', 'name');

    // Frontend expression generation should work
    $dependentExpression = $this->frontendService->buildVisibilityExpression($dependentField, $fields);
    expect($dependentExpression)->toBeString()
        ->and($dependentExpression)->toContain('custom_fields.details');

    // Test with empty details
    $this->user->saveCustomFieldValue($this->conditionalField, '');
    $backendVisible = $this->backendService->getVisibleFields($this->user, $fields);

    // Dependent should be hidden when details is empty
    expect($backendVisible->pluck('code')->toArray())
        ->not()->toContain('dependent');
});

test('operator compatibility and validation work correctly', function () {
    $textField = $this->alwaysVisibleField; // TEXT type
    $selectField = $this->triggerField; // SELECT type

    // Test operator compatibility
    expect($this->coreLogic->isOperatorCompatible(Operator::EQUALS, $textField))->toBeTrue()
        ->and($this->coreLogic->isOperatorCompatible(Operator::CONTAINS, $textField))->toBeTrue()
        ->and($this->coreLogic->isOperatorCompatible(Operator::IS_EMPTY, $textField))->toBeTrue()
        ->and($this->coreLogic->isOperatorCompatible(Operator::EQUALS, $selectField))->toBeTrue()
        ->and($this->coreLogic->isOperatorCompatible(Operator::CONTAINS, $selectField))->toBeTrue();

    // Test validation error messages
    expect($this->coreLogic->getOperatorValidationError(Operator::EQUALS, $textField))->toBeNull();

    // Test field metadata
    $metadata = $this->coreLogic->getFieldMetadata($this->conditionalField);
    expect($metadata)->toHaveKeys([
        'code', 'type', 'category', 'is_optionable', 'has_multiple_values',
        'compatible_operators', 'has_visibility_conditions', 'visibility_mode',
        'visibility_logic', 'visibility_conditions', 'dependent_fields', 'always_save',
    ])
        ->and($metadata['has_visibility_conditions'])->toBeTrue()
        ->and($metadata['visibility_mode'])->toBe('show_when');
});

test('dependency calculation works consistently across services', function () {
    $fields = collect([
        $this->triggerField,
        $this->conditionalField,
        $this->multiConditionField,
        $this->alwaysVisibleField,
    ]);

    $dependencies = $this->coreLogic->calculateDependencies($fields);

    // Status field should have dependents: details and advanced
    expect($dependencies)->toHaveKey('status')
        ->and($dependencies['status'])->toContain('details', 'advanced');

    // Backend service should return same dependencies
    $backendDependencies = $this->backendService->calculateDependencies($fields);
    expect($backendDependencies)->toEqual($dependencies);

    // Frontend export should include same dependencies
    $frontendExport = $this->frontendService->exportVisibilityLogicToJs($fields);
    expect($frontendExport['dependencies'])->toEqual($dependencies);
});

test('empty and null value handling is consistent', function () {
    $fields = collect([$this->triggerField, $this->conditionalField, $this->alwaysVisibleField]);

    // Test with no field values set
    $visibleFields = $this->backendService->getVisibleFields($this->user, $fields);

    // Only always visible and trigger should show (conditional should be hidden)
    expect($visibleFields->pluck('code')->toArray())
        ->toContain('status', 'name')
        ->and($visibleFields->pluck('code')->toArray())
        ->not()->toContain('details');

    // Test field value extraction with empty values
    $fieldValues = $this->backendService->extractFieldValues($this->user, $fields);
    expect($fieldValues)->toBeArray()
        ->and($fieldValues['status'])->toBeNull();

    // Frontend should handle null values in expressions
    $jsExpression = $this->frontendService->buildVisibilityExpression($this->conditionalField, $fields);
    expect($jsExpression)->toBeString(); // Should generate valid expression even with null comparison
});
