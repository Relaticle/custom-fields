<?php

namespace Relaticle\CustomFields\Models\Concerns;

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
}