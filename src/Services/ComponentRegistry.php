<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services;

use Relaticle\CustomFields\Contracts\Services\ServiceInterface;

/**
 * ABOUTME: Service for registering and retrieving component classes for field types
 * ABOUTME: Maps field types to their corresponding Filament component implementations
 */
class ComponentRegistry implements ServiceInterface
{
    /**
     * Whether the service is initialized
     */
    protected bool $initialized = false;

    /**
     * Configuration array
     *
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * Registry of form components
     *
     * @var array<string, class-string>
     */
    protected array $formComponents = [];

    /**
     * Registry of table columns
     *
     * @var array<string, class-string>
     */
    protected array $tableColumns = [];

    /**
     * Registry of table filters
     *
     * @var array<string, class-string>
     */
    protected array $tableFilters = [];

    /**
     * Registry of infolist entries
     *
     * @var array<string, class-string>
     */
    protected array $infolistEntries = [];

    /**
     * Check if the service is properly initialized
     *
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Initialize the service with configuration
     *
     * @param  array<string, mixed>  $config
     * @return void
     */
    public function initialize(array $config = []): void
    {
        $this->config = $config;
        $this->registerDefaultComponents();
        $this->initialized = true;
    }

    /**
     * Register a form component for a field type
     *
     * @param  string  $fieldType
     * @param  class-string  $componentClass
     * @return void
     */
    public function registerFormComponent(string $fieldType, string $componentClass): void
    {
        $this->formComponents[$fieldType] = $componentClass;
    }

    /**
     * Get form component class for a field type
     *
     * @param  string  $fieldType
     * @return class-string|null
     */
    public function getFormComponent(string $fieldType): ?string
    {
        return $this->formComponents[$fieldType] ?? null;
    }

    /**
     * Register a table column for a field type
     *
     * @param  string  $fieldType
     * @param  class-string  $columnClass
     * @return void
     */
    public function registerTableColumn(string $fieldType, string $columnClass): void
    {
        $this->tableColumns[$fieldType] = $columnClass;
    }

    /**
     * Get table column class for a field type
     *
     * @param  string  $fieldType
     * @return class-string|null
     */
    public function getTableColumn(string $fieldType): ?string
    {
        return $this->tableColumns[$fieldType] ?? null;
    }

    /**
     * Register a table filter for a field type
     *
     * @param  string  $fieldType
     * @param  class-string  $filterClass
     * @return void
     */
    public function registerTableFilter(string $fieldType, string $filterClass): void
    {
        $this->tableFilters[$fieldType] = $filterClass;
    }

    /**
     * Get table filter class for a field type
     *
     * @param  string  $fieldType
     * @return class-string|null
     */
    public function getTableFilter(string $fieldType): ?string
    {
        return $this->tableFilters[$fieldType] ?? null;
    }

    /**
     * Register an infolist entry for a field type
     *
     * @param  string  $fieldType
     * @param  class-string  $entryClass
     * @return void
     */
    public function registerInfolistEntry(string $fieldType, string $entryClass): void
    {
        $this->infolistEntries[$fieldType] = $entryClass;
    }

    /**
     * Get infolist entry class for a field type
     *
     * @param  string  $fieldType
     * @return class-string|null
     */
    public function getInfolistEntry(string $fieldType): ?string
    {
        return $this->infolistEntries[$fieldType] ?? null;
    }

    /**
     * Check if a field type has a form component registered
     *
     * @param  string  $fieldType
     * @return bool
     */
    public function hasFormComponent(string $fieldType): bool
    {
        return isset($this->formComponents[$fieldType]);
    }

    /**
     * Check if a field type has a table column registered
     *
     * @param  string  $fieldType
     * @return bool
     */
    public function hasTableColumn(string $fieldType): bool
    {
        return isset($this->tableColumns[$fieldType]);
    }

    /**
     * Check if a field type has a table filter registered
     *
     * @param  string  $fieldType
     * @return bool
     */
    public function hasTableFilter(string $fieldType): bool
    {
        return isset($this->tableFilters[$fieldType]);
    }

    /**
     * Check if a field type has an infolist entry registered
     *
     * @param  string  $fieldType
     * @return bool
     */
    public function hasInfolistEntry(string $fieldType): bool
    {
        return isset($this->infolistEntries[$fieldType]);
    }

    /**
     * Get all registered form components
     *
     * @return array<string, class-string>
     */
    public function getAllFormComponents(): array
    {
        return $this->formComponents;
    }

