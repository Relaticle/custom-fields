<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Base;

use Filament\Forms\Components\Field;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Contracts\FormComponentInterface;
use Relaticle\CustomFields\Filament\Integration\Concerns\Forms\ConfiguresFieldName;
use Relaticle\CustomFields\Filament\Integration\Forms\FieldConfigurator;
use Relaticle\CustomFields\Models\CustomField;

/**
 * Abstract base class for form field components.
 *
 * Eliminates duplication across 18+ component classes by providing
 * common structure and delegating to FieldConfigurator for shared logic.
 *
 * Each concrete component only needs to implement createField() to specify
 * the Filament component type and its basic configuration.
 */
abstract readonly class AbstractFormComponent implements FormComponentInterface
{
    use ConfiguresFieldName;

    public function __construct(protected FieldConfigurator $configurator) {}

    /**
     * Create and configure a field component.
     *
     * @param  array<string>  $dependentFieldCodes
     * @param  Collection<int, CustomField>|null  $allFields
     */
    public function make(CustomField $customField, array $dependentFieldCodes = [], ?Collection $allFields = null): Field
    {
        $field = $this->create($customField);

        /** @var Field */
        return $this->configurator->configure($field, $customField, $allFields ?? collect(), $dependentFieldCodes);
    }

    /**
     * Create the specific Filament field component.
     *
     * Concrete implementations should create the appropriate Filament component
     * (TextInput, Select, etc.) with field-specific configuration.
     *
     * Made public to allow composition patterns (like MultiSelectComponent).
     */
    abstract public function create(CustomField $customField): Field;
}
