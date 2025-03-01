<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Tables\Filter;

use Filament\Tables\Filters\SelectFilter as FilamentSelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\FilamentResourceService;
use Throwable;

final readonly class SelectFilter implements FilterInterface
{
    /**
     * @throws Throwable
     */
    public function make(CustomField $customField): FilamentSelectFilter
    {
        $filter = FilamentSelectFilter::make("custom_fields.{$customField->code}")
            ->multiple()
            ->label($customField->name)
            ->searchable()
            ->options($customField->options);

        if ($customField->lookup_type) {
            $filter = $this->configureLookup($filter, $customField->lookup_type);
        } else {
            $filter->options($customField->options->pluck('name', 'id')->all());
        }

        $filter->query(
            fn (array $data, Builder $query): Builder => $query->when(
                ! empty($data['values']),
                fn (Builder $query): Builder => $query->whereHas('customFieldValues', function (Builder $query) use ($customField, $data) {
                    $query->where('custom_field_id', $customField->id)
                        ->when($customField->getValueColumn() === 'json_value', fn (Builder $query) => $query->whereJsonContains($customField->getValueColumn(), $data['values']))
                        ->when($customField->getValueColumn() !== 'json_value', fn (Builder $query) => $query->whereIn($customField->getValueColumn(), $data['values']));
                }),
            )
        );

        return $filter;
    }

    /**
     * @throws Throwable
     */
    protected function configureLookup(FilamentSelectFilter $select, $lookupType): FilamentSelectFilter
    {
        $entityInstance = FilamentResourceService::getModelInstance($lookupType);
        $recordTitleAttribute = FilamentResourceService::getRecordTitleAttribute($lookupType);
        $globalSearchableAttributes = FilamentResourceService::getGlobalSearchableAttributes($lookupType);

        // TODO: Check tenant support for below queries and other lookups
        return $select
            ->getSearchResultsUsing(fn (string $search): array => $entityInstance->query()
                ->whereAny($globalSearchableAttributes, 'like', "%{$search}%")
                ->limit(50)
                ->pluck($recordTitleAttribute, 'id')
                ->toArray())
            ->getOptionLabelUsing(fn ($value) => $entityInstance::query()->find($value)?->{$recordTitleAttribute})
            ->getOptionLabelsUsing(fn (array $values): array => $entityInstance::query()
                ->whereIn('id', $values)
                ->pluck($recordTitleAttribute, 'id')
                ->toArray());
    }
}
