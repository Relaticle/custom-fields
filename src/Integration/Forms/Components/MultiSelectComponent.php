<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms\Components;

use Filament\Forms\Components\Field;
use Relaticle\CustomFields\Models\CustomField;

final readonly class MultiSelectComponent extends AbstractFormComponent
{
    public function create(CustomField $customField): Field
    {
        $selectComponent = new SelectComponent($this->configurator);
        return $selectComponent->create($customField)->multiple();
    }
}
