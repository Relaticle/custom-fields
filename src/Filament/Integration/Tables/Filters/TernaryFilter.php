<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Tables\Filters;

use Filament\Tables\Filters\TernaryFilter as FilamentTernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\CustomFields\Models\CustomField;

final readonly class TernaryFilter implements FilterInterface
{
    public function make(CustomField $customField): FilamentTernaryFilter
    {
        return FilamentTernaryFilter::make("custom_fields.$customField->code")
            ->label($customField->name)
            ->options([
                true => 'Yes',
                false => 'No',
            ])
            ->nullable()
            ->queries(
                true: fn (Builder $query) => $query
                    ->whereHas('customFieldValues', function (Builder $query) use ($customField): void {
                        $query->where('custom_field_id', $customField->getKey())->where($customField->getValueColumn(), true);
                    }),
                false: fn (Builder $query) => $query
                    ->where(fn (Builder $query) => $query
                        ->whereHas('customFieldValues', function (Builder $query) use ($customField): void {
                            $query->where('custom_field_id', $customField->getKey())->where($customField->getValueColumn(), false);
                        })->orWhereDoesntHave('customFieldValues', function (Builder $query) use ($customField): void {
                            $query->where('custom_field_id', $customField->getKey())->where($customField->getValueColumn(), true);
                        })
                    )
            );
    }
}
