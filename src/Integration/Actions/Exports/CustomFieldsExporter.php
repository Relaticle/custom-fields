<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Actions\Exports;

use Filament\Actions\Exports\ExportColumn;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Contracts\ValueResolvers;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\Visibility\BackendVisibilityService;

readonly class CustomFieldsExporter
{
    /**
     * @return array<string, mixed>
     */
    public static function getColumns(string $modelInstance): array
    {
        $model = app($modelInstance);
        $valueResolver = app(ValueResolvers::class);
        $visibilityService = app(BackendVisibilityService::class);

        $allFields = $model
            ->customFields()
            ->with('options')
            ->visibleInList()
            ->get();

        return $allFields
            ->map(
                fn (CustomField $customField): ExportColumn => self::create(
                    $customField,
                    $valueResolver,
                    $visibilityService,
                    $allFields
                )
            )
            ->toArray();
    }

    /**
     * @param  Collection<int, CustomField>  $allFields
     */
    public static function create(
        CustomField $customField,
        ValueResolvers $valueResolver,
        BackendVisibilityService $visibilityService,
        Collection $allFields
    ): ExportColumn {
        return ExportColumn::make($customField->name)
            ->label($customField->name)
            ->state(function ($record) use (
                $customField,
                $valueResolver,
                $visibilityService,
                $allFields
            ) {
                // Apply the same visibility logic as infolists - only export visible fields
                if (
                    ! $visibilityService->isFieldVisible(
                        $record,
                        $customField,
                        $allFields
                    )
                ) {
                    return null; // Don't export values for fields that should be hidden
                }

                return $valueResolver->resolve(
                    record: $record,
                    customField: $customField,
                    exportable: true
                );
            });
    }
}
