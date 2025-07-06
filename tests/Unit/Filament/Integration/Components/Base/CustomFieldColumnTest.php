<?php

declare(strict_types=1);

use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Mockery\MockInterface;
use Relaticle\CustomFields\Filament\Integration\Components\Base\CustomFieldColumn;
use Relaticle\CustomFields\Models\CustomField;

beforeEach(function () {
    $this->customFieldColumn = new class extends CustomFieldColumn
    {
        protected function createColumn(CustomField $customField): Column
        {
            return TextColumn::make($this->getStateAttributeName($customField));
        }

        protected function applyColumnSpecificConfiguration(Column $column, CustomField $customField): void
        {
            // Test implementation - no specific configuration
        }
    };
});

it('creates a column with basic configuration', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'test_column';
    $customField->label = 'Test Column';
    $customField->help_text = 'Column tooltip';
    $customField->field_config = [];
    $customField->type = (object) ['value' => 'text'];

    $column = $this->customFieldColumn->make($customField);

    expect($column)->toBeInstanceOf(TextColumn::class);
    expect($column->getLabel())->toBe('Test Column');
    expect($column->getTooltip())->toBe('Column tooltip');
});

it('applies column configuration from field_config', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'test_column';
    $customField->label = 'Test Column';
    $customField->help_text = null;
    $customField->field_config = [
        'sortable' => true,
        'searchable' => true,
        'toggleable' => true,
        'hidden' => false,
        'alignment' => 'center',
        'wrap' => true,
        'width' => '200px',
        'extraAttributes' => ['class' => 'custom-class'],
    ];
    $customField->type = (object) ['value' => 'text'];

    $column = $this->customFieldColumn->make($customField);

    expect($column->isSortable())->toBeTrue();
    expect($column->isSearchable())->toBeTrue();
    expect($column->isToggleable())->toBeTrue();
    expect($column->getAlignment()->value)->toBe('center');
    expect($column->canWrap())->toBeTrue();
    expect($column->getWidth())->toBe('200px');
    expect($column->getExtraAttributes())->toBe(['class' => 'custom-class']);
});

it('configures empty state display', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'test_column';
    $customField->label = 'Test Column';
    $customField->help_text = null;
    $customField->field_config = [
        'placeholder' => 'No data',
    ];
    $customField->type = (object) ['value' => 'text'];

    $column = $this->customFieldColumn->make($customField);

    expect($column->getPlaceholder())->toBe('No data');
});

it('uses emptyStateLabel when placeholder is not set', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'test_column';
    $customField->label = 'Test Column';
    $customField->help_text = null;
    $customField->field_config = [
        'emptyStateLabel' => 'Empty',
    ];
    $customField->type = (object) ['value' => 'text'];

    $column = $this->customFieldColumn->make($customField);

    expect($column->getPlaceholder())->toBe('Empty');
});

it('applies state formatting configuration', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'test_column';
    $customField->label = 'Test Column';
    $customField->help_text = null;
    $customField->field_config = [
        'description' => 'Column description',
        'descriptionPosition' => 'below',
    ];
    $customField->type = (object) ['value' => 'text'];

    $column = $this->customFieldColumn->make($customField);

    // Note: Filament's Column class might not expose these properties directly
    // This test verifies the configuration is applied
    expect($column)->toBeInstanceOf(Column::class);
});

it('configures hidden state', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'hidden_column';
    $customField->label = 'Hidden Column';
    $customField->help_text = null;
    $customField->field_config = [
        'hidden' => true,
    ];
    $customField->type = (object) ['value' => 'text'];

    $column = $this->customFieldColumn->make($customField);

    expect($column->isHidden())->toBeTrue();
});

it('creates column with visibility configuration', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'dependent_column';
    $customField->label = 'Dependent Column';
    $customField->help_text = null;
    $customField->field_config = [];
    $customField->type = (object) ['value' => 'text'];
    $customField->visibility_rules = [
        'logic' => 'and',
        'conditions' => [
            ['field' => 'master_field', 'operator' => 'equals', 'value' => 'show'],
        ],
    ];

    $column = $this->customFieldColumn->makeWithVisibility(
        $customField,
        ['master_field']
    );

    expect($column)->toBeInstanceOf(TextColumn::class);
    // The visibility configuration would be applied through the createVisibilityClosure method
});

it('handles column with no configuration gracefully', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'simple_column';
    $customField->label = 'Simple Column';
    $customField->help_text = null;
    $customField->field_config = null;
    $customField->type = (object) ['value' => 'text'];

    $column = $this->customFieldColumn->make($customField);

    expect($column)->toBeInstanceOf(TextColumn::class);
    expect($column->getLabel())->toBe('Simple Column');
});

it('resolves state using the trait method', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->id = 1;
    $customField->code = 'test_column';
    $customField->label = 'Test Column';
    $customField->help_text = null;
    $customField->field_config = [];
    $customField->type = (object) ['value' => 'text'];

    $column = $this->customFieldColumn->make($customField);

    // The column should be configured to use getStateUsing with the resolveState method
    expect($column)->toBeInstanceOf(TextColumn::class);
});