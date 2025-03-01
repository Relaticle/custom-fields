<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Tables\Filter;

use Filament\Tables\Filters\BaseFilter;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Support\Utils;

final readonly class CustomFieldsFilter
{
    /**
     * @return array<int, BaseFilter>
     */
    public static function all($instance): array
    {
        if (Utils::isTableFiltersEnabled() === false) {
            return [];
        }

        $fieldFilterFactory = new FieldFilterFactory(app());

        return $instance->customFields()
            ->with('options')
            ->whereIn('type', CustomFieldType::filterable()->pluck('value'))
            ->nonEncrypted()
            ->get()
            ->map(fn (CustomField $customField) => $fieldFilterFactory->create($customField))
            ->toArray();
    }
}
