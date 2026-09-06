<?php

declare(strict_types=1);

// ABOUTME: Builder for creating Filament table columns and filters from custom fields
// ABOUTME: Provides fluent API for generating table components with filtering support

namespace Relaticle\CustomFields\Filament\Integration\Builders;

use Closure;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Enums\CustomFieldsFeature;
use Relaticle\CustomFields\FeatureSystem\FeatureManager;
use Relaticle\CustomFields\Filament\Integration\Components\Tables\Columns\RecordColumnView;
use Relaticle\CustomFields\Filament\Integration\Factories\FieldColumnFactory;
use Relaticle\CustomFields\Filament\Integration\Factories\FieldFilterFactory;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\QueryBuilders\ColumnSearchableQuery;
use Relaticle\CustomFields\Services\Visibility\BackendVisibilityService;
use Relaticle\CustomFields\Support\ThroughRelationResolver;

final class TableBuilder extends BaseBuilder
{
    private ?string $through = null;

    /**
     * Read the fields of a to-one related record instead of the row record, for tables whose
     * rows carry no custom fields of their own.
     */
    public function through(string $relation): static
    {
        $this->through = $relation;

        return $this;
    }

    /**
     * @return Collection<int, Column>
     */
    public function columns(): Collection
    {
        if (! FeatureManager::isEnabled(CustomFieldsFeature::UI_TABLE_COLUMNS)) {
            return collect();
        }

        $fieldColumnFactory = app(FieldColumnFactory::class);
        $backendVisibilityService = app(BackendVisibilityService::class);

        // Get all fields using the most efficient method
        $allFields = $this->getAllFields();

        return $allFields
            ->filter(fn (CustomField $field): bool => $field->typeData->tableColumn !== null)
            ->map(function (CustomField $field) use ($fieldColumnFactory, $backendVisibilityService, $allFields) {
                $column = $fieldColumnFactory->create($field);

                $this->readThroughRelation($column, $field);

                if (! method_exists($column, 'formatStateUsing')) {
                    return $column;
                }

                $existingFormatter = (fn (): ?Closure => $this->formatStateUsing)->call($column); // @phpstan-ignore property.notFound

                $column->formatStateUsing(function (mixed $state, mixed $record) use ($field, $backendVisibilityService, $allFields, $existingFormatter, $column): mixed {
                    $subject = $this->fieldRecord($record);

                    if (! $subject instanceof Model) {
                        return null;
                    }

                    if (! $backendVisibilityService->isFieldVisible($subject, $field, $allFields)) {
                        return null;
                    }

                    if ($existingFormatter) {
                        return $column->evaluate($existingFormatter, [
                            'state' => $state,
                            'record' => $subject,
                            'column' => $column,
                        ]);
                    }

                    return $state;
                });

                return $column;
            })
            ->values();
    }

    /**
     * @return Collection<int, BaseFilter>
     */
    public function filters(): Collection
    {
        if (! FeatureManager::isEnabled(CustomFieldsFeature::UI_TABLE_FILTERS)) {
            return collect();
        }

        $fieldFilterFactory = app(FieldFilterFactory::class);

        return $this->getAllFields()
            ->filter(fn (CustomField $field): bool => $field->isFilterable() && $field->typeData->tableFilter !== null)
            ->map(fn (CustomField $field) => $fieldFilterFactory->create($field))
            ->filter()
            ->values();
    }

    /**
     * Point a factory-built column at the related record. Every setter here is idempotent,
     * so this replaces what the field type configured instead of layering onto it.
     */
    private function readThroughRelation(Column $column, CustomField $field): void
    {
        if ($this->through === null) {
            return;
        }

        $relation = $this->through;
        $resolver = app(ThroughRelationResolver::class);

        $column->getStateUsing(
            fn (Model $record): mixed => $resolver->relatedRecord($record, $relation)?->getCustomFieldValue($field)
        );

        if ($column instanceof RecordColumnView) {
            // Ordering a record field through a relation would join the link ledger a second
            // time, so the column stays readable and filterable instead of ordering by nothing.
            $column->through($relation)->sortable(false);
        } elseif ($column->isSortable()) {
            $column->sortable(
                condition: true,
                query: fn (Builder $query, string $direction): Builder => $resolver->orderByFieldValue($query, $relation, $field, $direction),
            );
        }

        if (! $column->isSearchable()) {
            return;
        }

        $column->searchable(
            condition: true,
            query: fn (Builder $query, string $search): Builder => $resolver->constrain(
                $query,
                $relation,
                fn (Builder $related): Builder => (new ColumnSearchableQuery)->builder($related, $field, $search),
            ),
        );
    }

    /**
     * The record a column's state, visibility and formatter are evaluated against.
     */
    private function fieldRecord(mixed $record): ?Model
    {
        if (! $record instanceof Model) {
            return null;
        }

        if ($this->through === null) {
            return $record;
        }

        return app(ThroughRelationResolver::class)->relatedRecord($record, $this->through);
    }
}
