<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Tables\Columns;

use Filament\Tables\Filters\BaseFilter;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Support\Utils;

final readonly class CustomFieldsColumn
{
    /**
     * @return array<int, BaseFilter>
     */
    public static function all($instance): array
    {
        if (Utils::isTableColumnsEnabled() === false) {
            return [];
        }

        $fieldColumnFactory = new FieldColumnFactory(app());

        return $instance->customFields()
            ->visibleInList()
            ->with('options')
            ->get()
            ->map(
                fn (CustomField $customField) => $fieldColumnFactory->create($customField)
                    ->toggleable(
                        condition: Utils::isTableColumnsToggleableEnabled(),
                        isToggledHiddenByDefault: $customField->settings->list_toggleable_hidden
                    )
            )
            ->toArray();
    }
}
