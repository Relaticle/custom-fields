<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Relaticle\CustomFields\Data\CustomFieldSectionSettingsData;
use Relaticle\CustomFields\Data\CustomFieldSettingsData;
use Relaticle\CustomFields\Enums\CustomFieldSectionType;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Tests\Models\User;
use Relaticle\CustomFields\Tests\Resources\UserResource\Pages\CreateUser;
use Relaticle\CustomFields\Tests\Resources\UserResource\Pages\EditUser;
use function Pest\Livewire\livewire;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $user = User::factory()->create();

    actingAs($user);


    // Create a custom field section for testing
    $this->section = CustomFieldSection::create([
        'name' => 'Test Section',
        'code' => 'test_section',
        'entity_type' => User::class,
        'type' => CustomFieldSectionType::SECTION,
        'settings' => new CustomFieldSectionSettingsData,
        'sort_order' => 1,
        'active' => true,
        'system_defined' => false,
    ]);
});

test('custom fields appear in create user form', function () {
    // Create a text field
    $textField = CustomField::create([
        'custom_field_section_id' => $this->section->id,
        'name' => 'Company Name',
        'code' => 'company_name',
        'type' => CustomFieldType::TEXT,
        'entity_type' => User::class,
        'settings' => new CustomFieldSettingsData,
        'validation_rules' => [],
        'sort_order' => 1,
        'active' => true,
        'system_defined' => false,
    ]);

    // Mount the create user page
    $livewire = livewire(CreateUser::class);

    // Assert that the custom field appears in the form
    $livewire->assertFormFieldExists('company_name');
    
    // Assert form validation works for required custom field
    $livewire
        ->fillForm([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            // Missing required company_name field
        ])
        ->call('create')
        ->assertHasFormErrors(['company_name' => 'required']);
});

test('can save custom field values through create form', function () {
    // Create multiple field types
    $textField = CustomField::create([
        'custom_field_section_id' => $this->section->id,
        'name' => 'Company Name',
        'code' => 'company_name',
        'type' => CustomFieldType::TEXT,
        'entity_type' => User::class,
        'settings' => new CustomFieldSettingsData,
        'validation_rules' => [],
        'sort_order' => 1,
        'active' => true,
        'system_defined' => false,
    ]);

    $numberField = CustomField::create([
        'custom_field_section_id' => $this->section->id,
        'name' => 'Employee Count',
        'code' => 'employee_count',
        'type' => CustomFieldType::NUMBER,
        'entity_type' => User::class,
        'settings' => new CustomFieldSettingsData,
        'validation_rules' => [],
        'sort_order' => 2,
        'active' => true,
        'system_defined' => false,
    ]);

    // Create user with custom field values
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'company_name' => 'Acme Corp',
            'employee_count' => 50,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Verify user was created
    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();

    // Verify custom field values were saved
    expect($user->getCustomFieldValue($textField))->toBe('Acme Corp');
    expect($user->getCustomFieldValue($numberField))->toBe(50);
});

test('can edit custom field values through edit form', function () {
    // Create a user
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    // Create a custom field
    $textField = CustomField::create([
        'custom_field_section_id' => $this->section->id,
        'name' => 'Company Name',
        'code' => 'company_name',
        'type' => CustomFieldType::TEXT,
        'entity_type' => User::class,
        'settings' => new CustomFieldSettingsData,
        'validation_rules' => [],
        'sort_order' => 1,
        'active' => true,
        'system_defined' => false,
    ]);

    // Save initial value
    $user->saveCustomFieldValue($textField, 'Initial Company');

    // Edit the user with new custom field value
    livewire(EditUser::class, ['record' => $user->id])
        ->assertFormSet([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'company_name' => 'Initial Company',
        ])
        ->fillForm([
            'name' => 'Updated User',
            'company_name' => 'Updated Company',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    // Refresh user and verify values
    $user->refresh();
    expect($user->name)->toBe('Updated User');
    expect($user->getCustomFieldValue($textField))->toBe('Updated Company');
});

test('different custom field types work correctly in forms', function () {
    // Create fields of different types
    $fields = [];
    
    $fields['text'] = CustomField::create([
        'custom_field_section_id' => $this->section->id,
        'name' => 'Text Field',
        'code' => 'text_field',
        'type' => CustomFieldType::TEXT,
        'entity_type' => User::class,
        'settings' => new CustomFieldSettingsData,
        'validation_rules' => [],
        'sort_order' => 1,
        'active' => true,
        'system_defined' => false,
    ]);

    $fields['toggle'] = CustomField::create([
        'custom_field_section_id' => $this->section->id,
        'name' => 'Toggle Field',
        'code' => 'toggle_field',
        'type' => CustomFieldType::TOGGLE,
        'entity_type' => User::class,
        'settings' => new CustomFieldSettingsData,
        'validation_rules' => [],
        'sort_order' => 2,
        'active' => true,
        'system_defined' => false,
    ]);

    $fields['date'] = CustomField::create([
        'custom_field_section_id' => $this->section->id,
        'name' => 'Date Field',
        'code' => 'date_field',
        'type' => CustomFieldType::DATE,
        'entity_type' => User::class,
        'settings' => new CustomFieldSettingsData,
        'validation_rules' => [],
        'sort_order' => 3,
        'active' => true,
        'system_defined' => false,
    ]);

    // Test all field types appear in form
    $livewire = livewire(CreateUser::class);
    $livewire->assertFormFieldExists('text_field');
    $livewire->assertFormFieldExists('toggle_field');
    $livewire->assertFormFieldExists('date_field');

    // Create user with all field types
    $livewire
        ->fillForm([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'text_field' => 'Some text',
            'toggle_field' => true,
            'date_field' => '2024-01-15',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Verify all values were saved correctly
    $user = User::where('email', 'test@example.com')->first();
    expect($user->getCustomFieldValue($fields['text']))->toBe('Some text')
        ->and($user->getCustomFieldValue($fields['toggle']))->toBe(true)
        ->and($user->getCustomFieldValue($fields['date'])->format('Y-m-d'))->toBe('2024-01-15');
});

test('inactive custom fields do not appear in forms', function () {
    // Create an active field
    $activeField = CustomField::create([
        'custom_field_section_id' => $this->section->id,
        'name' => 'Active Field',
        'code' => 'active_field',
        'type' => CustomFieldType::TEXT,
        'entity_type' => User::class,
        'settings' => new CustomFieldSettingsData,
        'validation_rules' => [],
        'sort_order' => 1,
        'active' => true,
        'system_defined' => false,
    ]);

    // Create an inactive field
    $inactiveField = CustomField::create([
        'custom_field_section_id' => $this->section->id,
        'name' => 'Inactive Field',
        'code' => 'inactive_field',
        'type' => CustomFieldType::TEXT,
        'entity_type' => User::class,
        'settings' => new CustomFieldSettingsData,
        'validation_rules' => [],
        'sort_order' => 2,
        'active' => false,
        'system_defined' => false,
    ]);

    // Mount the create user page
    $livewire = livewire(CreateUser::class);

    // Assert active field appears but inactive field does not
    $livewire->assertFormFieldExists('active_field');
    $livewire->assertFormFieldDoesNotExist('inactive_field');
});