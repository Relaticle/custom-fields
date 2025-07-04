<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Models\CustomField;

interface FieldComponentInterface
{
    /**
     * @param  array<string>  $dependentFieldCodes
     * @param  Collection<int, CustomField>|null  $allFields
     */
    public function make(CustomField $customField, array $dependentFieldCodes = [], ?Collection $allFields = null): Field;
}
