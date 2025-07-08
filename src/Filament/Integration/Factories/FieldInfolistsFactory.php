<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Factories;

use Filament\Infolists\Components\Entry;
use Relaticle\CustomFields\Models\CustomField;

final class FieldInfolistsFactory
{
    public function create(CustomField $customField): Entry
    {
        $component = app($customField->typeData->infolistEntry);

        return $component->make($customField)
            ->columnSpan($customField->width->getSpanValue())
            ->state(function ($record) use ($customField) {
                return $record->getCustomFieldValue($customField);
            })
            ->inlineLabel(false);
    }
}
