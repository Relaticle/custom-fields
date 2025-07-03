<?php

namespace Relaticle\CustomFields\FieldTypes;

use Closure;
use Filament\Support\Concerns\EvaluatesClosures;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Contracts\FieldTypeDefinitionInterface;
use Relaticle\CustomFields\Data\FieldTypeData;

class FieldTypeManager
{
    use EvaluatesClosures;

    const DEFAULT_FIELD_TYPES = [
        SelectFieldType::class,
        DateTimeFieldType::class,
    ];

    /**
     * @var array<array<string, array<int, string> | string> | Closure>
     */
    protected array $fieldTypes = [];

    /**
     * @var array<string, array<int, string>>
     */
    protected array $cachedFieldTypes;

    /**
     * @param  array<string, array<int, string> | string> | Closure  $fieldTypes
     */
    public function register(array | Closure $fieldTypes): static
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

        array_unshift($this->fieldTypes, static::DEFAULT_FIELD_TYPES);

        foreach ($this->fieldTypes as $fieldTypes) {
            $fieldTypes = $this->evaluate($fieldTypes);

            foreach ($fieldTypes as $name => $fieldType) {
                $this->cachedFieldTypes[$name] = $fieldType;
            }
        }

        return $this->cachedFieldTypes;
    }

    /**
     * @param string $fieldType
     * @return FieldTypeData
     */
    public function getFieldType(string $fieldType): FieldTypeData
    {
        return $this->toDataCollection()->firstWhere('key', $fieldType);
    }

    public function toDataCollection(): Collection
    {
        $fieldTypes = [];

        foreach ($this->getFieldTypes() as $fieldTypeClass) {
            /** @var FieldTypeDefinitionInterface $fieldType */
            $fieldType = new $fieldTypeClass;

            $fieldTypes[] = new FieldTypeData(
                key: $fieldType->getKey(),
                label: $fieldType->getLabel(),
                icon: $fieldType->getIcon(),
                dataType: $fieldType->getDataType(),
                formComponent: $fieldType->getFormComponentClass(),
                tableComponent: $fieldType->getTableColumnClass(),
                infolistComponent: $fieldType->getInfolistEntryClass(),
            );
        }

        return collect($fieldTypes)->sortBy('label')->values();
    }
}
