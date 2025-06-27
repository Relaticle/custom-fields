<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Relaticle\CustomFields\Contracts\FieldTypeDefinitionInterface;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Integration\Forms\Components\CheckboxComponent;
use Relaticle\CustomFields\Integration\Forms\Components\CheckboxListComponent;
use Relaticle\CustomFields\Integration\Forms\Components\ColorPickerComponent;
use Relaticle\CustomFields\Integration\Forms\Components\CurrencyComponent;
use Relaticle\CustomFields\Integration\Forms\Components\DateComponent;
use Relaticle\CustomFields\Integration\Forms\Components\DateTimeComponent;
use Relaticle\CustomFields\Integration\Forms\Components\FieldComponentInterface;
use Relaticle\CustomFields\Integration\Forms\Components\LinkComponent;
use Relaticle\CustomFields\Integration\Forms\Components\MarkdownEditorComponent;
use Relaticle\CustomFields\Integration\Forms\Components\MultiSelectComponent;
use Relaticle\CustomFields\Integration\Forms\Components\NumberComponent;
use Relaticle\CustomFields\Integration\Forms\Components\RadioComponent;
use Relaticle\CustomFields\Integration\Forms\Components\RichEditorComponent;
use Relaticle\CustomFields\Integration\Forms\Components\SelectComponent;
use Relaticle\CustomFields\Integration\Forms\Components\TagsInputComponent;
use Relaticle\CustomFields\Integration\Forms\Components\TextareaFieldComponent;
use Relaticle\CustomFields\Integration\Forms\Components\TextInputComponent;
use Relaticle\CustomFields\Integration\Forms\Components\ToggleButtonsComponent;
use Relaticle\CustomFields\Integration\Forms\Components\ToggleComponent;
use Relaticle\CustomFields\Integration\Infolists\FieldInfolistsComponentInterface;
use Relaticle\CustomFields\Integration\Infolists\Fields\BooleanEntry;
use Relaticle\CustomFields\Integration\Infolists\Fields\ColorEntry;
use Relaticle\CustomFields\Integration\Infolists\Fields\HtmlEntry;
use Relaticle\CustomFields\Integration\Infolists\Fields\MultiValueEntry;
use Relaticle\CustomFields\Integration\Infolists\Fields\SingleValueEntry;
use Relaticle\CustomFields\Integration\Infolists\Fields\TextEntry;
use Relaticle\CustomFields\Integration\Tables\Columns\ColorColumn;
use Relaticle\CustomFields\Integration\Tables\Columns\ColumnInterface;
use Relaticle\CustomFields\Integration\Tables\Columns\IconColumn;
use Relaticle\CustomFields\Integration\Tables\Columns\MultiValueColumn;
use Relaticle\CustomFields\Integration\Tables\Columns\SingleValueColumn;
use Relaticle\CustomFields\Integration\Tables\Columns\TextColumn;
use RuntimeException;

/**
 * Central registry for all field types (built-in and custom).
 */
final class FieldTypeRegistryService
{
    /**
     * @var array<string, FieldTypeDefinitionInterface>
     */
    private array $customFieldTypes = [];

    /**
     * @var array<string, array{label: string, icon: string, category: string, validation_rules: array<string>, form_component: string, table_column: string, infolist_entry: string, searchable: bool, filterable: bool, encryptable: bool, priority: int}>|null
     */
    private ?array $cachedOptions = null;

    private bool $discoveredFieldTypes = false;

    public function __construct(
        private readonly FieldTypeDiscoveryService $discoveryService
    ) {}

    /**
     * Register a custom field type.
     */
    public function register(FieldTypeDefinitionInterface $fieldType): void
    {
        $key = $fieldType->getKey();

        // Validate that the key doesn't conflict with built-in types
        if ($this->isBuiltInFieldType($key)) {
            throw new InvalidArgumentException("Field type key '{$key}' conflicts with built-in field type.");
        }

        // Validate that component classes exist and implement correct interfaces
        $this->validateComponentClasses($fieldType);

        $this->customFieldTypes[$key] = $fieldType;
        $this->clearCache();
    }

