<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\FieldTypeSystem\Concerns;

use Relaticle\CustomFields\Enums\VisibilityOperator;

trait ConfiguresCapabilities
{
    private bool $searchable = true;

    private bool $sortable = true;

    private bool $filterable = false;

    private bool $encryptable = false;

    private bool $acceptsArbitraryValues = false;

    private bool $supportsMultiValue = false;

    private bool $supportsUniqueConstraint = false;

    private bool $withoutUserOptions = false;

    private bool $requiresLookupType = false;

    /** @var array<int, VisibilityOperator>|null */
    private ?array $visibilityOperators = null;

    /**
     * Configure searchability in tables
     */
    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * Configure sortability in tables
     */
    public function sortable(bool $sortable = true): self
    {
        $this->sortable = $sortable;

        return $this;
    }

    /**
     * Configure filterability in tables
     */
    public function filterable(bool $filterable = true): self
    {
        $this->filterable = $filterable;

        return $this;
    }

    /**
     * Configure encryption capability
     */
    public function encryptable(bool $encryptable = true): self
    {
        $this->encryptable = $encryptable;

        return $this;
    }

    /**
     * Configure whether field accepts arbitrary values (like tags input)
     */
    public function withArbitraryValues(bool $accepts = true): self
    {
        $this->acceptsArbitraryValues = $accepts;

        return $this;
    }

    /**
     * Configure whether field supports multiple values (e.g., multiple emails, phones)
     */
    public function supportsMultiValue(bool $supports = true): self
    {
        $this->supportsMultiValue = $supports;

        return $this;
    }

    /**
     * Configure whether field supports unique value constraint per entity type
     */
    public function supportsUniqueConstraint(bool $supports = true): self
    {
        $this->supportsUniqueConstraint = $supports;

        return $this;
    }

    /**
     * Enable encryption for this field (text fields)
     */
    public function encrypted(): self
    {
        $this->encryptable();

        return $this;
    }

    /**
     * Configure as a long text field (textarea)
     */
    public function longText(): self
    {
        return $this;
    }

    /**
     * Allow users to create new options on the fly (choice fields)
     */
    public function allowArbitraryValues(): self
    {
        $this->withArbitraryValues();

        return $this;
    }

    /**
     * Field doesn't need user-configured options (choice fields)
     * This disables database options UI and enables dynamic extraction from components
     */
    public function withoutUserOptions(): self
    {
        $this->withoutUserOptions = true;

        return $this;
    }

    /**
     * Override the default visibility operators derived from the data type.
     *
     * @param  array<int, VisibilityOperator>  $operators
     */
    public function visibilityOperators(array $operators): self
    {
        $this->visibilityOperators = $operators;

        return $this;
    }

    /**
     * Field requires lookup_type selection (entity type selector)
     * This shows the entity selector directly without the options toggle
     */
    public function requiresLookupType(bool $requires = true): self
    {
        $this->requiresLookupType = $requires;

        return $this;
    }

    /**
     * Check if field is searchable
     */
    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    /**
     * Check if field is sortable
     */
    public function isSortable(): bool
    {
        return $this->sortable;
    }

    /**
     * Check if field is filterable
     */
    public function isFilterable(): bool
    {
        return $this->filterable;
    }

    /**
     * Check if field is encryptable
     */
    public function isEncryptable(): bool
    {
        return $this->encryptable;
    }

    /**
     * Check if field accepts arbitrary values
     */
    public function acceptsArbitraryValues(): bool
    {
        return $this->acceptsArbitraryValues;
    }
}
