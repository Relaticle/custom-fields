<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Traits;

/**
 * ABOUTME: Trait providing field filtering functionality for builders
 * ABOUTME: Implements only() and except() methods for selective field inclusion/exclusion
 */
trait HasFieldFilters
{
    /**
     * Field codes to include
     *
     * @var array<string>
     */
    protected array $onlyFields = [];

    /**
     * Field codes to exclude
     *
     * @var array<string>
     */
    protected array $exceptFields = [];

    /**
     * Include only specific fields by their codes
     *
     * @param  array<string>  $fieldCodes
     * @return static
     */
    public function only(array $fieldCodes): static
    {
        $this->onlyFields = array_unique(array_merge($this->onlyFields, $fieldCodes));
        
        return $this;
    }

    /**
     * Exclude specific fields by their codes
     *
     * @param  array<string>  $fieldCodes
     * @return static
     */
    public function except(array $fieldCodes): static
    {
        $this->exceptFields = array_unique(array_merge($this->exceptFields, $fieldCodes));
        
        return $this;
    }

    /**
     * Check if a field should be included based on filters
     *
     * @param  string  $fieldCode
     * @return bool
     */
    protected function shouldIncludeField(string $fieldCode): bool
    {
        // If only() was used, field must be in the list
        if (! empty($this->onlyFields)) {
            return in_array($fieldCode, $this->onlyFields, true);
        }

        // If except() was used, field must not be in the list
        if (! empty($this->exceptFields)) {
            return ! in_array($fieldCode, $this->exceptFields, true);
        }

        // No filters applied, include all fields
        return true;
    }

    /**
     * Reset field filters
     *
     * @return void
     */
    protected function resetFieldFilters(): void
    {
        $this->onlyFields = [];
        $this->exceptFields = [];
    }
}