    /**
     * Get all registered field types (built-in + custom).
     *
     * @return Collection<string, array{label: string, icon: string, category: string, validation_rules: array<string>, form_component: string, table_column: string, infolist_entry: string, searchable: bool, filterable: bool, encryptable: bool, priority: int}>
     */
    public function getAllFieldTypes(): Collection
    {
        $this->ensureFieldTypesDiscovered();

        if ($this->cachedOptions === null) {
            $this->buildCache();
        }

        return collect($this->cachedOptions)
            ->sortBy('priority')
            ->sortBy('label');
    }

    /**
     * Get field type options formatted for select dropdowns.
     *
     * @return Collection<int, array{label: string, value: string, icon: string}>
     */
    public function getFieldTypeOptions(): Collection
    {
        return $this->getAllFieldTypes()
            ->map(fn (array $config, string $key): array => [
                'label' => $config['label'],
                'value' => $key,
                'icon' => $config['icon'],
            ])
            ->values();
    }

    /**
     * Get a specific field type configuration.
     *
     * @return array{label: string, icon: string, category: string, validation_rules: array<int, string>, form_component: string, table_column: string, infolist_entry: string, searchable: bool, filterable: bool, encryptable: bool, priority: int}|null
     */
    public function getFieldType(string $key): ?array
    {
        $allTypes = $this->getAllFieldTypes();

        return $allTypes->get($key);
    }

    /**
     * Check if a field type exists (built-in or custom).
     */
    public function hasFieldType(string $key): bool
    {
        if ($this->isBuiltInFieldType($key)) {
            return true;
        }

        return isset($this->customFieldTypes[$key]);
    }

    /**
     * Get custom field types only.
     *
     * @return Collection<string, FieldTypeDefinitionInterface>
     */
    public function getCustomFieldTypes(): Collection
    {
        return collect($this->customFieldTypes);
    }

    /**
     * Check if a field type is a built-in type.
     */
    public function isBuiltInFieldType(string $key): bool
    {
        return collect(CustomFieldType::cases())
            ->pluck('value')
            ->contains($key);
    }

    /**
     * Get searchable field types.
     *
     * @return Collection<int, string>
     */
    /** @return Collection<int, string> */
    public function getSearchableFieldTypes(): Collection
    {
        return $this->getAllFieldTypes()
            ->filter(fn (array $config): bool => $config['searchable'])
            ->keys();
    }

    /**
     * Get filterable field types.
     *
     * @return Collection<int, string>
     */
    /** @return Collection<int, string> */
    public function getFilterableFieldTypes(): Collection
    {
        return $this->getAllFieldTypes()
            ->filter(fn (array $config): bool => $config['filterable'])
            ->keys();
    }

    /**
     * Get encryptable field types.
     *
     * @return Collection<int, string>
     */
    /** @return Collection<int, string> */
    public function getEncryptableFieldTypes(): Collection
    {
        return $this->getAllFieldTypes()
            ->filter(fn (array $config): bool => $config['encryptable'])
            ->keys();
    }

    /**
     * Ensure custom field types have been discovered.
     */
    private function ensureFieldTypesDiscovered(): void
    {
        if ($this->discoveredFieldTypes) {
            return;
        }

        $config = config('custom-fields.field_type_discovery', []);

        if (! ($config['enabled'] ?? true)) {
            $this->discoveredFieldTypes = true;

            return;
        }

        $cacheKey = 'custom-fields.discovered-field-types';
        $cacheDuration = $config['cache_duration'] ?? 60;
        $cacheEnabled = $config['cache'] ?? true;

        if ($cacheEnabled && Cache::has($cacheKey)) {
            $cachedFieldTypes = Cache::get($cacheKey, []);
            foreach ($cachedFieldTypes as $className) {
                if (class_exists($className)) {
                    $fieldType = new $className;
                    if ($fieldType instanceof FieldTypeDefinitionInterface) {
                        $this->register($fieldType);
                    }
                }
            }
        } else {
            $discoveredFieldTypes = $this->discoveryService->discoverFromConfig();

            if ($cacheEnabled) {
                $classNames = $discoveredFieldTypes->map(fn ($fieldType): string => $fieldType::class)->toArray();
                Cache::put($cacheKey, $classNames, now()->addMinutes($cacheDuration));
            }

            foreach ($discoveredFieldTypes as $fieldType) {
                $this->register($fieldType);
            }
        }

        $this->discoveredFieldTypes = true;
    }

