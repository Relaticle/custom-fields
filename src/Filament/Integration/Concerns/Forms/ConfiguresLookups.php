<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Concerns\Forms;

use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;
use ReflectionException;
use Relaticle\CustomFields\Facades\Entities;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Support\Utils;
use Throwable;

/**
 * Trait for configuring lookup options in form components.
 *
 * Removes duplication across multiple components that need to resolve
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
     * 1. Lookup types: Query external models using Entity Management System
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
     * Get options from lookup type using Entity Management System.
     *
     * @return array<int|string, string>
     */
    protected function getLookupOptions(string $lookupType, int $limit = 50): array
    {
        $entity = Entities::getEntity($lookupType);

        if (! $entity) {
            throw new InvalidArgumentException('No entity found for lookup type: '.$lookupType);
        }

        $query = $entity->newQuery();
        $primaryAttribute = $entity->getPrimaryAttribute();

        return $query->limit($limit)->pluck($primaryAttribute, 'id')->toArray();
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
        $entity = Entities::getEntity($lookupType);

        if (! $entity) {
            throw new InvalidArgumentException('No entity found for lookup type: '.$lookupType);
        }

        $entityInstanceQuery = $entity->newQuery();
        $entityInstance = $entity->createModelInstance();
        $entityInstanceKeyName = $entityInstance->getKeyName();
        $recordTitleAttribute = $entity->getPrimaryAttribute();

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
        $entity = Entities::getEntity($lookupType);

        if (! $entity) {
            throw new InvalidArgumentException('No entity found for lookup type: '.$lookupType);
        }

        $entityInstanceQuery = $entity->newQuery();
        $entityInstanceKeyName = $entity->createModelInstance()->getKeyName();
        $recordTitleAttribute = $entity->getPrimaryAttribute();
        $globalSearchableAttributes = $entity->getSearchAttributes();
        $resource = null;

        if ($entity->getResourceClass()) {
            try {
                $resource = App::make($entity->getResourceClass());
            } catch (Throwable) {
                $resource = null;
                // No resource available
            }
        } else {
            $resource = null;
        }

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
                if ($resource instanceof Resource) {
                    Utils::invokeMethodByReflection($resource, 'applyGlobalSearchAttributeConstraints', [
                        $entityInstanceQuery,
                        $search,
                        $globalSearchableAttributes,
                    ]);
                } else {
                    // Apply search constraints manually if no resource
                    $entityInstanceQuery->where(function ($query) use ($search, $globalSearchableAttributes, $recordTitleAttribute): void {
                        $searchAttributes = empty($globalSearchableAttributes) ? [$recordTitleAttribute] : $globalSearchableAttributes;
                        foreach ($searchAttributes as $attribute) {
                            $query->orWhere($attribute, 'like', sprintf('%%%s%%', $search));
                        }
                    });
                }

                return $entityInstanceQuery
                    ->limit(50)
                    ->pluck($recordTitleAttribute, $entityInstanceKeyName)
                    ->toArray();
            })
            ->getOptionLabelUsing(fn (mixed $value): string|int|null => $entityInstanceQuery->find($value)?->getAttribute($recordTitleAttribute))
            ->getOptionLabelsUsing(fn (array $values): array => $entityInstanceQuery
                ->whereIn($entityInstanceKeyName, $values)
                ->pluck($recordTitleAttribute, $entityInstanceKeyName)
                ->toArray());
    }
}
