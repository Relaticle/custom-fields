<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Tables\Filters;

use Filament\Tables\Filters\BaseFilter;
use Illuminate\Contracts\Container\BindingResolutionException;
use Relaticle\CustomFields\Filament\Integration\AbstractComponentFactory;
use Relaticle\CustomFields\Models\CustomField;

/**
 * @extends AbstractComponentFactory<FilterInterface, BaseFilter>
 */
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
