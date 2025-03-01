<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Tables\Columns;

use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn as BaseIconColumn;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\CustomFields\Models\CustomField;

class IconColumn implements ColumnInterface
{
    public function make(CustomField $customField): Column
    {
        return BaseIconColumn::make("custom_fields.$customField->code")
            ->boolean()
            ->sortable(
                condition: ! $customField->settings->encrypted,
                query: function (Builder $query, string $direction) use ($customField): Builder {
                    $table = $query->getModel()->getTable();
                    $key = $query->getModel()->getKeyName();

                    return $query->orderBy(
                        $customField->values()
                            ->select($customField->getValueColumn())
                            ->whereColumn('custom_field_values.entity_id', "$table.$key")
                            ->limit(1),
                        $direction
                    );
                }
            )
            ->searchable(false)
            ->label($customField->name)
            ->getStateUsing(fn ($record) => $record->getCustomFieldValue($customField) ?? false);
    }
}
