<?php

namespace Relaticle\CustomFields\Models\Concerns;

use Relaticle\CustomFields\Enums\FieldDataType;

trait HasFieldType
{
    public function isChoiceField(): bool
    {
        return $this->typeData->dataType->isChoiceField();
    }

    public function isMultiChoiceField(): bool
    {
        return $this->typeData->dataType->isMultiChoiceField();
    }

    public function isDateField(): bool
    {
        return $this->typeData->dataType === FieldDataType::DATE;
    }
}
