<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Contracts;

use Filament\Forms\Components\Field;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Models\CustomField;

interface FormComponentInterface
{
    /**
     * @param  array<string>  $dependentFieldCodes
     * @param  Collection<int, CustomField>|null  $allFields
     */
    public function make(CustomField $customField, array $dependentFieldCodes = [], ?Collection $allFields = null): Field;
}
