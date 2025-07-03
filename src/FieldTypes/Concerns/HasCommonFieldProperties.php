<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\FieldTypes\Concerns;

/**
 * Provides default implementations for common field type properties.
 * This trait can be used by field type implementations to reduce boilerplate.
 */
trait HasCommonFieldProperties
{
    /**
     * Determine if this field type supports conditional visibility.
     * Default: true (most fields support this feature)
     */
    public function supportsConditionalVisibility(): bool
    {
        return true;
    }

    /**
     * Determine if this field type supports storing multiple values.
     * Default: false (most fields store single values)
     */
    public function supportsMultiplicity(): bool
    {
        return false;
    }

    /**
     * Determine if this field type is filterable in tables.
     * Default: true (most fields can be filtered)
     */
    public function isFilterable(): bool
    {
        return true;
    }

    /**
     * Determine if this field type is sortable in tables.
     * Default: true (most fields can be sorted)
     */
    public function isSortable(): bool
    {
        return true;
    }

    /**
     * Determine if this field type is searchable in tables.
     * Default: true (most fields can be searched)
     */
    public function isSearchable(): bool
    {
        return true;
    }

    /**
     * Determine if this field type supports encryption.
     * Default: true (most fields can be encrypted)
     */
    public function isEncryptable(): bool
    {
        return true;
    }

    /**
     * Get the priority for field type ordering in the admin panel.
     * Lower numbers appear first.
     * Default: 100 (neutral priority)
     */
    public function getPriority(): int
    {
        return 100;
    }
}
