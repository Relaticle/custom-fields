<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Forms\Components\Traits;

use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use ReflectionException;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\FilamentResourceService;
use Throwable;

/**
 * Trait for configuring lookup options in form components.
 *
 * Eliminates duplication across multiple components that need to resolve
 * options from either custom field options or external model lookups.
 *
 * Used by: SelectComponent, RadioComponent, CheckboxListComponent,
 * TagsInputComponent, and other optionable components.
 */
trait ConfiguresLookups
{
    /**
     * Get options array for a custom field, resolving from lookup or field options.
     *
     * This method handles the two main option sources:
     * 1. Lookup types: Query external models using FilamentResourceService
     * 2. Field options: Use the custom field's configured options
     *
     * @return array<int|string, string> Options as key => label pairs
     */
    protected function getFieldOptions(CustomField $customField, int $limit = 50): array
    {
        if ($customField->lookup_type) {
            return $this->getLookupOptions($customField->lookup_type, $limit);
        }

        return $this->getCustomFieldOptions($customField);
    }

    /**
     * Get options from lookup type using FilamentResourceService.
     *
     * @return array<int|string, string>
     */
    protected function getLookupOptions(string $lookupType, int $limit = 50): array
    {
        /** @var Model $entityInstance */
        $entityInstance = FilamentResourceService::getModelInstance($lookupType);
        $recordTitleAttribute = FilamentResourceService::getRecordTitleAttribute($lookupType);

        /** @var Builder<Model> $query */
        $query = $entityInstance->newQuery();

        return $query->limit($limit)->pluck($recordTitleAttribute, 'id')->toArray();
    }

    /**
     * Get options from custom field's configured options.
     *
     * @return array<int|string, string>
     */
    protected function getCustomFieldOptions(CustomField $customField): array
    {
        return $customField->options->pluck('name', 'id')->all();
    }

    /**
     * Get advanced lookup options with full query builder access.
     *
     * For components like SelectComponent that need more sophisticated lookup handling.
     *
     * @return array{
     *   entityInstanceQuery: Builder<Model>,
     *   entityInstanceKeyName: string,
     *   recordTitleAttribute: string,
     *   entityInstance: Model
     * }
     */
    protected function getAdvancedLookupData(string $lookupType): array
    {
        $entityInstanceQuery = FilamentResourceService::getModelInstanceQuery($lookupType);
        $entityInstanceKeyName = $entityInstanceQuery->getModel()->getKeyName();
        $recordTitleAttribute = FilamentResourceService::getRecordTitleAttribute($lookupType);
        $entityInstance = FilamentResourceService::getModelInstance($lookupType);

        return [
            'entityInstanceQuery' => $entityInstanceQuery,
            'entityInstanceKeyName' => $entityInstanceKeyName,
            'recordTitleAttribute' => $recordTitleAttribute,
            'entityInstance' => $entityInstance,
        ];
    }

    /**
     * Check if field uses lookup type.
     */
    protected function usesLookupType(CustomField $customField): bool
    {
        return ! empty($customField->lookup_type);
    }

    /**
     * Configure a Select field with advanced lookup functionality.
     *
     * @throws Throwable
     * @throws ReflectionException
     */
    protected function configureAdvancedLookup(Select $select, string $lookupType): Select
    {
        $resource = FilamentResourceService::getResourceInstance($lookupType);
        $entityInstanceQuery = FilamentResourceService::getModelInstanceQuery($lookupType);
        $entityInstanceKeyName = $entityInstanceQuery->getModel()->getKeyName();
        $recordTitleAttribute = FilamentResourceService::getRecordTitleAttribute($lookupType);
        $globalSearchableAttributes = FilamentResourceService::getGlobalSearchableAttributes($lookupType);

        return $select
            ->options(function () use ($select, $entityInstanceQuery, $recordTitleAttribute, $entityInstanceKeyName): array {
                if (! $select->isPreloaded()) {
                    return [];
                }

                return $entityInstanceQuery
                    ->pluck($recordTitleAttribute, $entityInstanceKeyName)
                    ->toArray();
            })
            ->getSearchResultsUsing(function (string $search) use ($entityInstanceQuery, $entityInstanceKeyName, $recordTitleAttribute, $globalSearchableAttributes, $resource): array {
                FilamentResourceService::invokeMethodByReflection($resource, 'applyGlobalSearchAttributeConstraints', [
                    'query' => $entityInstanceQuery,
                    'search' => $search,
                    'searchableAttributes' => $globalSearchableAttributes,
                ]);

                return $entityInstanceQuery
                    ->limit(50)
                    ->pluck($recordTitleAttribute, $entityInstanceKeyName)
                    ->toArray();
            })
            ->getOptionLabelUsing(fn (mixed $value): ?string => $entityInstanceQuery->find($value)?->getAttribute($recordTitleAttribute))
            ->getOptionLabelsUsing(fn (array $values): array => $entityInstanceQuery
                ->whereIn($entityInstanceKeyName, $values)
                ->pluck($recordTitleAttribute, $entityInstanceKeyName)
                ->toArray());
    }
}
