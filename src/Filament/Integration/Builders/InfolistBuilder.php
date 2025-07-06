<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Builders;

use Filament\Infolists\Components\Component;
use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Relaticle\CustomFields\Filament\Integration\Factories\InfolistComponentFactory;
use Relaticle\CustomFields\Filament\Integration\Services\FieldRepository;
use Relaticle\CustomFields\Filament\Integration\Services\VisibilityResolver;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;

/**
 * ABOUTME: Builder for creating Filament infolist components from custom fields
 * ABOUTME: Returns collection of sections with infolist entries organized by custom field sections
 */
class InfolistBuilder extends CustomFieldsBuilder
{
    /**
     * Create a new infolist builder instance
     */
    public function __construct(
        FieldRepository $fieldRepository,
        InfolistComponentFactory $factory,
        VisibilityResolver $visibilityResolver
    ) {
        parent::__construct($fieldRepository, $factory, $visibilityResolver);
    }
    /**
     * Whether to show empty values
     *
     * @var bool
     */
    protected bool $showEmptyValues = false;

    /**
     * Get infolist components organized by sections
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
     * Get infolist entries without sections
     *
     * @return Collection<int, Entry>
     */
    public function entries(): Collection
    {
        $fields = $this->getFields();

        return $fields->map(function (CustomField $field) {
            return $this->createEntryForField($field);
        })->filter()->values();
    }

    /**
     * Build the complete infolist schema
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
     * Show empty values in the infolist
     *
     * @param  bool  $show
     * @return $this
     */
    public function showEmptyValues(bool $show = true): static
    {
        $this->showEmptyValues = $show;

        return $this;
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
            ->schema($this->buildFieldsForSection($section));

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

        if (isset($config['collapsed']) && $config['collapsed']) {
            $sectionComponent->collapsed();
        }

        return $sectionComponent;
    }

    /**
     * Create an infolist entry for a custom field
     *
     * @param  CustomField  $field
     * @return Entry|null
     */
    protected function createEntryForField(CustomField $field): ?Entry
    {
        try {
            $component = $this->factory->createComponent($field);

            if (! $component instanceof Entry) {
                return null;
            }

            // Configure entry-specific settings
            $this->configureEntry($component, $field);

            // Apply visibility rules if needed
            if ($this->visibilityResolver->hasVisibilityDependencies($field)) {
                $this->applyVisibilityRules($component, $field);
            }

            return $component;
        } catch (\Exception $e) {
            logger()->error('Failed to create infolist entry for field', [
                'field' => $field->code,
                'type' => $field->type,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Configure additional entry properties
     *
     * @param  Entry  $entry
     * @param  CustomField  $field
     * @return void
     */
    protected function configureEntry(Entry $entry, CustomField $field): void
    {
        // Hide empty values unless explicitly requested
        if (! $this->showEmptyValues) {
            $entry->hidden(fn ($state) => empty($state));
        }

        $config = $field->field_config ?? [];

        // Apply entry-specific configuration
        if (isset($config['infolistColumnSpan'])) {
            $entry->columnSpan($config['infolistColumnSpan']);
        }

        if (isset($config['infolistCopyable']) && $config['infolistCopyable']) {
            if (method_exists($entry, 'copyable')) {
                $entry->copyable();
            }
        }

        if (isset($config['infolistTooltip'])) {
            $entry->tooltip($config['infolistTooltip']);
        }

        if (isset($config['infolistHint'])) {
            $entry->hint($config['infolistHint']);
        }

        if (isset($config['infolistHintIcon'])) {
            $entry->hintIcon($config['infolistHintIcon']);
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
     * Create component for field (required by abstract parent)
     *
     * @param  CustomField  $field
     * @return mixed
     */
    protected function createComponentForField(CustomField $field): mixed
    {
        return $this->createEntryForField($field);
    }
}