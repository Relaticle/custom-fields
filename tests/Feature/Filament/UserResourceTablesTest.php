<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\CustomFields\Enums\FieldType;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldOption;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Tests\Models\User;
use Relaticle\CustomFields\Tests\Resources\UserResource\Pages\ListUsers;

beforeEach(function () {
    // Create a custom field section for User model
    $this->section = CustomFieldSection::factory()->create([
        'name' => 'User Details',
        'entity_type' => User::class,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    // Create various field types for testing table columns
    $this->textField = CustomField::factory()->create([
        'section_id' => $this->section->id,
        'name' => 'Position',
        'label' => 'Job Position',
        'type' => FieldType::TEXT,
        'is_visible_in_tables' => true,
        'is_searchable' => true,
        'is_sortable' => true,
        'sort_order' => 1,
    ]);

    $this->numberField = CustomField::factory()->create([
        'section_id' => $this->section->id,
        'name' => 'Salary',
        'label' => 'Annual Salary',
        'type' => FieldType::NUMBER,
        'is_visible_in_tables' => true,
        'is_sortable' => true,
        'sort_order' => 2,
    ]);

    $this->selectField = CustomField::factory()->create([
        'section_id' => $this->section->id,
        'name' => 'Department',
        'label' => 'Department',
        'type' => FieldType::SELECT,
        'is_visible_in_tables' => true,
        'is_filterable' => true,
        'sort_order' => 3,
    ]);

    // Create options for select field
    CustomFieldOption::factory()->create([
        'custom_field_id' => $this->selectField->id,
        'label' => 'Engineering',
        'value' => 'engineering',
        'sort_order' => 1,
    ]);

    CustomFieldOption::factory()->create([
        'custom_field_id' => $this->selectField->id,
        'label' => 'Marketing',
        'value' => 'marketing',
        'sort_order' => 2,
    ]);

    $this->checkboxField = CustomField::factory()->create([
        'section_id' => $this->section->id,
        'name' => 'Active',
        'label' => 'Is Active',
        'type' => FieldType::CHECKBOX,
        'is_visible_in_tables' => true,
        'is_filterable' => true,
        'sort_order' => 4,
    ]);

    $this->hiddenField = CustomField::factory()->create([
        'section_id' => $this->section->id,
        'name' => 'Secret',
        'label' => 'Secret Field',
        'type' => FieldType::TEXT,
        'is_visible_in_tables' => false,
        'sort_order' => 5,
    ]);

    // Create test users with custom field values
    $this->user1 = User::factory()->create([
        'name' => 'John Developer',
        'email' => 'john@example.com',
    ]);

    $this->user1->saveCustomFieldValue($this->textField, 'Senior Developer');
    $this->user1->saveCustomFieldValue($this->numberField, 75000);
    $this->user1->saveCustomFieldValue($this->selectField, 'engineering');
    $this->user1->saveCustomFieldValue($this->checkboxField, true);

    $this->user2 = User::factory()->create([
        'name' => 'Jane Marketer',
        'email' => 'jane@example.com',
    ]);

    $this->user2->saveCustomFieldValue($this->textField, 'Marketing Manager');
    $this->user2->saveCustomFieldValue($this->numberField, 65000);
    $this->user2->saveCustomFieldValue($this->selectField, 'marketing');
    $this->user2->saveCustomFieldValue($this->checkboxField, false);

    $this->user3 = User::factory()->create([
        'name' => 'Bob Engineer',
        'email' => 'bob@example.com',
    ]);

    $this->user3->saveCustomFieldValue($this->textField, 'Junior Developer');
    $this->user3->saveCustomFieldValue($this->numberField, 55000);
    $this->user3->saveCustomFieldValue($this->selectField, 'engineering');
    $this->user3->saveCustomFieldValue($this->checkboxField, true);
});

test('custom field columns are added to table', function () {
    $component = Livewire::test(ListUsers::class);

    $component->assertCanSeeTableRecords([
        $this->user1, 
        $this->user2, 
        $this->user3
    ]);

    // Check that custom field columns are present and display values correctly
    $component->assertTableColumnExists('name');
    $component->assertTableColumnExists('email');
    
    // Custom field columns should be visible
    $tableColumns = $component->instance()->getTable()->getColumns();
    $columnNames = collect($tableColumns)->map(fn($column) => $column->getName())->toArray();
    
    // Should include our custom field columns
    expect($columnNames)->toContain('Position'); // Text field
    expect($columnNames)->toContain('Annual Salary'); // Number field  
    expect($columnNames)->toContain('Department'); // Select field
    expect($columnNames)->toContain('Is Active'); // Checkbox field
    
    // Hidden field should not be visible
    expect($columnNames)->not->toContain('Secret Field');
});

test('custom field values are displayed in table', function () {
    $component = Livewire::test(ListUsers::class);

    // Check that custom field values are displayed correctly
    $component->assertTableCellExists($this->user1, 'name', 'John Developer');
    $component->assertTableCellExists($this->user2, 'name', 'Jane Marketer');
    $component->assertTableCellExists($this->user3, 'name', 'Bob Engineer');
    
    // Test custom field value display
    // Note: The exact cell testing depends on how the custom field columns render values
    $tableRecords = $component->instance()->getTable()->getRecords();
    
    expect($tableRecords)->toHaveCount(3);
    
    // Verify that records have their custom field values loaded
    $user1Record = $tableRecords->firstWhere('id', $this->user1->id);
    expect($user1Record->getCustomFieldValue($this->textField))->toBe('Senior Developer');
    expect($user1Record->getCustomFieldValue($this->selectField))->toBe('engineering');
});

test('can search by custom field values', function () {
    $component = Livewire::test(ListUsers::class);

    // Search for a custom field value
    $component->searchTable('Senior Developer');
    
    // Should find user1 but not user2 or user3
    $component->assertCanSeeTableRecords([$this->user1]);
    $component->assertCanNotSeeTableRecords([$this->user2, $this->user3]);
    
    // Clear search
    $component->searchTable('');
    $component->assertCanSeeTableRecords([$this->user1, $this->user2, $this->user3]);
    
    // Search for another custom field value
    $component->searchTable('Marketing Manager');
    $component->assertCanSeeTableRecords([$this->user2]);
    $component->assertCanNotSeeTableRecords([$this->user1, $this->user3]);
});

test('can sort by custom field values', function () {
    $component = Livewire::test(ListUsers::class);

    // Sort by salary (number field) ascending
    $component->sortTable('Annual Salary');
    
    $tableRecords = $component->instance()->getTable()->getRecords();
    $salaries = $tableRecords->map(fn($record) => $record->getCustomFieldValue($this->numberField))->toArray();
    
    // Should be sorted: 55000, 65000, 75000
    expect($salaries)->toBe([55000, 65000, 75000]);
    
    // Sort descending
    $component->sortTable('Annual Salary', 'desc');
    
    $tableRecords = $component->instance()->getTable()->getRecords();
    $salaries = $tableRecords->map(fn($record) => $record->getCustomFieldValue($this->numberField))->toArray();
    
    // Should be sorted: 75000, 65000, 55000
    expect($salaries)->toBe([75000, 65000, 55000]);
});

test('can filter by select custom field', function () {
    $component = Livewire::test(ListUsers::class);

    // Filter by department
    $component->filterTable('Department', 'engineering');
    
    // Should show only engineering users
    $component->assertCanSeeTableRecords([$this->user1, $this->user3]);
    $component->assertCanNotSeeTableRecords([$this->user2]);
    
    // Filter by marketing
    $component->filterTable('Department', 'marketing');
    
    $component->assertCanSeeTableRecords([$this->user2]);
    $component->assertCanNotSeeTableRecords([$this->user1, $this->user3]);
    
    // Clear filter
    $component->removeTableFilter('Department');
    $component->assertCanSeeTableRecords([$this->user1, $this->user2, $this->user3]);
});

test('can filter by checkbox custom field', function () {
    $component = Livewire::test(ListUsers::class);

    // Filter by active status
    $component->filterTable('Is Active', true);
    
    // Should show only active users (user1 and user3)
    $component->assertCanSeeTableRecords([$this->user1, $this->user3]);
    $component->assertCanNotSeeTableRecords([$this->user2]);
    
    // Filter by inactive
    $component->filterTable('Is Active', false);
    
    $component->assertCanSeeTableRecords([$this->user2]);
    $component->assertCanNotSeeTableRecords([$this->user1, $this->user3]);
});

test('custom field columns are toggleable', function () {
    $component = Livewire::test(ListUsers::class);

    // Get table instance
    $table = $component->instance()->getTable();
    
    // Find custom field columns
    $positionColumn = collect($table->getColumns())->first(fn($col) => $col->getName() === 'Position');
    $salaryColumn = collect($table->getColumns())->first(fn($col) => $col->getName() === 'Annual Salary');
    
    expect($positionColumn)->not->toBeNull();
    expect($salaryColumn)->not->toBeNull();
    
    // Columns should be toggleable by default
    expect($positionColumn->isToggleable())->toBeTrue();
    expect($salaryColumn->isToggleable())->toBeTrue();
});

test('hidden custom fields do not appear in table', function () {
    $component = Livewire::test(ListUsers::class);

    $tableColumns = $component->instance()->getTable()->getColumns();
    $columnNames = collect($tableColumns)->map(fn($column) => $column->getName())->toArray();
    
    // Hidden field should not appear
    expect($columnNames)->not->toContain('Secret Field');
});

test('table loads custom field values efficiently', function () {
    $component = Livewire::test(ListUsers::class);

    // Check that custom field values are eager loaded
    $tableRecords = $component->instance()->getTable()->getRecords();
    
    // Verify relationships are loaded to avoid N+1 queries
    foreach ($tableRecords as $record) {
        expect($record->relationLoaded('customFieldValues'))->toBeTrue();
        expect($record->customFieldValues->first()?->relationLoaded('customField'))->toBeTrue();
    }
});

test('non-filterable custom fields do not have filters', function () {
    $component = Livewire::test(ListUsers::class);

    $tableFilters = $component->instance()->getTable()->getFilters();
    $filterNames = collect($tableFilters)->map(fn($filter) => $filter->getName())->toArray();
    
    // Only filterable fields should have filters
    expect($filterNames)->toContain('Department'); // select field - filterable
    expect($filterNames)->toContain('Is Active'); // checkbox field - filterable
    expect($filterNames)->not->toContain('Job Position'); // text field - not filterable
    expect($filterNames)->not->toContain('Annual Salary'); // number field - not filterable
});

test('custom field filters work with multiple selections', function () {
    // Create additional user with different department
    $user4 = User::factory()->create([
        'name' => 'Alice Designer',
        'email' => 'alice@example.com',
    ]);

    $user4->saveCustomFieldValue($this->selectField, 'engineering');
    $user4->saveCustomFieldValue($this->checkboxField, true);

    $component = Livewire::test(ListUsers::class);

    // Filter by engineering - should show 3 users now
    $component->filterTable('Department', 'engineering');
    
    $component->assertCanSeeTableRecords([$this->user1, $this->user3, $user4]);
    $component->assertCanNotSeeTableRecords([$this->user2]);
});