<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Collections;

use Illuminate\Support\Collection;
use Relaticle\CustomFields\Data\FieldTypeData;
use Relaticle\CustomFields\Enums\FieldDataType;

final class FieldTypeCollection extends Collection
{
    public function onlyChoiceables(): static
    {
        return $this->filter(fn (FieldTypeData $fieldType) => $fieldType->dataType->isChoiceField());
    }

    public function onlySearchables(): static
    {
        return $this->filter(fn (FieldTypeData $fieldType) => $fieldType->searchable);
    }

    public function onlySortables(): static
    {
        return $this->filter(fn (FieldTypeData $fieldType) => $fieldType->sortable);
    }

    public function onlyFilterables(): static
    {
        return $this->filter(fn (FieldTypeData $fieldType) => $fieldType->filterable);
    }

    public function whereDataType(FieldDataType $dataType): static
    {
        return $this->filter(fn (FieldTypeData $fieldType) => $fieldType->dataType === $dataType);
    }
}