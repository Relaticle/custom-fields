<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Builders;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Relaticle\CustomFields\Filament\Integration\Factories\FormComponentFactory;
use Relaticle\CustomFields\Filament\Integration\Services\FieldRepository;
use Relaticle\CustomFields\Filament\Integration\Services\VisibilityResolver;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;

/**
 * ABOUTME: Builder for creating Filament form components from custom fields
 * ABOUTME: Returns collection of sections with form fields organized by custom field sections
 */
class FormBuilder extends CustomFieldsBuilder
{
    /**
     * Create a new form builder instance
     */
    public function __construct(
        FieldRepository $fieldRepository,
        FormComponentFactory $factory,
        VisibilityResolver $visibilityResolver
    ) {
        parent::__construct($fieldRepository, $factory, $visibilityResolver);
    }
    /**
     * Get form components organized by sections
     *
     * @return Collection<int, Section>
     */
    public function components(): Collection
    {
        $sections = $this->getSectionsWithFields()
            ->filter(fn (CustomFieldSection $section) => $this->hasVisibleFields($section));

        return $sections->map(function (CustomFieldSection $section) {
            return $this->createSectionComponent($section);
        });
    }

    /**
     * Build the complete form schema
     *
     * @return Component
     */
    public function build(): Component
    {
        $components = $this->components();

        // If no sections, return empty grid
        if ($components->isEmpty()) {
            return Grid::make()->schema([]);
        }

        // If single section without label, return its schema directly
        if ($components->count() === 1) {
            $section = $components->first();
            if ($section instanceof Section && empty($section->getHeading())) {
                return Grid::make()->schema($section->getChildComponents());
            }
        }

        // Return grid with all sections
        return Grid::make()
            ->schema($components->toArray())
            ->columns(1);
    }

    /**
     * Create a section component from a custom field section
     *
     * @param  CustomFieldSection  $section
     * @return Section
     */
    protected function createSectionComponent(CustomFieldSection $section): Section
    {
        $sectionComponent = Section::make($section->name)
            ->schema($this->buildFieldsForSection($section))
            ->collapsed($section->settings?->collapsed ?? false);

        if ($section->description) {
            $sectionComponent->description($section->description);
        }

        if ($section->icon) {
            $sectionComponent->icon($section->icon);
        }

        // Apply section configuration
        $config = $section->section_config ?? [];
        
        if (isset($config['columns'])) {
            $sectionComponent->columns($config['columns']);
        }

        if (isset($config['columnSpan'])) {
            $sectionComponent->columnSpan($config['columnSpan']);
        }

        if (isset($config['collapsible']) && $config['collapsible']) {
            $sectionComponent->collapsible();
        }

        if (isset($config['compact']) && $config['compact']) {
            $sectionComponent->compact();
        }

        return $sectionComponent;
    }

    /**
     * Create a form component for a custom field
     *
     * @param  CustomField  $field
     * @return Component|null
     */
    protected function createComponentForField(CustomField $field): ?Component
    {
        try {
            $component = $this->factory->createComponent($field);

            if (! $component instanceof Component) {
                return null;
            }

            // Apply visibility rules if needed
            if ($this->visibilityResolver->hasVisibilityDependencies($field)) {
                $this->applyVisibilityRules($component, $field);
            }

            return $component;
        } catch (\Exception $e) {
            // Log error and skip field
            logger()->error('Failed to create form component for field', [
                'field' => $field->code,
                'type' => $field->type,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Apply visibility rules to a component
     *
     * @param  Component  $component
     * @param  CustomField  $field
     * @return void
     */
    protected function applyVisibilityRules(Component $component, CustomField $field): void
    {
        $visibilityConfig = $this->visibilityResolver->buildVisibilityConfiguration($field);

        foreach ($visibilityConfig as $rule) {
            $fieldPath = $rule['field'];
            $operator = $rule['operator'];
            $value = $rule['value'];

            // Apply Filament visibility methods based on operator
            switch ($operator) {
                case 'is':
                    $component->visible(fn ($get) => $get($fieldPath) === $value);
                    break;
                case 'isNot':
                    $component->visible(fn ($get) => $get($fieldPath) !== $value);
                    break;
                case 'gt':
                    $component->visible(fn ($get) => $get($fieldPath) > $value);
                    break;
                case 'lt':
                    $component->visible(fn ($get) => $get($fieldPath) < $value);
                    break;
                case 'gte':
                    $component->visible(fn ($get) => $get($fieldPath) >= $value);
                    break;
                case 'lte':
                    $component->visible(fn ($get) => $get($fieldPath) <= $value);
                    break;
                case 'contains':
                    $component->visible(fn ($get) => str_contains((string) $get($fieldPath), (string) $value));
                    break;
                case 'doesNotContain':
                    $component->visible(fn ($get) => ! str_contains((string) $get($fieldPath), (string) $value));
                    break;
                case 'isEmpty':
                    $component->visible(fn ($get) => empty($get($fieldPath)));
                    break;
                case 'isNotEmpty':
                    $component->visible(fn ($get) => ! empty($get($fieldPath)));
                    break;
            }
        }
    }

    /**
     * Set whether to include fields without sections
     *
     * @param  bool  $include
     * @return $this
     */
    public function includeFieldsWithoutSection(bool $include = true): static
    {
        // This could be implemented if needed
        return $this;
    }

    /**
     * Set custom section configuration
     *
     * @param  array  $config
     * @return $this
     */
    public function sectionConfig(array $config): static
    {
        // This could be implemented if needed
        return $this;
    }
}