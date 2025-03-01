<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Tables\Columns;

use Filament\Tables\Columns\Column as BaseColumn;
use Filament\Tables\Columns\TextColumn as BaseTextColumn;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Queries\ColumnSearchableQuery;

final readonly class TextColumn implements ColumnInterface
{
    public function make(CustomField $customField): BaseColumn
    {
        return BaseTextColumn::make("custom_fields.$customField->code")
            ->label($customField->name)
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
            ->searchable(
                condition: $customField->settings->searchable,
                query: fn (Builder $query, string $search) => (new ColumnSearchableQuery)->builder($query, $customField, $search),
            )
            ->getStateUsing(fn ($record) => $record->getCustomFieldValue($customField));
    }
}
