<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Builders;

use Relaticle\CustomFields\Contracts\Builders\CustomFieldsBuilderInterface;
use Relaticle\CustomFields\Contracts\Factories\ComponentFactoryInterface;
use Relaticle\CustomFields\Contracts\Services\FieldRepositoryInterface;
use Relaticle\CustomFields\Contracts\Services\StateManagerInterface;
use Relaticle\CustomFields\Traits\BuildsComponents;
use Relaticle\CustomFields\Traits\HasFieldFilters;
use Relaticle\CustomFields\Traits\HasModelContext;

/**
 * ABOUTME: Abstract base class for all custom fields builders
 * ABOUTME: Provides common functionality for building Filament components from custom fields
 */
abstract class AbstractCustomFieldsBuilder implements CustomFieldsBuilderInterface
{
    use HasFieldFilters;
    use HasModelContext;
    use BuildsComponents;

    /**
     * Create a new builder instance
     *
     * @param  FieldRepositoryInterface  $fieldRepository
     * @param  StateManagerInterface  $stateManager
     * @param  ComponentFactoryInterface  $componentFactory
     */
    public function __construct(
        protected FieldRepositoryInterface $fieldRepository,
        protected StateManagerInterface $stateManager,
        protected ComponentFactoryInterface $componentFactory,
    ) {
    }

    /**
     * Reset the builder to its initial state
     *
     * @return static
     */
    public function reset(): static
    {
        $this->resetFieldFilters();
        $this->resetModelContext();
        $this->resetComponentBuilding();

        return $this;
    }

    /**
     * Get the field repository instance
     *
     * @return FieldRepositoryInterface
     */
    protected function getFieldRepository(): FieldRepositoryInterface
    {
        return $this->fieldRepository;
    }

    /**
     * Get the state manager instance
     *
     * @return StateManagerInterface
     */
    protected function getStateManager(): StateManagerInterface
    {
        return $this->stateManager;
    }

    /**
     * Get the component factory instance
     *
     * @return ComponentFactoryInterface
     */
    protected function getComponentFactory(): ComponentFactoryInterface
    {
        return $this->componentFactory;
    }

    /**
     * Validate that the builder is properly configured
     *
     * @return void
     * @throws \RuntimeException
     */
    protected function validateConfiguration(): void
    {
        if (! $this->hasModelClass()) {
            throw new \RuntimeException('Model class must be set before building. Use forModel() or forRecord().');
        }
    }

    /**
     * Build and return the result
     *
     * @return mixed
     */
    abstract public function build(): mixed;
}