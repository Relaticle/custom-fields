<?php

declare(strict_types=1);

use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Livewire\ManageCustomField;
use Relaticle\CustomFields\Livewire\ManageCustomFieldSection;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Tests\Fixtures\Models\Post;
use Relaticle\CustomFields\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    // Arrange: Create authenticated user for all tests
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Set up common test entity types for all tests
    $this->postEntityType = Post::class;
    $this->userEntityType = User::class;
});

describe('ManageCustomFieldSection - Field Management', function (): void {
    beforeEach(function (): void {
        $this->section = CustomFieldSection::factory()
            ->forEntityType($this->userEntityType)
            ->create();
    });

    it('can update field order within a section', function (): void {
        // Arrange - use enhanced factory methods
        $field1 = CustomField::factory()
            ->ofType(CustomFieldType::TEXT)
            ->create([
                'custom_field_section_id' => $this->section->getKey(),
                'entity_type' => $this->userEntityType,
                'sort_order' => 0,
            ]);
        $field2 = CustomField::factory()
            ->ofType(CustomFieldType::TEXT)
            ->create([
                'custom_field_section_id' => $this->section->getKey(),
                'entity_type' => $this->userEntityType,
                'sort_order' => 1,
            ]);

        // Act
        livewire(ManageCustomFieldSection::class, [
            'section' => $this->section,
            'entityType' => $this->userEntityType,
        ])->call('updateFieldsOrder', $this->section->getKey(), [$field2->getKey(), $field1->getKey()]);

        // Assert - use enhanced expectations
        expect($field2->fresh())->sort_order->toBe(0);
        expect($field1->fresh())->sort_order->toBe(1);
    });

    it('can update field width', function (): void {
        // Arrange
        $field = CustomField::factory()
            ->create([
                'custom_field_section_id' => $this->section->getKey(),
                'entity_type' => $this->userEntityType,
                'width' => 50,
                'type' => CustomFieldType::TEXT,
            ]);

        // Act
        livewire(ManageCustomFieldSection::class, [
            'section' => $this->section,
            'entityType' => $this->userEntityType,
        ])->call('fieldWidthUpdated', $field->getKey(), 100);

        // Assert
        $this->assertDatabaseHas(CustomField::class, [
            'id' => $field->getKey(),
            'width' => 100,
        ]);
    });

});

describe('ManageCustomField - Field Actions', function (): void {
    beforeEach(function (): void {
        $this->section = CustomFieldSection::factory()
            ->forEntityType($this->userEntityType)
            ->create();

        $this->field = CustomField::factory()
            ->create([
                'custom_field_section_id' => $this->section->getKey(),
                'entity_type' => $this->userEntityType,
                'type' => CustomFieldType::TEXT,
            ]);
    });

    it('can activate an inactive field', function (): void {
        // Arrange
        $inactiveField = CustomField::factory()
            ->create([
                'custom_field_section_id' => $this->section->getKey(),
                'entity_type' => $this->userEntityType,
                'active' => false,
                'type' => CustomFieldType::TEXT,
            ]);

        // Act
        livewire(ManageCustomField::class, [
            'field' => $inactiveField,
        ])->callAction('activate');

        // Assert
        $this->assertDatabaseHas(CustomField::class, [
            'id' => $inactiveField->getKey(),
            'active' => true,
        ]);
    });

    it('can deactivate an active field', function (): void {
        // Act
        livewire(ManageCustomField::class, [
            'field' => $this->field,
        ])->callAction('deactivate');

        // Assert
        $this->assertDatabaseHas(CustomField::class, [
            'id' => $this->field->getKey(),
            'active' => false,
        ]);
    });

    it('can delete an inactive non-system field', function (): void {
        // Arrange
        $deletableField = CustomField::factory()
            ->create([
                'custom_field_section_id' => $this->section->getKey(),
                'entity_type' => $this->userEntityType,
                'active' => false,
                'system_defined' => false,
                'type' => CustomFieldType::TEXT,
            ]);

        // Act
        livewire(ManageCustomField::class, [
            'field' => $deletableField,
        ])->callAction('delete');

        // Assert
        $this->assertDatabaseMissing(CustomField::class, [
            'id' => $deletableField->getKey(),
        ]);
    });

    it('cannot delete an active field', function (): void {
        livewire(ManageCustomField::class, [
            'field' => $this->field,
        ])->assertActionHidden('delete');
    });

    it('cannot delete a system-defined field', function (): void {
        // Arrange
        $systemField = CustomField::factory()
            ->create([
                'custom_field_section_id' => $this->section->getKey(),
                'entity_type' => $this->userEntityType,
                'active' => false,
                'system_defined' => true,
                'type' => CustomFieldType::TEXT,
            ]);

        // Act & Assert
        livewire(ManageCustomField::class, [
            'field' => $systemField,
        ])->assertActionHidden('delete');
    });

    it('dispatches width update event', function (): void {
        // Act & Assert
        livewire(ManageCustomField::class, [
            'field' => $this->field,
        ])->call('setWidth', $this->field->getKey(), 75)
            ->assertDispatched('field-width-updated', $this->field->getKey(), 75);
    });
});

