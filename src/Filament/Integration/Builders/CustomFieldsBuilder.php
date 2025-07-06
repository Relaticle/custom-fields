<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Builders;

use Illuminate\Support\Collection;
use Relaticle\CustomFields\Contracts\Factories\ComponentFactoryInterface;
use Relaticle\CustomFields\Filament\Integration\Services\FieldRepository;
use Relaticle\CustomFields\Filament\Integration\Services\VisibilityResolver;

/**
 * ABOUTME: Abstract base class for all custom field builders
 * ABOUTME: Provides common functionality for building Filament components from custom fields
 */
abstract class CustomFieldsBuilder
{
    use Concerns\HasFieldFilters;
    use Concerns\HasModelContext;
    use Concerns\BuildsComponents;

    /**
     * Create a new builder instance
     *
     * @param  FieldRepository  $fieldRepository
     * @param  ComponentFactoryInterface  $factory
     * @param  VisibilityResolver  $visibilityResolver
     */
    public function __construct(
        protected FieldRepository $fieldRepository,
        protected ComponentFactoryInterface $factory,
        protected VisibilityResolver $visibilityResolver
    ) {
    }

    /**
     * Get the components for this builder
     *
     * @return Collection
     */
    abstract public function components(): Collection;

    /**
     * Build and return the final component/schema
     * This method can be overridden by concrete builders if needed
     *
     * @return mixed
     */
    public function build(): mixed
    {
        return $this->components();
    }

    /**
     * Create a new instance of the builder
     *
     * @return static
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Get sections with fields for the current entity type
     *
     * @return Collection
     */
    protected function getSectionsWithFields(): Collection
    {
        return $this->fieldRepository->getSectionsWithFields($this->getEntityType());
    }

    /**
     * Get fields for the current entity type
     *
     * @return Collection
     */
    protected function getFields(): Collection
    {
        return $this->fieldRepository->getFields(
            $this->getEntityType(),
            $this->onlyFields,
            $this->exceptFields
        );
    }

    /**
     * Reset the builder to its initial state
     *
     * @return $this
     */
    public function reset(): static
    {
        $this->withoutFilters();
        $this->withoutModel();

        return $this;
    }
}