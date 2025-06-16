<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\CustomFields\Enums\FieldType;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldOption;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Models\CustomFieldValue;
use Relaticle\CustomFields\Tests\Models\User;
use Relaticle\CustomFields\Tests\Resources\UserResource\Pages\CreateUser;
use Relaticle\CustomFields\Tests\Resources\UserResource\Pages\EditUser;
use Relaticle\CustomFields\Tests\Resources\UserResource\Pages\ListUsers;
use Relaticle\CustomFields\Tests\Resources\UserResource\Pages\ViewUser;

beforeEach(function () {
    // Create comprehensive test scenario with multiple sections and field types
    $this->basicSection = CustomFieldSection::factory()->create([
        'name' => 'Basic Information',
        'entity_type' => User::class,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $this->advancedSection = CustomFieldSection::factory()->create([
        'name' => 'Advanced Details',
        'entity_type' => User::class,
        'sort_order' => 2,
        'is_active' => true,
    ]);

    // Basic field types
    $this->textField = CustomField::factory()->create([
        'section_id' => $this->basicSection->id,
        'name' => 'JobTitle',
        'label' => 'Job Title',
        'type' => FieldType::TEXT,
        'is_required' => true,
        'is_visible_in_forms' => true,
        'is_visible_in_tables' => true,
        'is_visible_in_view' => true,
        'is_searchable' => true,
        'sort_order' => 1,
    ]);

    $this->numberField = CustomField::factory()->create([
        'section_id' => $this->basicSection->id,
        'name' => 'Salary',
        'label' => 'Annual Salary',
        'type' => FieldType::NUMBER,
        'is_required' => false,
        'is_visible_in_forms' => true,
        'is_visible_in_tables' => true,
        'is_visible_in_view' => true,
        'is_sortable' => true,
        'sort_order' => 2,
    ]);

    $this->selectField = CustomField::factory()->create([
        'section_id' => $this->basicSection->id,
        'name' => 'Department',
        'label' => 'Department',
        'type' => FieldType::SELECT,
        'is_required' => true,
        'is_visible_in_forms' => true,
        'is_visible_in_tables' => true,
        'is_visible_in_view' => true,
        'is_filterable' => true,
        'sort_order' => 3,
    ]);

    // Create select options
    $this->engineeringOption = CustomFieldOption::factory()->create([
        'custom_field_id' => $this->selectField->id,
        'label' => 'Engineering',
        'value' => 'engineering',
        'sort_order' => 1,
    ]);

    $this->marketingOption = CustomFieldOption::factory()->create([
        'custom_field_id' => $this->selectField->id,
        'label' => 'Marketing',
        'value' => 'marketing',
        'sort_order' => 2,
    ]);

    // Advanced field types
    $this->checkboxField = CustomField::factory()->create([
        'section_id' => $this->advancedSection->id,
        'name' => 'RemoteWork',
        'label' => 'Remote Work Eligible',
        'type' => FieldType::CHECKBOX,
        'is_required' => false,
        'is_visible_in_forms' => true,
        'is_visible_in_tables' => true,
        'is_visible_in_view' => true,
        'is_filterable' => true,
        'sort_order' => 1,
    ]);

    $this->dateField = CustomField::factory()->create([
        'section_id' => $this->advancedSection->id,
        'name' => 'HireDate',
        'label' => 'Hire Date',
        'type' => FieldType::DATE,
        'is_required' => false,
        'is_visible_in_forms' => true,
        'is_visible_in_tables' => false,
        'is_visible_in_view' => true,
        'sort_order' => 2,
    ]);

    $this->textareaField = CustomField::factory()->create([
        'section_id' => $this->advancedSection->id,
        'name' => 'Notes',
        'label' => 'Additional Notes',
        'type' => FieldType::TEXTAREA,
        'is_required' => false,
        'is_visible_in_forms' => true,
        'is_visible_in_tables' => false,
        'is_visible_in_view' => true,
        'sort_order' => 3,
    ]);
});

test('complete user lifecycle with custom fields', function () {
    // Step 1: Create user with custom fields
    $createComponent = Livewire::test(CreateUser::class);
    
    $userData = [
        'name' => 'Alice Johnson',
        'email' => 'alice@example.com',
        'password' => 'password123',
        "custom_field_{$this->textField->id}" => 'Senior Software Engineer',
        "custom_field_{$this->numberField->id}" => 95000,
        "custom_field_{$this->selectField->id}" => 'engineering',
        "custom_field_{$this->checkboxField->id}" => true,
        "custom_field_{$this->dateField->id}" => '2022-01-15',
        "custom_field_{$this->textareaField->id}" => 'Excellent problem solver with strong leadership skills.',
    ];
    
    $createComponent->fillForm($userData);
    $createComponent->call('create');
    $createComponent->assertHasNoFormErrors();
    
    $user = User::where('email', 'alice@example.com')->first();
    expect($user)->not->toBeNull();
    
    // Step 2: Verify user appears in table with custom field values
    $listComponent = Livewire::test(ListUsers::class);
    $listComponent->assertCanSeeTableRecords([$user]);
    
    // Verify custom field values in table
    $tableRecords = $listComponent->instance()->getTable()->getRecords();
    $userRecord = $tableRecords->firstWhere('id', $user->id);
    
    expect($userRecord->getCustomFieldValue($this->textField))->toBe('Senior Software Engineer')
        ->and($userRecord->getCustomFieldValue($this->numberField))->toBe(95000)
        ->and($userRecord->getCustomFieldValue($this->selectField))->toBe('engineering')
        ->and($userRecord->getCustomFieldValue($this->checkboxField))->toBe(true);

    // Step 3: View user details in infolist
    $viewComponent = Livewire::test(ViewUser::class, ['record' => $user->getRouteKey()]);
    
    $viewComponent->assertSee('Senior Software Engineer');
    $viewComponent->assertSee('95000');
    $viewComponent->assertSee('Engineering'); // Option label
    $viewComponent->assertSee('Yes'); // Boolean true
    $viewComponent->assertSee('2022-01-15');
    $viewComponent->assertSee('Excellent problem solver with strong leadership skills.');
    
    // Step 4: Edit user and update custom fields
    $editComponent = Livewire::test(EditUser::class, ['record' => $user->getRouteKey()]);
    
    $editComponent->assertFormSet([
        'name' => 'Alice Johnson',
        "custom_field_{$this->textField->id}" => 'Senior Software Engineer',
        "custom_field_{$this->numberField->id}" => 95000,
        "custom_field_{$this->selectField->id}" => 'engineering',
        "custom_field_{$this->checkboxField->id}" => true,
    ]);
    
    // Update some values
    $updatedData = [
        "custom_field_{$this->textField->id}" => 'Principal Software Engineer',
        "custom_field_{$this->numberField->id}" => 120000,
        "custom_field_{$this->selectField->id}" => 'engineering',
        "custom_field_{$this->checkboxField->id}" => false,
        "custom_field_{$this->textareaField->id}" => 'Promoted to principal level. Leading architecture decisions.',
    ];
    
    $editComponent->fillForm($updatedData);
    $editComponent->call('save');
    $editComponent->assertHasNoFormErrors();
    
    // Step 5: Verify updates in view
    $viewComponent->mount($user->getRouteKey());
    $viewComponent->assertSee('Principal Software Engineer');
    $viewComponent->assertSee('120000');
    $viewComponent->assertSee('No'); // Boolean false
    $viewComponent->assertSee('Promoted to principal level. Leading architecture decisions.');
});

test('custom field validation across the application', function () {
    // Test required field validation in create form
    $createComponent = Livewire::test(CreateUser::class);
    
    $incompleteData = [
        'name' => 'Bob Smith',
        'email' => 'bob@example.com',
        'password' => 'password123',
        // Missing required text field and select field
        "custom_field_{$this->numberField->id}" => 75000,
        "custom_field_{$this->checkboxField->id}" => false,
    ];
    
    $createComponent->fillForm($incompleteData);
    $createComponent->call('create');
    
    // Should have validation errors for required fields
    $createComponent->assertHasFormErrors([
        "custom_field_{$this->textField->id}",
        "custom_field_{$this->selectField->id}",
    ]);
    
    // Test that user was not created
    expect(User::where('email', 'bob@example.com')->exists())->toBeFalse();
});

test('table filtering and searching with custom fields', function () {
    // Create multiple users with different custom field values
    $user1 = User::factory()->create(['name' => 'Engineer One', 'email' => 'eng1@example.com']);
    $user1->saveCustomFieldValue($this->textField, 'Frontend Developer');
    $user1->saveCustomFieldValue($this->selectField, 'engineering');
    $user1->saveCustomFieldValue($this->checkboxField, true);
    
    $user2 = User::factory()->create(['name' => 'Engineer Two', 'email' => 'eng2@example.com']);
    $user2->saveCustomFieldValue($this->textField, 'Backend Developer');
    $user2->saveCustomFieldValue($this->selectField, 'engineering');
    $user2->saveCustomFieldValue($this->checkboxField, false);
    
    $user3 = User::factory()->create(['name' => 'Marketer One', 'email' => 'mark1@example.com']);
    $user3->saveCustomFieldValue($this->textField, 'Marketing Specialist');
    $user3->saveCustomFieldValue($this->selectField, 'marketing');
    $user3->saveCustomFieldValue($this->checkboxField, true);
    
    $listComponent = Livewire::test(ListUsers::class);
    
    // Test department filtering
    $listComponent->filterTable('Department', 'engineering');
    $listComponent->assertCanSeeTableRecords([$user1, $user2]);
    $listComponent->assertCanNotSeeTableRecords([$user3]);
    
    // Test remote work filtering
    $listComponent->filterTable('Remote Work Eligible', true);
    $listComponent->assertCanSeeTableRecords([$user1]);
    $listComponent->assertCanNotSeeTableRecords([$user2, $user3]);
    
    // Clear filters and test searching
    $listComponent->removeTableFilter('Department');
    $listComponent->removeTableFilter('Remote Work Eligible');
    
    $listComponent->searchTable('Frontend');
    $listComponent->assertCanSeeTableRecords([$user1]);
    $listComponent->assertCanNotSeeTableRecords([$user2, $user3]);
    
    $listComponent->searchTable('Marketing');
    $listComponent->assertCanSeeTableRecords([$user3]);
    $listComponent->assertCanNotSeeTableRecords([$user1, $user2]);
});

test('custom field data persistence and retrieval', function () {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
    
    // Test saving various field types
    $user->saveCustomFieldValue($this->textField, 'Software Engineer');
    $user->saveCustomFieldValue($this->numberField, 80000);
    $user->saveCustomFieldValue($this->selectField, 'engineering');
    $user->saveCustomFieldValue($this->checkboxField, true);
    $user->saveCustomFieldValue($this->dateField, '2023-03-20');
    $user->saveCustomFieldValue($this->textareaField, 'Long description with multiple lines');
    
    // Test retrieval
    expect($user->getCustomFieldValue($this->textField))->toBe('Software Engineer');
    expect($user->getCustomFieldValue($this->numberField))->toBe(80000);
    expect($user->getCustomFieldValue($this->selectField))->toBe('engineering');
    expect($user->getCustomFieldValue($this->checkboxField))->toBe(true);
    expect($user->getCustomFieldValue($this->dateField))->toBe('2023-03-20');
    expect($user->getCustomFieldValue($this->textareaField))->toBe('Long description with multiple lines');
    
    // Test database persistence
    expect(CustomFieldValue::where('customizable_id', $user->id)->count())->toBe(6);
    
    // Test fresh model retrieval
    $freshUser = User::find($user->id);
    expect($freshUser->getCustomFieldValue($this->textField))->toBe('Software Engineer');
    expect($freshUser->getCustomFieldValue($this->numberField))->toBe(80000);
});

test('custom field options are handled correctly', function () {
    $user = User::factory()->create(['name' => 'Option Test', 'email' => 'option@example.com']);
    
    // Test saving select field with option value
    $user->saveCustomFieldValue($this->selectField, 'engineering');
    expect($user->getCustomFieldValue($this->selectField))->toBe('engineering');
    
    // Test different option
    $user->saveCustomFieldValue($this->selectField, 'marketing');
    expect($user->getCustomFieldValue($this->selectField))->toBe('marketing');
    
    // Test in infolist that label is shown
    $viewComponent = Livewire::test(ViewUser::class, ['record' => $user->getRouteKey()]);
    $viewComponent->assertSee('Marketing'); // Should see label
    $viewComponent->assertDontSee('marketing'); // Should not see raw value
});

test('inactive custom field sections are handled properly', function () {
    // Create inactive section
    $inactiveSection = CustomFieldSection::factory()->create([
        'name' => 'Inactive Section',
        'entity_type' => User::class,
        'sort_order' => 3,
        'is_active' => false,
    ]);
    
    $inactiveField = CustomField::factory()->create([
        'section_id' => $inactiveSection->id,
        'name' => 'InactiveField',
        'label' => 'Inactive Field',
        'type' => FieldType::TEXT,
        'is_visible_in_forms' => true,
        'sort_order' => 1,
    ]);
    
    // Inactive fields should not appear in forms
    $createComponent = Livewire::test(CreateUser::class);
    $createComponent->assertFormFieldDoesNotExist("custom_field_{$inactiveField->id}");
    
    // Inactive sections should not appear in infolists
    $user = User::factory()->create(['name' => 'Test', 'email' => 'test@example.com']);
    $viewComponent = Livewire::test(ViewUser::class, ['record' => $user->getRouteKey()]);
    $viewComponent->assertDontSee('Inactive Section');
});

test('custom fields work with model mass assignment', function () {
    $customFieldData = [
        $this->textField->id => 'Mass Assignment Test',
        $this->numberField->id => 50000,
        $this->selectField->id => 'engineering',
        $this->checkboxField->id => true,
    ];
    
    $user = User::factory()->create(['name' => 'Mass Test', 'email' => 'mass@example.com']);
    $user->saveCustomFields($customFieldData);
    
    // Verify all fields were saved
    expect($user->getCustomFieldValue($this->textField))->toBe('Mass Assignment Test');
    expect($user->getCustomFieldValue($this->numberField))->toBe(50000);
    expect($user->getCustomFieldValue($this->selectField))->toBe('engineering');
    expect($user->getCustomFieldValue($this->checkboxField))->toBe(true);
});