describe('Enhanced field management with datasets', function (): void {
    beforeEach(function (): void {
        $this->section = CustomFieldSection::factory()
            ->forEntityType($this->userEntityType)
            ->create();
    });

    it('can create and manage fields of all types with proper configurations', function (string $fieldType, array $config, array $testValues, string $expectedComponent): void {
        // Create field with specific configuration
        $field = CustomField::factory()
            ->ofType(CustomFieldType::from($fieldType))
            ->withValidation($config['validation_rules'] ?? [])
            ->create([
                'custom_field_section_id' => $this->section->getKey(),
                'entity_type' => $this->userEntityType,
            ]);

        // Test field properties
        expect($field)
            ->toHaveFieldType($fieldType)
            ->toHaveCorrectComponent($expectedComponent)
            ->toBeActive();

        // Test validation rules if present
        foreach ($config['validation_rules'] ?? [] as $rule) {
            expect($field)->toHaveValidationRule($rule['name'], $rule['parameters'] ?? []);
        }

        // Test that the field can be managed through Livewire
        livewire(ManageCustomField::class, [
            'field' => $field,
        ])->assertSuccessful();
    })->with('field_type_configurations');

    it('can handle field state transitions correctly', function (): void {
        $field = CustomField::factory()
            ->ofType(CustomFieldType::TEXT)
            ->create([
                'custom_field_section_id' => $this->section->getKey(),
                'entity_type' => $this->userEntityType,
            ]);

        // Initially active
        expect($field)->toBeActive();

        // Deactivate
        livewire(ManageCustomField::class, [
            'field' => $field,
        ])->callAction('deactivate');

        expect($field->fresh())->toBeInactive();

        // Reactivate
        livewire(ManageCustomField::class, [
            'field' => $field->fresh(),
        ])->callAction('activate');

        expect($field->fresh())->toBeActive();
    });

    it('validates field deletion restrictions correctly', function (): void {
        // System-defined field cannot be deleted
        $systemField = CustomField::factory()
            ->ofType(CustomFieldType::TEXT)
            ->systemDefined()
            ->inactive()
            ->create([
                'custom_field_section_id' => $this->section->getKey(),
                'entity_type' => $this->userEntityType,
            ]);

        livewire(ManageCustomField::class, [
            'field' => $systemField,
        ])->assertActionHidden('delete');

        // Active field cannot be deleted
        $activeField = CustomField::factory()
            ->ofType(CustomFieldType::TEXT)
            ->create([
                'custom_field_section_id' => $this->section->getKey(),
                'entity_type' => $this->userEntityType,
            ]);

        livewire(ManageCustomField::class, [
            'field' => $activeField,
        ])->assertActionHidden('delete');

        // Only inactive, non-system fields can be deleted
        $deletableField = CustomField::factory()
            ->ofType(CustomFieldType::TEXT)
            ->inactive()
            ->create([
                'custom_field_section_id' => $this->section->getKey(),
                'entity_type' => $this->userEntityType,
            ]);

        livewire(ManageCustomField::class, [
            'field' => $deletableField,
        ])->callAction('delete');

        expect(CustomField::find($deletableField->id))->toBeNull();
    });

    it('handles complex field configurations with options', function (): void {
        $selectField = CustomField::factory()
            ->ofType(CustomFieldType::SELECT)
            ->withOptions([
                ['label' => 'Option 1', 'value' => 'opt1'],
                ['label' => 'Option 2', 'value' => 'opt2'],
                ['label' => 'Option 3', 'value' => 'opt3'],
            ])
            ->withValidation([
                ['name' => 'required', 'parameters' => []],
                ['name' => 'in', 'parameters' => ['opt1', 'opt2', 'opt3']],
            ])
            ->create([
                'custom_field_section_id' => $this->section->getKey(),
                'entity_type' => $this->userEntityType,
            ]);

        expect($selectField)
            ->toHaveFieldType('select')
            ->toHaveCorrectComponent('Select')
            ->toHaveValidationRule('required')
            ->toHaveValidationRule('in', ['opt1', 'opt2', 'opt3']);

        expect($selectField->options)->toHaveCount(3);
    });
});
