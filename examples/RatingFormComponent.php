<?php

declare(strict_types=1);

namespace Examples;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Integration\Forms\Components\FieldComponentInterface;
use Relaticle\CustomFields\Integration\Forms\FieldConfigurator;
use Relaticle\CustomFields\Models\CustomField;

/**
 * Form component for rating field type.
 *
 * This creates a select dropdown with star ratings (1-5 stars).
 */
class RatingFormComponent implements FieldComponentInterface
{
    public function __construct(
        private readonly FieldConfigurator $configurator
    ) {}

    /**
     * @param  array<string>  $dependentFieldCodes
     * @param  Collection<int, CustomField>|null  $allFields
     */
    public function make(CustomField $customField, array $dependentFieldCodes = [], ?Collection $allFields = null): Field
    {
        $field = Select::make($customField->code)
            ->label($customField->name)
            ->options([
                1 => '⭐ (1 star)',
                2 => '⭐⭐ (2 stars)',
                3 => '⭐⭐⭐ (3 stars)',
                4 => '⭐⭐⭐⭐ (4 stars)',
                5 => '⭐⭐⭐⭐⭐ (5 stars)',
            ])
            ->placeholder('Select a rating')
            ->selectablePlaceholder(false);

        // Apply common field configuration (validation, visibility, etc.)
        return $this->configurator->configure($field, $customField, $dependentFieldCodes, $allFields);
    }
}
