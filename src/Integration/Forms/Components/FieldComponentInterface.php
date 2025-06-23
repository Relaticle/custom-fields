<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Relaticle\CustomFields\Models\CustomField;

interface FieldComponentInterface
{
    /**
     * @param  array<string>  $dependentFieldCodes
     */
    public function make(CustomField $customField, array $dependentFieldCodes = []): Field;
}
