<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\CustomFields\Database\Factories\CustomFieldFactory;
use Relaticle\CustomFields\Database\Factories\CustomFieldOptionFactory;
use Relaticle\CustomFields\Database\Factories\CustomFieldSectionFactory;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldOption;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Tests\Models\User;
use Relaticle\CustomFields\Tests\Resources\UserResource\Pages\CreateUser;
use Relaticle\CustomFields\Tests\Resources\UserResource\Pages\EditUser;
use Relaticle\CustomFields\Tests\Resources\UserResource\Pages\ListUsers;
use Relaticle\CustomFields\Tests\Resources\UserResource\Pages\ViewUser;

beforeEach(function () {
    // Create test data using factories
    $sectionFactory = new CustomFieldSectionFactory();
    $fieldFactory = new CustomFieldFactory();
    $optionFactory = new CustomFieldOptionFactory();

    // Create a section for User model
    $this->section = $sectionFactory->create([
        'name' => 'User Profile',
        'entity_type' => User::class,
        'sort_order' => 1,
        'active' => true,
    ]);

    // Create basic field types
    $this->textField = $fieldFactory->create([
        'custom_field_section_id' => $this->section->id,
        'name' => 'JobTitle',
        'code' => 'job_title',
        'type' => CustomFieldType::TEXT,
        'entity_type' => User::class,
        'sort_order' => 1,
    ]);

    $this->numberField = $fieldFactory->create([
        'custom_field_section_id' => $this->section->id,
        'name' => 'Salary',
        'code' => 'salary',
        'type' => CustomFieldType::NUMBER,
        'entity_type' => User::class,
        'sort_order' => 2,
    ]);

    $this->selectField = $fieldFactory->create([
        'custom_field_section_id' => $this->section->id,
        'name' => 'Department',
        'code' => 'department',
        'type' => CustomFieldType::SELECT,
        'entity_type' => User::class,
        'sort_order' => 3,
    ]);

    // Create select options
    $optionFactory->create([
        'custom_field_id' => $this->selectField->id,
        'name' => 'engineering',
        'sort_order' => 1,
    ]);

    $optionFactory->create([
        'custom_field_id' => $this->selectField->id,
        'name' => 'marketing',
        'sort_order' => 2,
    ]);

    $this->checkboxField = $fieldFactory->create([
        'custom_field_section_id' => $this->section->id,
        'name' => 'RemoteWork',
        'code' => 'remote_work',
        'type' => CustomFieldType::CHECKBOX,
        'entity_type' => User::class,
        'sort_order' => 4,
    ]);
});

