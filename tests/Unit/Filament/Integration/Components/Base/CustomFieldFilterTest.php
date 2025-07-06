<?php

declare(strict_types=1);

use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Mockery\MockInterface;
use Relaticle\CustomFields\Filament\Integration\Components\Base\CustomFieldFilter;
use Relaticle\CustomFields\Models\CustomField;

beforeEach(function () {
    $this->customFieldFilter = new class extends CustomFieldFilter
    {
        protected function createFilterFormComponent(CustomField $customField): TextInput
        {
            return TextInput::make('value')
                ->label($customField->label);
        }

        protected function applyFilter(Builder $query, CustomField $customField): Builder
        {
            return $this->buildCustomFieldQuery($query, $customField, 'test_value', '=');
        }
    };
});

it('creates a filter with basic configuration', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'test_filter';
    $customField->label = 'Test Filter';
    $customField->field_config = [];
    $customField->type = (object) ['value' => 'text'];

    $filter = $this->customFieldFilter->make($customField);

    expect($filter)->toBeInstanceOf(BaseFilter::class);
    expect($filter->getLabel())->toBe('Test Filter');
    expect($filter->getName())->toBe('custom_fields_test_filter');
});

it('applies filter configuration from field_config', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'test_filter';
    $customField->label = 'Test Filter';
    $customField->field_config = [
        'defaultFilterValue' => 'default',
        'filterColumnSpan' => 2,
    ];
    $customField->type = (object) ['value' => 'text'];

    $filter = $this->customFieldFilter->make($customField);

    expect($filter->getDefaultState())->toBe('default');
    expect($filter->getColumnSpan())->toBe(2);
});

it('builds custom field query with equals operator', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->id = 1;

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('whereHas')
        ->once()
        ->with('customFieldValues', Mockery::type('Closure'))
        ->andReturnSelf();

    $result = $this->customFieldFilter->buildCustomFieldQuery($builder, $customField, 'test', '=');

    expect($result)->toBe($builder);
});

it('builds custom field query with like operator', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->id = 1;

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('whereHas')
        ->once()
        ->with('customFieldValues', Mockery::type('Closure'))
        ->andReturnSelf();

    $result = $this->customFieldFilter->buildCustomFieldQuery($builder, $customField, 'test', 'like');

    expect($result)->toBe($builder);
});

it('builds custom field query with in operator', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->id = 1;

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('whereHas')
        ->once()
        ->with('customFieldValues', Mockery::type('Closure'))
        ->andReturnSelf();

    $result = $this->customFieldFilter->buildCustomFieldQuery($builder, $customField, ['a', 'b'], 'in');

    expect($result)->toBe($builder);
});

it('builds custom field query with between operator', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->id = 1;

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('whereHas')
        ->once()
        ->with('customFieldValues', Mockery::type('Closure'))
        ->andReturnSelf();

    $result = $this->customFieldFilter->buildCustomFieldQuery($builder, $customField, [10, 20], 'between');

    expect($result)->toBe($builder);
});

it('returns query unchanged when value is null', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->id = 1;

    $builder = Mockery::mock(Builder::class);
    $builder->shouldNotReceive('whereHas');

    $result = $this->customFieldFilter->buildCustomFieldQuery($builder, $customField, null, '=');

    expect($result)->toBe($builder);
});

it('returns query unchanged when value is empty string', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->id = 1;

    $builder = Mockery::mock(Builder::class);
    $builder->shouldNotReceive('whereHas');

    $result = $this->customFieldFilter->buildCustomFieldQuery($builder, $customField, '', '=');

    expect($result)->toBe($builder);
});

it('builds boolean query correctly', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->id = 1;

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('whereHas')
        ->once()
        ->with('customFieldValues', Mockery::type('Closure'))
        ->andReturnSelf();

    $result = $this->customFieldFilter->buildBooleanQuery($builder, $customField, true);

    expect($result)->toBe($builder);
});

it('builds date range query correctly', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->id = 1;

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('whereHas')
        ->once()
        ->with('customFieldValues', Mockery::type('Closure'))
        ->andReturnSelf();

    $dateRange = [
        'from' => '2024-01-01',
        'to' => '2024-12-31',
    ];

    $result = $this->customFieldFilter->buildDateRangeQuery($builder, $customField, $dateRange);

    expect($result)->toBe($builder);
});

it('builds numeric range query correctly', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->id = 1;

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('whereHas')
        ->once()
        ->with('customFieldValues', Mockery::type('Closure'))
        ->andReturnSelf();

    $range = [
        'min' => 10,
        'max' => 100,
    ];

    $result = $this->customFieldFilter->buildNumericRangeQuery($builder, $customField, $range);

    expect($result)->toBe($builder);
});

it('generates correct filter state key', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'company_name';

    $key = $this->customFieldFilter->getFilterStateKey($customField);

    expect($key)->toBe('custom_fields_company_name');
});