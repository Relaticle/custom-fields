<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Tables\Columns;

use Filament\Tables\Columns\Column;
use Illuminate\Contracts\Container\BindingResolutionException;
use Relaticle\CustomFields\Integration\AbstractComponentFactory;
use Relaticle\CustomFields\Models\CustomField;

final class FieldColumnFactory extends AbstractComponentFactory
{
    /**
     * @throws BindingResolutionException
     */
    public function create(CustomField $customField): Column
    {
        /** @var ColumnInterface */
        $component = $this->createComponent($customField, 'table_column', ColumnInterface::class);

        return $component->make($customField)
            ->columnSpan($customField->width->getSpanValue());
    }
}