test('user resource integrates with custom fields in forms', function () {
    $component = Livewire::test(CreateUser::class);

    // Check that the form exists and contains our custom fields component
    $component->assertFormExists();
    
    // Test creating a user with custom field values
    $userData = [
        'name' => 'John Developer',
        'email' => 'john@example.com',
        'password' => 'password123',
        "custom_field_{$this->textField->id}" => 'Senior Software Engineer',
        "custom_field_{$this->numberField->id}" => 95000,
        "custom_field_{$this->selectField->id}" => 'engineering',
        "custom_field_{$this->checkboxField->id}" => true,
    ];

    $component->fillForm($userData);
    $component->call('create');
    $component->assertHasNoFormErrors();

    // Verify user was created
    $user = User::where('email', 'john@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('John Developer');

    // Verify custom field values were saved
    expect($user->getCustomFieldValue($this->textField))->toBe('Senior Software Engineer');
    expect($user->getCustomFieldValue($this->numberField))->toBe(95000);
    expect($user->getCustomFieldValue($this->selectField))->toBe('engineering');
    expect($user->getCustomFieldValue($this->checkboxField))->toBe(true);
});

test('user resource validates required custom fields', function () {
    $component = Livewire::test(CreateUser::class);

    // Try to create user without required custom field
    $userData = [
        'name' => 'Jane Developer',
        'email' => 'jane@example.com',
        'password' => 'password123',
        // Missing required textField (Job Title)
        "custom_field_{$this->numberField->id}" => 75000,
        "custom_field_{$this->selectField->id}" => 'marketing',
    ];

    $component->fillForm($userData);
    $component->call('create');

    // Should have validation error for required field
    $component->assertHasFormErrors(["custom_field_{$this->textField->id}"]);
});

test('user resource displays custom fields in table', function () {
    // Create user with custom field values
    $user = User::factory()->create([
        'name' => 'Table Test User',
        'email' => 'table@example.com',
    ]);

    $user->saveCustomFieldValue($this->textField, 'Lead Developer');
    $user->saveCustomFieldValue($this->numberField, 120000);
    $user->saveCustomFieldValue($this->selectField, 'engineering');
    $user->saveCustomFieldValue($this->checkboxField, true);

    $component = Livewire::test(ListUsers::class);

    // Check that user appears in table
    $component->assertCanSeeTableRecords([$user]);

    // Verify that table includes custom field columns
    $tableColumns = $component->instance()->getTable()->getColumns();
    $columnLabels = collect($tableColumns)->map(fn($column) => $column->getLabel())->toArray();

    // Should include our custom field columns
    expect($columnLabels)->toContain('Job Title');
    expect($columnLabels)->toContain('Annual Salary');
    expect($columnLabels)->toContain('Department');
    expect($columnLabels)->toContain('Remote Work Eligible');
});

test('user resource allows searching by custom field values', function () {
    // Create multiple users with different custom field values
    $user1 = User::factory()->create(['name' => 'Developer One', 'email' => 'dev1@example.com']);
    $user1->saveCustomFieldValue($this->textField, 'Frontend Developer');
    $user1->saveCustomFieldValue($this->selectField, 'engineering');

    $user2 = User::factory()->create(['name' => 'Developer Two', 'email' => 'dev2@example.com']);
    $user2->saveCustomFieldValue($this->textField, 'Backend Developer');
    $user2->saveCustomFieldValue($this->selectField, 'engineering');

    $user3 = User::factory()->create(['name' => 'Marketer', 'email' => 'marketing@example.com']);
    $user3->saveCustomFieldValue($this->textField, 'Marketing Specialist');
    $user3->saveCustomFieldValue($this->selectField, 'marketing');

    $component = Livewire::test(ListUsers::class);

    // Search for specific job title
    $component->searchTable('Frontend');
    $component->assertCanSeeTableRecords([$user1]);
    $component->assertCanNotSeeTableRecords([$user2, $user3]);

    // Search for different title
    $component->searchTable('Marketing');
    $component->assertCanSeeTableRecords([$user3]);
    $component->assertCanNotSeeTableRecords([$user1, $user2]);
});

test('user resource allows filtering by custom field values', function () {
    // Create users with different department values
    $engineerUser = User::factory()->create(['name' => 'Engineer', 'email' => 'eng@example.com']);
    $engineerUser->saveCustomFieldValue($this->selectField, 'engineering');
    $engineerUser->saveCustomFieldValue($this->checkboxField, true);

    $marketerUser = User::factory()->create(['name' => 'Marketer', 'email' => 'mark@example.com']);
    $marketerUser->saveCustomFieldValue($this->selectField, 'marketing');
    $marketerUser->saveCustomFieldValue($this->checkboxField, false);

    $component = Livewire::test(ListUsers::class);

    // Filter by department
    $component->filterTable('Department', 'engineering');
    $component->assertCanSeeTableRecords([$engineerUser]);
    $component->assertCanNotSeeTableRecords([$marketerUser]);

    // Clear filter and test boolean filter
    $component->removeTableFilter('Department');
    $component->filterTable('Remote Work Eligible', true);
    $component->assertCanSeeTableRecords([$engineerUser]);
    $component->assertCanNotSeeTableRecords([$marketerUser]);
});

test('user resource displays custom fields in infolist', function () {
    $user = User::factory()->create([
        'name' => 'View Test User',
        'email' => 'view@example.com',
    ]);

    $user->saveCustomFieldValue($this->textField, 'Principal Engineer');
    $user->saveCustomFieldValue($this->numberField, 150000);
    $user->saveCustomFieldValue($this->selectField, 'engineering');
    $user->saveCustomFieldValue($this->checkboxField, true);

    $component = Livewire::test(ViewUser::class, ['record' => $user->getRouteKey()]);

    // Check that infolist displays custom field values
    $component->assertInfolistExists();
    $component->assertSee('Principal Engineer');
    $component->assertSee('150000');
    $component->assertSee('Engineering'); // Should show option label
    $component->assertSee('Yes'); // Boolean true should show as "Yes"
});

test('user resource allows editing custom field values', function () {
    $user = User::factory()->create([
        'name' => 'Edit Test User',
        'email' => 'edit@example.com',
    ]);

    // Set initial values
    $user->saveCustomFieldValue($this->textField, 'Initial Title');
    $user->saveCustomFieldValue($this->numberField, 80000);
    $user->saveCustomFieldValue($this->selectField, 'engineering');
    $user->saveCustomFieldValue($this->checkboxField, false);

    $component = Livewire::test(EditUser::class, ['record' => $user->getRouteKey()]);

    // Verify form is pre-filled with existing values
    $component->assertFormSet([
        'name' => 'Edit Test User',
        "custom_field_{$this->textField->id}" => 'Initial Title',
        "custom_field_{$this->numberField->id}" => 80000,
        "custom_field_{$this->selectField->id}" => 'engineering',
        "custom_field_{$this->checkboxField->id}" => false,
    ]);

    // Update values
    $updatedData = [
        'name' => 'Updated User',
        "custom_field_{$this->textField->id}" => 'Updated Title',
        "custom_field_{$this->numberField->id}" => 90000,
        "custom_field_{$this->selectField->id}" => 'marketing',
        "custom_field_{$this->checkboxField->id}" => true,
    ];

    $component->fillForm($updatedData);
    $component->call('save');
    $component->assertHasNoFormErrors();

    // Verify updates were saved
    $user->refresh();
    expect($user->name)->toBe('Updated User');
    expect($user->getCustomFieldValue($this->textField))->toBe('Updated Title');
    expect($user->getCustomFieldValue($this->numberField))->toBe(90000);
    expect($user->getCustomFieldValue($this->selectField))->toBe('marketing');
    expect($user->getCustomFieldValue($this->checkboxField))->toBe(true);
});

test('custom field data persists correctly in database', function () {
    $user = User::factory()->create(['name' => 'Persistence Test', 'email' => 'persist@example.com']);

    // Save various types of custom field values
    $user->saveCustomFieldValue($this->textField, 'Test Job Title');
    $user->saveCustomFieldValue($this->numberField, 55000);
    $user->saveCustomFieldValue($this->selectField, 'marketing');
    $user->saveCustomFieldValue($this->checkboxField, true);

    // Verify values are stored in database
    expect($user->customFieldValues()->count())->toBe(4);

    // Verify values can be retrieved
    expect($user->getCustomFieldValue($this->textField))->toBe('Test Job Title');
    expect($user->getCustomFieldValue($this->numberField))->toBe(55000);
    expect($user->getCustomFieldValue($this->selectField))->toBe('marketing');
    expect($user->getCustomFieldValue($this->checkboxField))->toBe(true);

    // Test with fresh model instance
    $freshUser = User::find($user->id);
    expect($freshUser->getCustomFieldValue($this->textField))->toBe('Test Job Title');
    expect($freshUser->getCustomFieldValue($this->numberField))->toBe(55000);
    expect($freshUser->getCustomFieldValue($this->selectField))->toBe('marketing');
    expect($freshUser->getCustomFieldValue($this->checkboxField))->toBe(true);
});

test('complete user lifecycle with custom fields works end-to-end', function () {
    // 1. Create user through form
    $createComponent = Livewire::test(CreateUser::class);
    $userData = [
        'name' => 'Lifecycle User',
        'email' => 'lifecycle@example.com',
        'password' => 'password123',
        "custom_field_{$this->textField->id}" => 'Software Engineer',
        "custom_field_{$this->numberField->id}" => 70000,
        "custom_field_{$this->selectField->id}" => 'engineering',
        "custom_field_{$this->checkboxField->id}" => false,
    ];

    $createComponent->fillForm($userData);
    $createComponent->call('create');
    $createComponent->assertHasNoFormErrors();

    $user = User::where('email', 'lifecycle@example.com')->first();
    expect($user)->not->toBeNull();

    // 2. View user in table
    $listComponent = Livewire::test(ListUsers::class);
    $listComponent->assertCanSeeTableRecords([$user]);

    // 3. View user details
    $viewComponent = Livewire::test(ViewUser::class, ['record' => $user->getRouteKey()]);
    $viewComponent->assertSee('Software Engineer');
    $viewComponent->assertSee('70000');
    $viewComponent->assertSee('Engineering');
    $viewComponent->assertSee('No');

    // 4. Edit user
    $editComponent = Livewire::test(EditUser::class, ['record' => $user->getRouteKey()]);
    $editComponent->fillForm([
        "custom_field_{$this->textField->id}" => 'Senior Software Engineer',
        "custom_field_{$this->numberField->id}" => 85000,
        "custom_field_{$this->checkboxField->id}" => true,
    ]);
    $editComponent->call('save');
    $editComponent->assertHasNoFormErrors();

    // 5. Verify changes in view
    $viewComponent->mount($user->getRouteKey());
    $viewComponent->assertSee('Senior Software Engineer');
    $viewComponent->assertSee('85000');
    $viewComponent->assertSee('Yes');

    // 6. Verify data persistence
    $user->refresh();
    expect($user->getCustomFieldValue($this->textField))->toBe('Senior Software Engineer');
    expect($user->getCustomFieldValue($this->numberField))->toBe(85000);
    expect($user->getCustomFieldValue($this->checkboxField))->toBe(true);
});