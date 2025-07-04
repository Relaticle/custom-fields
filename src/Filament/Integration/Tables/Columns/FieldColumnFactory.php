<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Tables\Columns;

use Filament\Tables\Columns\Column;
use Illuminate\Contracts\Container\BindingResolutionException;
use Relaticle\CustomFields\Filament\Integration\AbstractComponentFactory;
use Relaticle\CustomFields\Models\CustomField;

/**
 * @extends AbstractComponentFactory<ColumnInterface, Column>
 */
final class FieldColumnFactory
{
    /**
     * @throws BindingResolutionException
     */
    public function create(CustomField $customField): Column
    {
        $component = app($customField->typeData->tableColumn);
        return $component->make($customField)->columnSpan($customField->width->getSpanValue());
    }
}
