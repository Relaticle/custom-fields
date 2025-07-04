<?php

namespace Relaticle\CustomFields\Models\Concerns;

trait HasFieldType
{
    public function isChoiceable(): bool
    {
        return $this->field_type->dataType->isChoiceable();
    }
}