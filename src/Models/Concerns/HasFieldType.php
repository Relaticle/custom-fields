<?php

namespace Relaticle\CustomFields\Models\Concerns;

trait HasFieldType
{
    public function isChoiceField(): bool
    {
        return $this->typeData->isChoiceField();
    }
}