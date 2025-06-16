<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\CustomFields\Enums\FieldType;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldOption;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Tests\Models\User;
use Relaticle\CustomFields\Tests\Resources\UserResource\Pages\ViewUser;

beforeEach(function () {
    // Create a custom field section for User model
    $this->section = CustomFieldSection::factory()->create([
        'name' => 'User Profile',
        'entity_type' => User::class,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    // Create various field types for testing infolist entries
    $this->textField = CustomField::factory()->create([
        'section_id' => $this->section->id,
        'name' => 'Bio',
        'label' => 'Biography',
        'type' => FieldType::TEXT,
        'is_visible_in_view' => true,
        'sort_order' => 1,
    ]);

    $this->numberField = CustomField::factory()->create([
        'section_id' => $this->section->id,
        'name' => 'Experience',
        'label' => 'Years of Experience',
        'type' => FieldType::NUMBER,
        'is_visible_in_view' => true,
        'sort_order' => 2,
    ]);

    $this->selectField = CustomField::factory()->create([
        'section_id' => $this->section->id,
        'name' => 'Level',
        'label' => 'Experience Level',
        'type' => FieldType::SELECT,
        'is_visible_in_view' => true,
        'sort_order' => 3,
    ]);

    // Create options for select field
    CustomFieldOption::factory()->create([
        'custom_field_id' => $this->selectField->id,
        'label' => 'Junior',
        'value' => 'junior',
        'sort_order' => 1,
    ]);

    CustomFieldOption::factory()->create([
        'custom_field_id' => $this->selectField->id,
        'label' => 'Senior',
        'value' => 'senior',
        'sort_order' => 2,
    ]);

    CustomFieldOption::factory()->create([
        'custom_field_id' => $this->selectField->id,
        'label' => 'Lead',
        'value' => 'lead',
        'sort_order' => 3,
    ]);

    $this->checkboxField = CustomField::factory()->create([
        'section_id' => $this->section->id,
        'name' => 'Remote',
        'label' => 'Works Remotely',
        'type' => FieldType::CHECKBOX,
        'is_visible_in_view' => true,
        'sort_order' => 4,
    ]);

    $this->dateField = CustomField::factory()->create([
        'section_id' => $this->section->id,
        'name' => 'StartDate',
        'label' => 'Start Date',
        'type' => FieldType::DATE,
        'is_visible_in_view' => true,
        'sort_order' => 5,
    ]);

    $this->hiddenField = CustomField::factory()->create([
        'section_id' => $this->section->id,
        'name' => 'Internal',
        'label' => 'Internal Notes',
        'type' => FieldType::TEXTAREA,
        'is_visible_in_view' => false,
        'sort_order' => 6,
    ]);

    // Create a second section
    $this->section2 = CustomFieldSection::factory()->create([
        'name' => 'Contact Information',
        'entity_type' => User::class,
        'sort_order' => 2,
        'is_active' => true,
    ]);

    $this->phoneField = CustomField::factory()->create([
        'section_id' => $this->section2->id,
        'name' => 'Phone',
        'label' => 'Phone Number',
        'type' => FieldType::TEXT,
        'is_visible_in_view' => true,
        'sort_order' => 1,
    ]);

    // Create test user with custom field values
    $this->user = User::factory()->create([
        'name' => 'John Developer',
        'email' => 'john@example.com',
    ]);

    $this->user->saveCustomFieldValue($this->textField, 'Experienced full-stack developer with expertise in Laravel and React');
    $this->user->saveCustomFieldValue($this->numberField, 8);
    $this->user->saveCustomFieldValue($this->selectField, 'senior');
    $this->user->saveCustomFieldValue($this->checkboxField, true);
    $this->user->saveCustomFieldValue($this->dateField, '2020-03-15');
    $this->user->saveCustomFieldValue($this->phoneField, '+1-555-123-4567');
    $this->user->saveCustomFieldValue($this->hiddenField, 'This should not be visible');
});

test('custom fields infolist renders in view page', function () {
    $component = Livewire::test(ViewUser::class, ['record' => $this->user->getRouteKey()]);

    $component->assertInfolistExists();
    
    // Check that infolist contains both standard and custom field sections
    $infolist = $component->instance()->getInfolist();
    expect($infolist)->not->toBeNull();
    
    // Should have User Information section + Custom Fields section
    $schema = $infolist->getSchema();
    expect($schema)->toHaveCount(2);
});

test('custom field values are displayed correctly in infolist', function () {
    $component = Livewire::test(ViewUser::class, ['record' => $this->user->getRouteKey()]);

    // Standard user fields should be displayed
    $component->assertInfolistEntryExists('name');
    $component->assertInfolistEntryExists('email');
    
    // Custom field values should be displayed
    $component->assertSee('Biography');
    $component->assertSee('Experienced full-stack developer with expertise in Laravel and React');
    
    $component->assertSee('Years of Experience');
    $component->assertSee('8');
    
    $component->assertSee('Experience Level');
    $component->assertSee('Senior'); // Should show the label, not the value
    
    $component->assertSee('Works Remotely');
    $component->assertSee('Yes'); // Boolean true should display as "Yes"
    
    $component->assertSee('Start Date');
    $component->assertSee('2020-03-15'); // Date should be formatted
    
    $component->assertSee('Phone Number');
    $component->assertSee('+1-555-123-4567');
});

test('hidden custom fields are not displayed in infolist', function () {
    $component = Livewire::test(ViewUser::class, ['record' => $this->user->getRouteKey()]);

    // Hidden field should not be visible
    $component->assertDontSee('Internal Notes');
    $component->assertDontSee('This should not be visible');
});

test('custom field sections organize entries properly', function () {
    $component = Livewire::test(ViewUser::class, ['record' => $this->user->getRouteKey()]);

    // Should see both section names
    $component->assertSee('User Profile');
    $component->assertSee('Contact Information');
    
    // Fields should be grouped under their respective sections
    $infolist = $component->instance()->getInfolist();
    $schema = $infolist->getSchema();
    
    // Find the Custom Fields section
    $customFieldsSection = collect($schema)->first(fn($component) => 
        $component->getName() === 'Custom Fields'
    );
    
    expect($customFieldsSection)->not->toBeNull();
    
    // Custom Fields section should contain our custom sections
    $customFieldsSchema = $customFieldsSection->getSchema();
    expect($customFieldsSchema)->toHaveCount(2); // User Profile + Contact Information
});

test('empty custom field values are handled gracefully', function () {
    // Create user without custom field values
    $emptyUser = User::factory()->create([
        'name' => 'Empty User',
        'email' => 'empty@example.com',
    ]);

    $component = Livewire::test(ViewUser::class, ['record' => $emptyUser->getRouteKey()]);

    // Should still render the custom fields section
    $component->assertSee('Custom Fields');
    
    // Should show field labels but not crash on empty values
    $component->assertSee('Biography');
    $component->assertSee('Years of Experience');
    $component->assertSee('Experience Level');
    
    // Empty values should display appropriately (empty or placeholder text)
    $component->assertInfolistExists();
});

test('select field displays option labels not values', function () {
    // Test different select values
    $juniorUser = User::factory()->create([
        'name' => 'Junior Developer',
        'email' => 'junior@example.com',
    ]);
    
    $juniorUser->saveCustomFieldValue($this->selectField, 'junior');
    
    $component = Livewire::test(ViewUser::class, ['record' => $juniorUser->getRouteKey()]);
    
    $component->assertSee('Experience Level');
    $component->assertSee('Junior');
    $component->assertDontSee('junior'); // Should not see raw value
});

test('boolean field displays proper yes/no values', function () {
    // Test false boolean value
    $remoteUser = User::factory()->create([
        'name' => 'Office Worker',
        'email' => 'office@example.com',
    ]);
    
    $remoteUser->saveCustomFieldValue($this->checkboxField, false);
    
    $component = Livewire::test(ViewUser::class, ['record' => $remoteUser->getRouteKey()]);
    
    $component->assertSee('Works Remotely');
    $component->assertSee('No'); // Boolean false should display as "No"
});

test('date field displays formatted dates', function () {
    $component = Livewire::test(ViewUser::class, ['record' => $this->user->getRouteKey()]);
    
    $component->assertSee('Start Date');
    
    // Date should be formatted (exact format depends on configuration)
    // The test checks that some form of the date is displayed
    $component->assertSee('2020');
    $component->assertSee('03');
    $component->assertSee('15');
});

test('infolist handles multiple field types correctly', function () {
    $component = Livewire::test(ViewUser::class, ['record' => $this->user->getRouteKey()]);

    // Verify different field types are handled
    $infolist = $component->instance()->getInfolist();
    
    // Should have entries for all visible field types
    $component->assertSee('Biography'); // TEXT
    $component->assertSee('Years of Experience'); // NUMBER
    $component->assertSee('Experience Level'); // SELECT
    $component->assertSee('Works Remotely'); // CHECKBOX
    $component->assertSee('Start Date'); // DATE
    $component->assertSee('Phone Number'); // TEXT in different section
});

test('infolist sections respect sort order', function () {
    $component = Livewire::test(ViewUser::class, ['record' => $this->user->getRouteKey()]);

    $infolist = $component->instance()->getInfolist();
    $schema = $infolist->getSchema();
    
    // Find the Custom Fields section
    $customFieldsSection = collect($schema)->first(fn($component) => 
        $component->getName() === 'Custom Fields'
    );
    
    $customSections = $customFieldsSection->getSchema();
    
    // First section should be "User Profile" (sort_order: 1)
    expect($customSections[0]->getName())->toBe('User Profile');
    
    // Second section should be "Contact Information" (sort_order: 2)
    expect($customSections[1]->getName())->toBe('Contact Information');
});

test('custom fields load efficiently for infolist', function () {
    $component = Livewire::test(ViewUser::class, ['record' => $this->user->getRouteKey()]);

    // Get the record from the component
    $record = $component->instance()->getRecord();
    
    // Verify that custom field values are properly loaded
    expect($record->relationLoaded('customFieldValues'))->toBeTrue();
    
    // Check that custom field relationships are loaded
    if ($record->customFieldValues->isNotEmpty()) {
        expect($record->customFieldValues->first()->relationLoaded('customField'))->toBeTrue();
    }
});