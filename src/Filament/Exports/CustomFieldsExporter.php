<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Exports;

use Filament\Actions\Exports\ExportColumn;
use Relaticle\CustomFields\Contracts\ValueResolvers;
use Relaticle\CustomFields\Models\CustomField;

readonly class CustomFieldsExporter
{
    /**
     * @param string $modelInstance
     * @return array
     */
    public static function getColumns(string $modelInstance): array
    {
        $model = app($modelInstance);
        $valueResolver = app(ValueResolvers::class);

        return $model->customFields()
            ->with('options')
            ->visibleInList()
            ->get()
            ->map(fn (CustomField $customField) => self::create($customField, $valueResolver))
            ->toArray();
    }

    /**
     * @param CustomField $customField
     * @param $valueResolver
     * @return ExportColumn
     */
    public static function create(CustomField $customField, $valueResolver): ExportColumn
    {
        return ExportColumn::make($customField->name)
            ->label($customField->name)
            ->state(function ($record) use ($customField, $valueResolver) {
                return $valueResolver->resolve($record, $customField);
            });
    }
}
