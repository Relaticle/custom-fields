<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\FieldTypes;

use Closure;
use Filament\Support\Concerns\EvaluatesClosures;
use Relaticle\CustomFields\Collections\FieldTypeCollection;
use Relaticle\CustomFields\Contracts\FieldTypeDefinitionInterface;
use Relaticle\CustomFields\Data\FieldTypeData;

final class FieldTypeManager
{
    use EvaluatesClosures;

    const array DEFAULT_FIELD_TYPES = [
        TextFieldType::class,
        NumberFieldType::class,
        LinkFieldType::class,
        TextareaFieldType::class,
        CheckboxFieldType::class,
        CheckboxListFieldType::class,
        RadioFieldType::class,
        RichEditorFieldType::class,
        MarkdownEditorFieldType::class,
        TagsInputFieldType::class,
        ColorPickerFieldType::class,
        ToggleFieldType::class,
        ToggleButtonsFieldType::class,
        CurrencyFieldType::class,
        DateFieldType::class,
        DateTimeFieldType::class,
        SelectFieldType::class,
        MultiSelectFieldType::class,
    ];

    /**
     * @var array<array<string, array<int, string> | string> | Closure>
     */
    private array $fieldTypes = [];

    /**
     * @var array<string, array<int, string>>
     */
    private array $cachedFieldTypes;

    /**
     * @var array<string, FieldTypeDefinitionInterface>
     */
    private array $cachedInstances = [];

    /**
     * @param  array<string, array<int, string> | string> | Closure  $fieldTypes
     */
    public function register(array|Closure $fieldTypes): static
    {
        $this->fieldTypes[] = $fieldTypes;

        return $this;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getFieldTypes(): array
    {
        if (isset($this->cachedFieldTypes)) {
            return $this->cachedFieldTypes;
        }

        array_unshift($this->fieldTypes, self::DEFAULT_FIELD_TYPES);

        foreach ($this->fieldTypes as $fieldTypes) {
            $fieldTypes = $this->evaluate($fieldTypes);

            foreach ($fieldTypes as $name => $fieldType) {
                $this->cachedFieldTypes[$name] = $fieldType;
            }
        }

        return $this->cachedFieldTypes;
    }

    public function getFieldType(string $fieldType): FieldTypeData
    {
        return $this->toCollection()->firstWhere('key', $fieldType);
    }

    /**
     * Get a field type instance by key.
     */
    public function getFieldTypeInstance(string $key): ?FieldTypeDefinitionInterface
    {
        if (isset($this->cachedInstances[$key])) {
            return $this->cachedInstances[$key];
        }

        // Build collection if needed (which also caches instances)
        $this->toCollection();

        return $this->cachedInstances[$key] ?? null;
    }

    /**
     * Check if a field type implements a specific interface.
     */
    public function fieldTypeImplements(string $key, string $interface): bool
    {
        $instance = $this->getFieldTypeInstance($key);

        return $instance instanceof FieldTypeDefinitionInterface && $instance instanceof $interface;
    }

    public function toCollection(): FieldTypeCollection
    {
        $fieldTypes = [];

        foreach ($this->getFieldTypes() as $fieldTypeClass) {
            /** @var FieldTypeDefinitionInterface $fieldType */
            $fieldType = new $fieldTypeClass;

            $fieldTypes[$fieldType->getKey()] = new FieldTypeData(
                key: $fieldType->getKey(),
                label: $fieldType->getLabel(),
                icon: $fieldType->getIcon(),
                dataType: $fieldType->getDataType(),
                tableColumn: $fieldType->getTableColumnClass(),
                tableFilter: $fieldType->getTableFilterClass(),
                formComponent: $fieldType->getFormComponentClass(),
                infolistEntry: $fieldType->getInfolistEntryClass(),
                searchable: $fieldType->isSearchable(),
                sortable: $fieldType->isSortable(),
                filterable: $fieldType->isFilterable(),
                validationRules: $fieldType->allowedValidationRules()
            );

            // Cache the instance
            $this->cachedInstances[$fieldType->getKey()] = $fieldType;
        }

        return FieldTypeCollection::make($fieldTypes)->sortBy('label');
    }
}
