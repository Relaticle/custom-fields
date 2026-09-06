<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Tables\Columns;

use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Relaticle\CustomFields\Facades\Entities;
use Relaticle\CustomFields\Filament\Integration\Base\AbstractTableColumn;
use Relaticle\CustomFields\Filament\Integration\Concerns\Tables\ConfiguresColumnLabel;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\QueryBuilders\RecordLinkQuery;

final class RecordColumn extends AbstractTableColumn
{
    use ConfiguresColumnLabel;

    public function make(CustomField $customField, ?Model $record = null): RecordColumnView
    {
        $column = RecordColumnView::make($customField->getFieldName())
            ->customField($customField)
            ->width('200px')
            ->disabledClick()
            ->extraCellAttributes([
                'style' => 'min-width: 200px; max-width: 200px; overflow: hidden;',
            ]);

        $this->configureLabel($column, $customField);
        $this->configureSorting($column, $customField);
        $this->configureSearching($column, $customField);

        return $column;
    }

    /**
     * Sorting joins the target's primary attribute, so an entity the host has not registered
     * leaves the column unsorted rather than ordering by nothing.
     */
    private function configureSorting(RecordColumnView $column, CustomField $customField): void
    {
        $definition = $customField->relationshipDefinitionOrFail();
        $attribute = $this->primaryAttribute($definition->targetEntityTypeFor($customField));

        $column->sortable(
            condition: $attribute !== null,
            query: function (Builder $query, string $direction) use ($customField, $definition, $attribute): Builder {
                if ($attribute === null) {
                    return $query;
                }

                return app(RecordLinkQuery::class)->orderByLinkedAttribute(
                    $query,
                    $definition,
                    $definition->readDirectionFor($customField),
                    $attribute,
                    $direction,
                );
            },
        );
    }

    private function configureSearching(RecordColumnView $column, CustomField $customField): void
    {
        $definition = $customField->relationshipDefinitionOrFail();

        $column->searchable(
            condition: $customField->settings->searchable,
            query: fn (Builder $query, string $search): Builder => app(RecordLinkQuery::class)->whereLinkedMatching(
                $query,
                $definition,
                $definition->readDirectionFor($customField),
                $this->searchAttributes($definition->targetEntityTypeFor($customField)),
                $search,
            ),
        );
    }

    private function primaryAttribute(string $entityType): ?string
    {
        return Entities::getEntity($entityType)?->getPrimaryAttribute();
    }

    /**
     * @return array<int, string>
     */
    private function searchAttributes(string $entityType): array
    {
        $entity = Entities::getEntity($entityType);

        if ($entity === null) {
            return [];
        }

        $attributes = $entity->getSearchAttributes();

        return $attributes === [] ? [$entity->getPrimaryAttribute()] : $attributes;
    }
}