    /**
     * Build the complete field type cache.
     */
    private function buildCache(): void
    {
        $this->cachedOptions = [];

        // Add built-in field types
        foreach (CustomFieldType::cases() as $type) {
            $this->cachedOptions[$type->value] = [
                'label' => $type->getLabel(),
                'icon' => $type->getIcon(),
                'category' => $type->getCategory()->value,
                'validation_rules' => array_map(
                    fn ($rule) => $rule->value,
                    $type->allowedValidationRules()
                ),
                'form_component' => $this->getBuiltInFormComponent($type),
                'table_column' => $this->getBuiltInTableColumn($type),
                'infolist_entry' => $this->getBuiltInInfolistEntry($type),
                'searchable' => CustomFieldType::searchables()->contains($type),
                'filterable' => CustomFieldType::filterable()->contains($type),
                'encryptable' => CustomFieldType::encryptables()->contains($type),
                'priority' => 100, // Built-in types have default priority
            ];
        }

        // Add custom field types
        foreach ($this->customFieldTypes as $key => $fieldType) {
            $this->cachedOptions[$key] = [
                'label' => $fieldType->getLabel(),
                'icon' => $fieldType->getIcon(),
                'category' => $fieldType->getCategory()->value,
                'validation_rules' => array_map(
                    fn ($rule) => $rule->value,
                    $fieldType->getAllowedValidationRules()
                ),
                'form_component' => $fieldType->getFormComponentClass(),
                'table_column' => $fieldType->getTableColumnClass(),
                'infolist_entry' => $fieldType->getInfolistEntryClass(),
                'searchable' => $fieldType->isSearchable(),
                'filterable' => $fieldType->isFilterable(),
                'encryptable' => $fieldType->isEncryptable(),
                'priority' => $fieldType->getPriority(),
            ];
        }
    }

    /**
     * Clear the cache when field types are registered.
     */
    private function clearCache(): void
    {
        $this->cachedOptions = null;
    }

    /**
     * Validate that component classes exist and implement correct interfaces.
     */
    private function validateComponentClasses(FieldTypeDefinitionInterface $fieldType): void
    {
        $formComponent = $fieldType->getFormComponentClass();
        $tableColumn = $fieldType->getTableColumnClass();
        $infolistEntry = $fieldType->getInfolistEntryClass();

        if (! class_exists($formComponent)) {
            throw new RuntimeException("Form component class '{$formComponent}' does not exist.");
        }

        if (! class_exists($tableColumn)) {
            throw new RuntimeException("Table column class '{$tableColumn}' does not exist.");
        }

        if (! class_exists($infolistEntry)) {
            throw new RuntimeException("Infolist entry class '{$infolistEntry}' does not exist.");
        }

        // Check if classes implement the correct interfaces
        if (! is_subclass_of($formComponent, FieldComponentInterface::class)) {
            throw new RuntimeException("Form component class '{$formComponent}' must implement FieldComponentInterface.");
        }

        if (! is_subclass_of($tableColumn, ColumnInterface::class)) {
            throw new RuntimeException("Table column class '{$tableColumn}' must implement ColumnInterface.");
        }

        if (! is_subclass_of($infolistEntry, FieldInfolistsComponentInterface::class)) {
            throw new RuntimeException("Infolist entry class '{$infolistEntry}' must implement FieldInfolistsComponentInterface.");
        }
    }

