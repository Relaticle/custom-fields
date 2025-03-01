<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Tables\Columns;

use Filament\Tables\Columns\ColorColumn as BaseColorColumn;
use Filament\Tables\Columns\Column as BaseColumn;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Queries\ColumnSearchableQuery;

final readonly class ColorColumn implements ColumnInterface
{
    public function make(CustomField $customField): BaseColumn
    {
        return BaseColorColumn::make("custom_fields.$customField->code")
            ->label($customField->name)
            ->searchable(
                condition: $customField->settings->searchable,
                query: fn (Builder $query, string $search) => (new ColumnSearchableQuery)->builder($query, $customField, $search),
            )
            ->getStateUsing(fn ($record) => $record->getCustomFieldValue($customField));
    }
}
