<?php

declare(strict_types=1);

use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\TextEntry;
use Mockery\MockInterface;
use Relaticle\CustomFields\Filament\Integration\Components\Base\CustomFieldEntry;
use Relaticle\CustomFields\Models\CustomField;

beforeEach(function () {
    $this->customFieldEntry = new class extends CustomFieldEntry
    {
        protected function createEntry(CustomField $customField): Entry
        {
            return TextEntry::make($this->getStateAttributeName($customField));
        }

        protected function applyEntrySpecificConfiguration(Entry $entry, CustomField $customField): void
        {
            // Test implementation - no specific configuration
        }
    };
});

it('creates an entry with basic configuration', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'test_entry';
    $customField->label = 'Test Entry';
    $customField->help_text = 'Entry tooltip';
    $customField->hint = 'Entry hint';
    $customField->field_config = [];
    $customField->type = (object) ['value' => 'text'];

    $entry = $this->customFieldEntry->make($customField);

    expect($entry)->toBeInstanceOf(TextEntry::class);
    expect($entry->getLabel())->toBe('Test Entry');
    expect($entry->getTooltip())->toBe('Entry tooltip');
    expect($entry->getHint())->toBe('Entry hint');
});

it('applies entry configuration from field_config', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'test_entry';
    $customField->label = 'Test Entry';
    $customField->help_text = null;
    $customField->hint = null;
    $customField->field_config = [
        'hidden' => false,
        'columnSpan' => 2,
        'icon' => 'heroicon-o-document',
        'iconPosition' => 'after',
        'weight' => 'bold',
        'size' => 'lg',
        'color' => 'primary',
    ];
    $customField->type = (object) ['value' => 'text'];

    $entry = $this->customFieldEntry->make($customField);

    expect($entry->isHidden())->toBeFalse();
    expect($entry->getColumnSpan())->toBe(2);
    expect($entry->getIcon())->toBe('heroicon-o-document');
    expect($entry->getIconPosition()->value)->toBe('after');
    expect($entry->getWeight()->value)->toBe('bold');
    expect($entry->getSize()->value)->toBe('lg');
    expect($entry->getColor())->toBe('primary');
});

it('configures empty state display with placeholder', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'test_entry';
    $customField->label = 'Test Entry';
    $customField->help_text = null;
    $customField->hint = null;
    $customField->field_config = [
        'placeholder' => 'No data available',
    ];
    $customField->type = (object) ['value' => 'text'];

    $entry = $this->customFieldEntry->make($customField);

    expect($entry->getPlaceholder())->toBe('No data available');
});

it('configures empty state display with default', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'test_entry';
    $customField->label = 'Test Entry';
    $customField->help_text = null;
    $customField->hint = null;
    $customField->field_config = [
        'default' => 'Default value',
    ];
    $customField->type = (object) ['value' => 'text'];

    $entry = $this->customFieldEntry->make($customField);

    expect($entry->getDefault())->toBe('Default value');
});

it('configures hidden state', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'hidden_entry';
    $customField->label = 'Hidden Entry';
    $customField->help_text = null;
    $customField->hint = null;
    $customField->field_config = [
        'hidden' => true,
    ];
    $customField->type = (object) ['value' => 'text'];

    $entry = $this->customFieldEntry->make($customField);

    expect($entry->isHidden())->toBeTrue();
});

it('creates entry with visibility configuration', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'dependent_entry';
    $customField->label = 'Dependent Entry';
    $customField->help_text = null;
    $customField->hint = null;
    $customField->field_config = [];
    $customField->type = (object) ['value' => 'text'];
    $customField->visibility_rules = [
        'logic' => 'and',
        'conditions' => [
            ['field' => 'master_field', 'operator' => 'equals', 'value' => 'show'],
        ],
    ];

    $entry = $this->customFieldEntry->makeWithVisibility(
        $customField,
        ['master_field']
    );

    expect($entry)->toBeInstanceOf(TextEntry::class);
    // The visibility configuration would be applied through the createVisibilityClosure method
});

it('handles entry with no configuration gracefully', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'simple_entry';
    $customField->label = 'Simple Entry';
    $customField->help_text = null;
    $customField->hint = null;
    $customField->field_config = null;
    $customField->type = (object) ['value' => 'text'];

    $entry = $this->customFieldEntry->make($customField);

    expect($entry)->toBeInstanceOf(TextEntry::class);
    expect($entry->getLabel())->toBe('Simple Entry');
});

it('resolves state using the trait method', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->id = 1;
    $customField->code = 'test_entry';
    $customField->label = 'Test Entry';
    $customField->help_text = null;
    $customField->hint = null;
    $customField->field_config = [];
    $customField->type = (object) ['value' => 'text'];

    $entry = $this->customFieldEntry->make($customField);

    // The entry should be configured to use getStateUsing with the resolveState method
    expect($entry)->toBeInstanceOf(TextEntry::class);
});

it('handles null help text and hint', function () {
    $customField = Mockery::mock(CustomField::class);
    $customField->code = 'test_entry';
    $customField->label = 'Test Entry';
    $customField->help_text = null;
    $customField->hint = null;
    $customField->field_config = [];
    $customField->type = (object) ['value' => 'text'];

    $entry = $this->customFieldEntry->make($customField);

    expect($entry->getTooltip())->toBeNull();
    expect($entry->getHint())->toBeNull();
});