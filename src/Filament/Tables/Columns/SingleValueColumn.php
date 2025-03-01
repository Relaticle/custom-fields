<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Tables\Columns;

use Filament\Tables\Columns\Column as BaseColumn;
use Filament\Tables\Columns\TextColumn as BaseTextColumn;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\ValueResolver\LookupSingleValueResolver;

final readonly class SingleValueColumn implements ColumnInterface
{
    public function __construct(public LookupSingleValueResolver $valueResolver) {}

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
            ->searchable(false)
            ->getStateUsing(fn ($record) => $this->valueResolver->resolve($record, $customField));
    }
}
