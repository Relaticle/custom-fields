<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Factories;

use Filament\Tables\Columns\Column;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Support\Utils;

final class FieldColumnFactory
{
    public function create(CustomField $customField): Column
    {
        $component = app($customField->typeData->tableColumn);

        return $component->make($customField)
            ->toggleable(
                condition: Utils::isTableColumnsToggleableEnabled(),
                isToggledHiddenByDefault: $customField->settings->list_toggleable_hidden
            )
            ->columnSpan($customField->width->getSpanValue());
    }
}