    /**
     * Get all registered table columns
     *
     * @return array<string, class-string>
     */
    public function getAllTableColumns(): array
    {
        return $this->tableColumns;
    }

    /**
     * Get all registered table filters
     *
     * @return array<string, class-string>
     */
    public function getAllTableFilters(): array
    {
        return $this->tableFilters;
    }

    /**
     * Get all registered infolist entries
     *
     * @return array<string, class-string>
     */
    public function getAllInfolistEntries(): array
    {
        return $this->infolistEntries;
    }

    /**
     * Register default components for common field types
     *
     * @return void
     */
    protected function registerDefaultComponents(): void
    {
        // These will be populated when we create the actual component classes
        // For now, we'll use placeholder class names
        
        // Text-based fields
        $this->formComponents['text'] = 'TextInputComponent';
        $this->formComponents['email'] = 'TextInputComponent';
        $this->formComponents['url'] = 'TextInputComponent';
        $this->formComponents['tel'] = 'TextInputComponent';
        $this->formComponents['textarea'] = 'TextareaComponent';
        $this->formComponents['markdown'] = 'MarkdownEditorComponent';
        $this->formComponents['richtext'] = 'RichEditorComponent';
        
        // Numeric fields
        $this->formComponents['number'] = 'NumberComponent';
        $this->formComponents['currency'] = 'CurrencyComponent';
        
        // Date/Time fields
        $this->formComponents['date'] = 'DateComponent';
        $this->formComponents['datetime'] = 'DateTimeComponent';
        $this->formComponents['time'] = 'TimeComponent';
        
        // Selection fields
        $this->formComponents['select'] = 'SelectComponent';
        $this->formComponents['multiselect'] = 'MultiSelectComponent';
        $this->formComponents['radio'] = 'RadioComponent';
        $this->formComponents['checkbox'] = 'CheckboxComponent';
        $this->formComponents['checkboxlist'] = 'CheckboxListComponent';
        $this->formComponents['toggle'] = 'ToggleComponent';
        $this->formComponents['togglebuttons'] = 'ToggleButtonsComponent';
        
        // Special fields
        $this->formComponents['color'] = 'ColorPickerComponent';
        $this->formComponents['file'] = 'FileUploadComponent';
        $this->formComponents['tags'] = 'TagsInputComponent';
        
        // Default table columns
        $this->tableColumns['text'] = 'TextColumn';
        $this->tableColumns['number'] = 'TextColumn';
        $this->tableColumns['currency'] = 'TextColumn';
        $this->tableColumns['date'] = 'DateTimeColumn';
        $this->tableColumns['datetime'] = 'DateTimeColumn';
        $this->tableColumns['boolean'] = 'IconColumn';
        $this->tableColumns['toggle'] = 'IconColumn';
        $this->tableColumns['color'] = 'ColorColumn';
        $this->tableColumns['select'] = 'TextColumn';
        $this->tableColumns['multiselect'] = 'MultiValueColumn';
        $this->tableColumns['tags'] = 'MultiValueColumn';
        
        // Default table filters
        $this->tableFilters['text'] = 'TextFilter';
        $this->tableFilters['select'] = 'SelectFilter';
        $this->tableFilters['multiselect'] = 'SelectFilter';
        $this->tableFilters['boolean'] = 'TernaryFilter';
        $this->tableFilters['toggle'] = 'TernaryFilter';
        $this->tableFilters['date'] = 'DateFilter';
        $this->tableFilters['datetime'] = 'DateFilter';
        
        // Default infolist entries
        $this->infolistEntries['text'] = 'TextEntry';
        $this->infolistEntries['textarea'] = 'TextEntry';
        $this->infolistEntries['number'] = 'TextEntry';
        $this->infolistEntries['currency'] = 'TextEntry';
        $this->infolistEntries['date'] = 'DateTimeEntry';
        $this->infolistEntries['datetime'] = 'DateTimeEntry';
        $this->infolistEntries['boolean'] = 'BooleanEntry';
        $this->infolistEntries['toggle'] = 'BooleanEntry';
        $this->infolistEntries['color'] = 'ColorEntry';
        $this->infolistEntries['select'] = 'SingleValueEntry';
        $this->infolistEntries['multiselect'] = 'MultiValueEntry';
        $this->infolistEntries['tags'] = 'TagsEntry';
        $this->infolistEntries['richtext'] = 'HtmlEntry';
        $this->infolistEntries['markdown'] = 'HtmlEntry';
    }
}