    /**
     * Get the built-in form component class for a field type.
     */
    private function getBuiltInFormComponent(CustomFieldType $type): string
    {
        $map = [
            CustomFieldType::TEXT->value => TextInputComponent::class,
            CustomFieldType::NUMBER->value => NumberComponent::class,
            CustomFieldType::CHECKBOX->value => CheckboxComponent::class,
            CustomFieldType::CHECKBOX_LIST->value => CheckboxListComponent::class,
            CustomFieldType::RICH_EDITOR->value => RichEditorComponent::class,
            CustomFieldType::MARKDOWN_EDITOR->value => MarkdownEditorComponent::class,
            CustomFieldType::TOGGLE_BUTTONS->value => ToggleButtonsComponent::class,
            CustomFieldType::TAGS_INPUT->value => TagsInputComponent::class,
            CustomFieldType::LINK->value => LinkComponent::class,
            CustomFieldType::COLOR_PICKER->value => ColorPickerComponent::class,
            CustomFieldType::TEXTAREA->value => TextareaFieldComponent::class,
            CustomFieldType::CURRENCY->value => CurrencyComponent::class,
            CustomFieldType::DATE->value => DateComponent::class,
            CustomFieldType::DATE_TIME->value => DateTimeComponent::class,
            CustomFieldType::TOGGLE->value => ToggleComponent::class,
            CustomFieldType::RADIO->value => RadioComponent::class,
            CustomFieldType::SELECT->value => SelectComponent::class,
            CustomFieldType::MULTI_SELECT->value => MultiSelectComponent::class,
        ];

        return $map[$type->value] ?? throw new RuntimeException("No form component mapped for type: {$type->value}");
    }

    /**
     * Get the built-in table column class for a field type.
     */
    private function getBuiltInTableColumn(CustomFieldType $type): string
    {
        $map = [
            CustomFieldType::TEXT->value => TextColumn::class,
            CustomFieldType::TEXTAREA->value => TextColumn::class,
            CustomFieldType::NUMBER->value => TextColumn::class,
            CustomFieldType::CURRENCY->value => TextColumn::class,
            CustomFieldType::DATE->value => TextColumn::class,
            CustomFieldType::DATE_TIME->value => TextColumn::class,
            CustomFieldType::LINK->value => TextColumn::class,
            CustomFieldType::CHECKBOX->value => IconColumn::class,
            CustomFieldType::TOGGLE->value => IconColumn::class,
            CustomFieldType::COLOR_PICKER->value => ColorColumn::class,
            CustomFieldType::SELECT->value => SingleValueColumn::class,
            CustomFieldType::RADIO->value => TextColumn::class,
            CustomFieldType::MULTI_SELECT->value => MultiValueColumn::class,
            CustomFieldType::CHECKBOX_LIST->value => MultiValueColumn::class,
            CustomFieldType::TAGS_INPUT->value => MultiValueColumn::class,
            CustomFieldType::TOGGLE_BUTTONS->value => MultiValueColumn::class,
            CustomFieldType::RICH_EDITOR->value => 'Relaticle\CustomFields\Integration\Tables\Columns\HtmlColumn',
            CustomFieldType::MARKDOWN_EDITOR->value => 'Relaticle\CustomFields\Integration\Tables\Columns\HtmlColumn',
        ];

        return $map[$type->value] ?? throw new RuntimeException("No table column mapped for type: {$type->value}");
    }

    /**
     * Get the built-in infolist entry class for a field type.
     */
    private function getBuiltInInfolistEntry(CustomFieldType $type): string
    {
        $map = [
            CustomFieldType::TEXT->value => TextEntry::class,
            CustomFieldType::TEXTAREA->value => TextEntry::class,
            CustomFieldType::NUMBER->value => TextEntry::class,
            CustomFieldType::CURRENCY->value => TextEntry::class,
            CustomFieldType::DATE->value => TextEntry::class,
            CustomFieldType::DATE_TIME->value => TextEntry::class,
            CustomFieldType::LINK->value => TextEntry::class,
            CustomFieldType::CHECKBOX->value => BooleanEntry::class,
            CustomFieldType::TOGGLE->value => BooleanEntry::class,
            CustomFieldType::COLOR_PICKER->value => ColorEntry::class,
            CustomFieldType::SELECT->value => SingleValueEntry::class,
            CustomFieldType::RADIO->value => TextEntry::class,
            CustomFieldType::MULTI_SELECT->value => MultiValueEntry::class,
            CustomFieldType::CHECKBOX_LIST->value => MultiValueEntry::class,
            CustomFieldType::TAGS_INPUT->value => MultiValueEntry::class,
            CustomFieldType::TOGGLE_BUTTONS->value => MultiValueEntry::class,
            CustomFieldType::RICH_EDITOR->value => HtmlEntry::class,
            CustomFieldType::MARKDOWN_EDITOR->value => HtmlEntry::class,
        ];

        return $map[$type->value] ?? throw new RuntimeException("No infolist entry mapped for type: {$type->value}");
    }
}
