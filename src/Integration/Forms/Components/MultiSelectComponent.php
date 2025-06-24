<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\Select;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Integration\Forms\FieldConfigurator;
use Relaticle\CustomFields\Models\CustomField;

final readonly class MultiSelectComponent implements FieldComponentInterface
{
    public function __construct(private FieldConfigurator $configurator) {}

    /**
     * @param  array<string>  $dependentFieldCodes
     */
    public function make(CustomField $customField, array $dependentFieldCodes = [], ?Collection $allFields = null): Select
    {
        return (new SelectComponent($this->configurator))->make($customField, $dependentFieldCodes, $allFields)->multiple();
    }
}
