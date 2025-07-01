<?php

declare(strict_types=1);

use Relaticle\CustomFields\Enums\CustomFieldType;
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

describe('CustomFieldsPage - Field Validation Testing', function (): void {
    beforeEach(function (): void {
        $this->section = CustomFieldSection::factory()
            ->forEntityType($this->userEntityType)
            ->create();
    });

    it('validates field form requires name', function (): void {
        livewire(ManageCustomFieldSection::class, [
            'section' => $this->section,
            'entityType' => $this->userEntityType,
        ])->callAction('createField', [
            'name' => '',
            'code' => 'test_code',
            'type' => CustomFieldType::TEXT->value,
        ])->assertHasFormErrors(['name']);
    });

    it('validates field form requires code', function (): void {
        livewire(ManageCustomFieldSection::class, [
            'section' => $this->section,
            'entityType' => $this->userEntityType,
        ])->callAction('createField', [
            'name' => 'Test Field',
            'code' => '',
            'type' => CustomFieldType::TEXT->value,
        ])->assertHasFormErrors(['code']);
    });

    it('validates field form requires type', function (): void {
        livewire(ManageCustomFieldSection::class, [
            'section' => $this->section,
            'entityType' => $this->userEntityType,
        ])->callAction('createField', [
            'name' => 'Test Field',
            'code' => 'test_code',
            'type' => null,
        ])->assertHasFormErrors(['type']);
    });

    it('validates field code must be unique', function (): void {
        // Arrange - create existing field
        $existingField = CustomField::factory()->create([
            'custom_field_section_id' => $this->section->getKey(),
            'entity_type' => $this->userEntityType,
            'code' => 'existing_code',
            'type' => CustomFieldType::TEXT,
        ]);

        // Act & Assert - try to create field with same code
        livewire(ManageCustomFieldSection::class, [
            'section' => $this->section,
            'entityType' => $this->userEntityType,
        ])->callAction('createField', [
            'name' => 'New Field',
            'code' => $existingField->code,
            'type' => CustomFieldType::TEXT->value,
        ])->assertHasFormErrors(['code']);
    });
});
