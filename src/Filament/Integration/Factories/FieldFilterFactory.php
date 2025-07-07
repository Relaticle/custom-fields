<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Factories;

use Illuminate\Contracts\Container\BindingResolutionException;
use Relaticle\CustomFields\Models\CustomField;

final class FieldFilterFactory
{
    /**
     * @throws BindingResolutionException
     */
    public function create(CustomField $customField)
    {
        $component = app($customField->typeData->tableFilter);

        return $component->make($customField)->columnSpan($customField->width->getSpanValue());
    }
}
