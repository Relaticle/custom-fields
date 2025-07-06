<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Services;

use Illuminate\Support\Collection;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;

/**
 * ABOUTME: Repository service for fetching and filtering custom fields
 * ABOUTME: Handles database queries and eager loading for fields and sections
 */
class FieldRepository
{
    /**
     * Get sections with their fields for a specific entity type
     *
     * @param  string  $entityType
     * @return Collection<int, CustomFieldSection>
     */
    public function getSectionsWithFields(string $entityType): Collection
    {
        return CustomFieldSection::query()
            ->with(['fields' => function ($query) use ($entityType) {
                $query->where('entity_type', $entityType)
                    ->where('active', true)
                    ->orderBy('sort_order');
            }])
            ->where('entity_type', $entityType)
            ->where('active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get fields for a specific entity type
     *
     * @param  string  $entityType
     * @param  array<string>  $only
     * @param  array<string>  $except
     * @return Collection<int, CustomField>
     */
    public function getFields(string $entityType, array $only = [], array $except = []): Collection
    {
        $query = CustomField::query()
            ->where('entity_type', $entityType)
            ->where('active', true);

        if (! empty($only)) {
            $query->whereIn('code', $only);
        }

        if (! empty($except)) {
            $query->whereNotIn('code', $except);
        }

        return $query->orderBy('sort_order')->get();
    }

    /**
     * Get fields that should appear in table columns
     *
     * @param  string  $entityType
     * @return Collection<int, CustomField>
     */
    public function getTableFields(string $entityType): Collection
    {
        return CustomField::query()
            ->where('entity_type', $entityType)
            ->where('active', true)
            ->whereJsonContains('settings->visible_in_list', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get fields that can be used as filters
     *
     * @param  string  $entityType
     * @return Collection<int, CustomField>
     */
    public function getFilterableFields(string $entityType): Collection
    {
        return CustomField::query()
            ->where('entity_type', $entityType)
            ->where('active', true)
            ->whereJsonContains('settings->searchable', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get a single field by code and entity type
     *
     * @param  string  $code
     * @param  string  $entityType
     * @return CustomField|null
     */
    public function getFieldByCode(string $code, string $entityType): ?CustomField
    {
        return CustomField::query()
            ->where('code', $code)
            ->where('entity_type', $entityType)
            ->where('active', true)
            ->first();
    }

    /**
     * Get all fields with their sections for caching
     *
     * @param  string  $entityType
     * @return Collection<int, CustomField>
     */
    public function getAllFieldsWithSections(string $entityType): Collection
    {
        return CustomField::query()
            ->with('section')
            ->where('entity_type', $entityType)
            ->where('active', true)
            ->orderBy('sort_order')
            ->get();
    }
}