<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Relaticle\CustomFields\Models\CustomField;

final readonly class MultiSelectComponent extends AbstractFieldComponent
{
    public function createField(CustomField $customField): Field
    {
        // Delegate to SelectComponent and make it multiple
        $selectComponent = new SelectComponent($this->configurator);
        return $selectComponent->createField($customField)->multiple();
    }
}
