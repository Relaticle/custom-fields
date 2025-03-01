<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Infolists;

use Filament\Infolists\Components\Entry;
use Relaticle\CustomFields\Models\CustomField;

final readonly class FieldInfolistsConfigurator
{
    /**
     * @template T of Entry
     */
    public function configure(Entry $field, CustomField $customField): Entry
    {
        return $field
            ->label($customField->name)
            ->state(function ($record) use ($customField) {
                return $record->getCustomFieldValue($customField);
            });
    }
}
