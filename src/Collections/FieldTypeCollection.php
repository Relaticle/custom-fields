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
        return $this->filter(fn (FieldTypeData $fieldType): bool => $fieldType->dataType->isChoiceField());
    }

    public function onlySearchables(): static
    {
        return $this->filter(fn (FieldTypeData $fieldType): bool => $fieldType->searchable);
    }

    public function onlySortables(): static
    {
        return $this->filter(fn (FieldTypeData $fieldType): bool => $fieldType->sortable);
    }

    public function onlyFilterables(): static
    {
        return $this->filter(fn (FieldTypeData $fieldType): bool => $fieldType->filterable);
    }

    public function whereDataType(FieldDataType $dataType): static
    {
        return $this->filter(fn (FieldTypeData $fieldType): bool => $fieldType->dataType === $dataType);
    }
}
