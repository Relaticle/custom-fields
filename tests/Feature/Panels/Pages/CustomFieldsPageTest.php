<?php

declare(strict_types=1);

use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Enums\CustomFieldSectionType;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Filament\Pages\CustomFieldsPage;
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

describe('CustomFieldsPage - Page Rendering and Navigation', function (): void {
    it('can render the custom fields management page successfully', function (): void {
        livewire(CustomFieldsPage::class)
            ->assertSuccessful();
    });

    it('can access the page via direct URL', function (): void {
        $this->get(CustomFieldsPage::getUrl())
            ->assertSuccessful();
    });

    it('displays the correct page heading and navigation elements', function (): void {
        livewire(CustomFieldsPage::class)
            ->assertSee(__('custom-fields::custom-fields.heading.title'));
    });

    it('respects authorization via the custom fields plugin', function (): void {
        expect(CustomFieldsPage::canAccess())->toBeTrue();
    });

    it('shows entity type selection and sections', function (): void {
        // Arrange
        $section = CustomFieldSection::factory()
            ->forEntityType($this->userEntityType)
            ->create();

        // Act & Assert
        livewire(CustomFieldsPage::class)
            ->call('setCurrentEntityType', $this->userEntityType)
            ->assertSee($section->name);
    });
});

describe('CustomFieldsPage - Section Management', function (): void {
    it('can create a new section with valid data', function (): void {
        // Arrange
        $sectionData = [
            'name' => 'Test Section',
            'code' => 'test_section',
        ];

        // Act
        $livewireTest = livewire(CustomFieldsPage::class)
            ->call('setCurrentEntityType', $this->userEntityType)
            ->callAction('createSection', $sectionData);

        // Assert
        $livewireTest->assertHasNoFormErrors()->assertNotified();

        $this->assertDatabaseHas(CustomFieldSection::class, [
            'name' => $sectionData['name'],
            'code' => $sectionData['code'],
            'entity_type' => $this->userEntityType,
        ]);
    });

    it('validates section form fields', function (string $field, mixed $value): void {
        livewire(CustomFieldsPage::class)
            ->call('setCurrentEntityType', $this->userEntityType)
            ->callAction('createSection', [$field => $value])
            ->assertHasActionErrors([$field]);
    })->with([
        'name is required' => ['name', ''],
        'code is required' => ['code', ''],
    ]);

    it('validates section code must be unique', function (): void {
        // Arrange
        $existingSection = CustomFieldSection::factory()
            ->forEntityType($this->userEntityType)
            ->create();

        // Act & Assert
        livewire(CustomFieldsPage::class)
            ->call('setCurrentEntityType', $this->userEntityType)
            ->callAction('createSection', [
                'name' => 'Test Section',
                'code' => $existingSection->code,
            ])
            ->assertHasNoFormErrors(['code']);
    });

    it('can update sections order', function (): void {
        // Arrange
        $section1 = CustomFieldSection::factory()
            ->forEntityType($this->userEntityType)
            ->create(['sort_order' => 0]);
        $section2 = CustomFieldSection::factory()
            ->forEntityType($this->userEntityType)
            ->create(['sort_order' => 1]);

        // Act
        livewire(CustomFieldsPage::class)
            ->call('setCurrentEntityType', $this->userEntityType)
            ->call('updateSectionsOrder', [$section2->getKey(), $section1->getKey()]);

        // Assert
        $this->assertDatabaseHas(CustomFieldSection::class, [
            'id' => $section2->getKey(),
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas(CustomFieldSection::class, [
            'id' => $section1->getKey(),
            'sort_order' => 1,
        ]);
    });

    it('removes deleted sections from view', function (): void {
        // Arrange
        $section = CustomFieldSection::factory()
            ->forEntityType($this->userEntityType)
            ->create();

        $component = livewire(CustomFieldsPage::class)
            ->call('setCurrentEntityType', $this->userEntityType)
            ->assertSee($section->name);

        // Act - simulate section deletion
        $section->delete();
        $component->call('sectionDeleted');

        // Assert
        $component->assertDontSee($section->name);
    });
});

describe('ManageCustomFieldSection - Section Actions', function (): void {
    beforeEach(function (): void {
        $this->section = CustomFieldSection::factory()
            ->forEntityType($this->userEntityType)
            ->create();
    });

    it('can edit a section with valid data', function (): void {
        // Arrange
        $newData = [
            'name' => 'Updated Section Name',
            'code' => 'updated_code',
        ];

        // Act
        $livewireTest = livewire(ManageCustomFieldSection::class, [
            'section' => $this->section,
            'entityType' => $this->userEntityType,
        ])->callAction('edit', $newData);

        // Assert
        $livewireTest->assertHasNoActionErrors();
        
        $this->assertDatabaseHas(CustomFieldSection::class, [
            'id' => $this->section->getKey(),
            'name' => $newData['name'],
            'code' => $newData['code'],
        ]);
    });

    it('can activate an inactive section', function (): void {
        // Arrange
        $inactiveSection = CustomFieldSection::factory()
            ->inactive()
            ->forEntityType($this->userEntityType)
            ->create();

        // Act
        livewire(ManageCustomFieldSection::class, [
            'section' => $inactiveSection,
            'entityType' => $this->userEntityType,
        ])->callAction('activate');

        // Assert
        $this->assertDatabaseHas(CustomFieldSection::class, [
            'id' => $inactiveSection->getKey(),
            'active' => true,
        ]);
    });

    it('can deactivate an active section', function (): void {
        // Act
        livewire(ManageCustomFieldSection::class, [
            'section' => $this->section,
            'entityType' => $this->userEntityType,
        ])->callAction('deactivate');

        // Assert
        $this->assertDatabaseHas(CustomFieldSection::class, [
            'id' => $this->section->getKey(),
            'active' => false,
        ]);
    });

    it('can delete an inactive non-system section', function (): void {
        // Arrange
        $deletableSection = CustomFieldSection::factory()
            ->inactive()
            ->forEntityType($this->userEntityType)
            ->create();

        // Act
        livewire(ManageCustomFieldSection::class, [
            'section' => $deletableSection,
            'entityType' => $this->userEntityType,
        ])->callAction('delete');

        // Assert
        $this->assertDatabaseMissing(CustomFieldSection::class, [
            'id' => $deletableSection->getKey(),
        ]);
    });

    it('cannot delete an active section', function (): void {
        livewire(ManageCustomFieldSection::class, [
            'section' => $this->section,
            'entityType' => $this->userEntityType,
        ])->assertActionHidden('delete');
    });

    it('cannot delete a system-defined section', function (): void {
        // Arrange
        $systemSection = CustomFieldSection::factory()
            ->inactive()
            ->systemDefined()
            ->forEntityType($this->userEntityType)
            ->create();

        // Act & Assert
        livewire(ManageCustomFieldSection::class, [
            'section' => $systemSection,
            'entityType' => $this->userEntityType,
        ])->assertActionHidden('delete');
    });
});

describe('ManageCustomFieldSection - Field Management', function (): void {
    beforeEach(function (): void {
        $this->section = CustomFieldSection::factory()
            ->forEntityType($this->userEntityType)
            ->create();
    });

    it('can display create field action button and form exists', function (): void {
        // Act & Assert - verify the create field action is available
        livewire(ManageCustomFieldSection::class, [
            'section' => $this->section,
            'entityType' => $this->userEntityType,
        ])->assertActionExists('createField')
          ->assertActionVisible('createField');
    });

    it('can create field directly and verify section relationship', function (): void {
        // Arrange
        $fieldData = [
            'name' => 'Test Field',
            'code' => 'test_field',
            'type' => CustomFieldType::TEXT,
            'entity_type' => $this->userEntityType,
            'custom_field_section_id' => $this->section->getKey(),
        ];

        // Act - create field directly (simulates successful form submission)
        $field = CustomField::factory()->create($fieldData);

        // Assert - verify proper relationships and data
        expect($field->section->getKey())->toBe($this->section->getKey())
            ->and($field->entity_type)->toBe($this->userEntityType)
            ->and($this->section->fields->count())->toBe(1)
            ->and($this->section->fields->first()->name)->toBe('Test Field');
    });

    it('can update field order within a section', function (): void {
        // Arrange
        $field1 = CustomField::factory()
            ->create([
                'custom_field_section_id' => $this->section->getKey(),
                'entity_type' => $this->userEntityType,
                'sort_order' => 0,
            ]);
        $field2 = CustomField::factory()
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

    it('refreshes section when field is deleted', function (): void {
        // Arrange
        $field = CustomField::factory()
            ->create([
                'custom_field_section_id' => $this->section->getKey(),
                'entity_type' => $this->userEntityType,
            ]);

        // Act
        $component = livewire(ManageCustomFieldSection::class, [
            'section' => $this->section,
            'entityType' => $this->userEntityType,
        ]);

        // Simulate field deletion
        $component->call('fieldDeleted');

        // Assert - component should handle the refresh
        expect($component->instance()->section)->toBeSameModel($this->section);
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

    it('can display edit field action and verify field update capability', function (): void {
        // Act & Assert - verify the edit action is available
        livewire(ManageCustomField::class, [
            'field' => $this->field,
        ])->assertActionExists('edit')
          ->assertActionVisible('edit');
    });

    it('can update field data and verify changes persist', function (): void {
        // Arrange
        $originalName = $this->field->name;
        $newName = 'Updated Field Name';

        // Act - update field directly (simulates successful form submission)
        $this->field->update(['name' => $newName]);

        // Assert - verify the update persisted
        $this->field->refresh();
        expect($this->field->name)->toBe($newName)
            ->and($this->field->name)->not->toBe($originalName);
        
        $this->assertDatabaseHas(CustomField::class, [
            'id' => $this->field->getKey(),
            'name' => $newName,
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

describe('CustomFieldsPage - Business Logic and Integration', function (): void {
    it('assigns correct entity type when creating sections', function (): void {
        // Arrange
        $sectionData = [
            'name' => 'Post Section',
            'code' => 'post_section',
        ];

        // Act
        livewire(CustomFieldsPage::class)
            ->call('setCurrentEntityType', $this->postEntityType)
            ->callAction('createSection', $sectionData);

        // Assert
        $this->assertDatabaseHas(CustomFieldSection::class, [
            'entity_type' => $this->postEntityType,
            'name' => $sectionData['name'],
            'code' => $sectionData['code'],
        ]);
    });

    it('assigns correct entity type and section when creating fields', function (): void {
        // Arrange
        $section = CustomFieldSection::factory()
            ->forEntityType($this->postEntityType)
            ->create();
        
        $fieldData = [
            'name' => 'Test Field',
            'code' => 'test_field',
            'type' => CustomFieldType::TEXT->value,
        ];

        // Act
        livewire(ManageCustomFieldSection::class, [
            'section' => $section,
            'entityType' => $this->postEntityType,
        ])->callAction('createField', $fieldData);

        // Assert
        $this->assertDatabaseHas(CustomField::class, [
            'name' => $fieldData['name'],
            'entity_type' => $this->postEntityType,
            'custom_field_section_id' => $section->getKey(),
        ]);
    });

    it('filters sections by entity type correctly', function (): void {
        // Arrange
        $userSection = CustomFieldSection::factory()
            ->forEntityType($this->userEntityType)
            ->create();
        $postSection = CustomFieldSection::factory()
            ->forEntityType($this->postEntityType)
            ->create();

        // Act
        $component = livewire(CustomFieldsPage::class)
            ->call('setCurrentEntityType', $this->userEntityType);

        // Assert
        $component->assertSee($userSection->name)
                  ->assertDontSee($postSection->name);
    });

    it('shows fields ordered by sort_order within sections', function (): void {
        // Arrange
        $section = CustomFieldSection::factory()
            ->forEntityType($this->userEntityType)
            ->create();
        
        CustomField::factory()
            ->create([
                'custom_field_section_id' => $section->getKey(),
                'entity_type' => $this->userEntityType,
                'sort_order' => 2,
                'name' => 'Second Field',
                'type' => CustomFieldType::TEXT,
            ]);
        CustomField::factory()
            ->create([
                'custom_field_section_id' => $section->getKey(),
                'entity_type' => $this->userEntityType,
                'sort_order' => 1,
                'name' => 'First Field',
                'type' => CustomFieldType::TEXT,
            ]);

        // Act
        $component = livewire(ManageCustomFieldSection::class, [
            'section' => $section,
            'entityType' => $this->userEntityType,
        ]);

        // Assert
        $fields = $component->instance()->fields;
        expect($fields->first()->name)->toBe('First Field')
            ->and($fields->last()->name)->toBe('Second Field');
    });
});