<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Traits;

use Illuminate\Support\Collection;
use Relaticle\CustomFields\Contracts\Factories\ComponentFactoryInterface;
use Relaticle\CustomFields\Contracts\Services\FieldRepositoryInterface;
use Relaticle\CustomFields\Contracts\Services\StateManagerInterface;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;

/**
 * ABOUTME: Trait providing component building functionality for builders
 * ABOUTME: Handles component creation, configuration, and visibility rules
 */
trait BuildsComponents
{
    /**
     * Sections to include
     *
     * @var array<string>
     */
    protected array $sections = [];

    /**
     * Whether to apply visibility rules
     *
     * @var bool
     */
    protected bool $applyVisibility = true;

    /**
     * Include fields from a specific section
     *
     * @param  string  $sectionCode
     * @return static
     */
    public function withSection(string $sectionCode): static
    {
        $this->sections[] = $sectionCode;
        $this->sections = array_unique($this->sections);

        return $this;
    }

    /**
     * Include fields from multiple sections
     *
     * @param  array<string>  $sectionCodes
     * @return static
     */
    public function withSections(array $sectionCodes): static
    {
        $this->sections = array_unique(array_merge($this->sections, $sectionCodes));

        return $this;
    }

    /**
     * Configure visibility rules handling
     *
     * @param  bool  $apply
     * @return static
     */
    public function applyVisibilityRules(bool $apply = true): static
    {
        $this->applyVisibility = $apply;

        return $this;
    }

    /**
     * Get fields for building components
     *
     * @return Collection<int, CustomField>
     */
    protected function getFieldsForBuilding(): Collection
    {
        if (! $this->hasModelClass()) {
            return collect();
        }

        $repository = $this->getFieldRepository();
        $fields = $repository->getFieldsForModel($this->getModelClass());

        // Filter by sections if specified
        if (! empty($this->sections)) {
            $fields = $fields->filter(function (CustomField $field): bool {
                return in_array($field->section?->code, $this->sections, true);
            });
        }

        // Apply field filters
        if (method_exists($this, 'shouldIncludeField')) {
            $fields = $fields->filter(function (CustomField $field): bool {
                return $this->shouldIncludeField($field->code);
            });
        }

        return $fields;
    }

    /**
     * Get sections for building components
     *
     * @return Collection<int, CustomFieldSection>
     */
    protected function getSectionsForBuilding(): Collection
    {
        if (! $this->hasModelClass()) {
            return collect();
        }

        $repository = $this->getFieldRepository();
        $sections = $repository->getSectionsForModel($this->getModelClass());

        // Filter by specified sections
        if (! empty($this->sections)) {
            $sections = $sections->filter(function (CustomFieldSection $section): bool {
                return in_array($section->code, $this->sections, true);
            });
        }

        return $sections;
    }

    /**
     * Apply common configuration to a component
     *
     * @param  mixed  $component
     * @param  CustomField  $field
     * @return mixed
     */
    protected function configureComponent(mixed $component, CustomField $field): mixed
    {
        // This method will be extended by specific builders
        // to apply their own configuration logic
        
        return $component;
    }

    /**
     * Reset component building state
     *
     * @return void
     */
    protected function resetComponentBuilding(): void
    {
        $this->sections = [];
        $this->applyVisibility = true;
    }

    /**
     * These methods must be implemented by the class using this trait
     */
    abstract protected function getFieldRepository(): FieldRepositoryInterface;
    
    abstract protected function getStateManager(): StateManagerInterface;
    
    abstract protected function getComponentFactory(): ComponentFactoryInterface;
    
    abstract protected function hasModelClass(): bool;
    
    abstract protected function getModelClass(): ?string;
}