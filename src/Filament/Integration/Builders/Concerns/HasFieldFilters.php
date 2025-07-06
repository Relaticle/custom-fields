<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Builders\Concerns;

/**
 * ABOUTME: Trait for filtering fields with only() and except() methods
 * ABOUTME: Provides fluent API for including or excluding specific fields
 */
trait HasFieldFilters
{
    /**
     * Fields to include (whitelist)
     *
     * @var array<string>
     */
    protected array $onlyFields = [];

    /**
     * Fields to exclude (blacklist)
     *
     * @var array<string>
     */
    protected array $exceptFields = [];

    /**
     * Include only the specified fields
     *
     * @param  array<string>|string  $fields
     * @return $this
     */
    public function only(array|string $fields): static
    {
        $this->onlyFields = is_array($fields) ? $fields : [$fields];

        return $this;
    }

    /**
     * Exclude the specified fields
     *
     * @param  array<string>|string  $fields
     * @return $this
     */
    public function except(array|string $fields): static
    {
        $this->exceptFields = is_array($fields) ? $fields : [$fields];

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
        // If only fields are specified, the field must be in the list
        if (! empty($this->onlyFields)) {
            return in_array($fieldCode, $this->onlyFields, true);
        }

        // If except fields are specified, the field must not be in the list
        if (! empty($this->exceptFields)) {
            return ! in_array($fieldCode, $this->exceptFields, true);
        }

        // Include all fields by default
        return true;
    }

    /**
     * Reset field filters
     *
     * @return $this
     */
    public function withoutFilters(): static
    {
        $this->onlyFields = [];
        $this->exceptFields = [];

        return $this;
    }
}