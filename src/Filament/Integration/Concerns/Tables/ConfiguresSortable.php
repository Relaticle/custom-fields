<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Concerns\Tables;

use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\CustomFields\Models\CustomField;

/**
 * ABOUTME: Trait providing sortable configuration for table columns.
 * ABOUTME: Extracts common sortable query logic used across multiple column types.
 */
trait ConfiguresSortable
{
    use \Relaticle\CustomFields\Filament\Integration\Concerns\Shared\ConfiguresEncryption;

    /**
     * Configure sortable behavior for a column.
     */
    protected function configureSortable(Column $column, CustomField $customField): Column
    {
        return $column->sortable(
            condition: $this->isNotEncrypted($customField),
            query: function (Builder $query, string $direction) use ($customField): Builder {
                $table = $query->getModel()->getTable();
                $key = $query->getModel()->getKeyName();

                return $query->orderBy(
                    $customField->values()
                        ->select($customField->getValueColumn())
                        ->whereColumn('custom_field_values.entity_id', "$table.$key")
                        ->limit(1)
                        ->getQuery(),
                    $direction
                );
            }
        );
    }
}
