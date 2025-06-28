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
        // Arrange
        $field1 = CustomField::factory()
            ->create([
                'custom_field_section_id' => $this->section->getKey(),
                'entity_type' => $this->userEntityType,
                'sort_order' => 0,
                'type' => CustomFieldType::TEXT,
            ]);
        $field2 = CustomField::factory()
            ->create([
                'custom_field_section_id' => $this->section->getKey(),
                'entity_type' => $this->userEntityType,
                'sort_order' => 1,
                'type' => CustomFieldType::TEXT,
            ]);

        // Act
        livewire(ManageCustomFieldSection::class, [
            'section' => $this->section,
            'entityType' => $this->userEntityType,
        ])->call('updateFieldsOrder', $this->section->getKey(), [$field2->getKey(), $field1->getKey()]);

        // Assert
        $this->assertDatabaseHas(CustomField::class, [
            'id' => $field2->getKey(),
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas(CustomField::class, [
            'id' => $field1->getKey(),
            'sort_order' => 1,
        ]);
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