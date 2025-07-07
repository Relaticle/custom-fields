<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Tables;

use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn as BaseIconColumn;
use Relaticle\CustomFields\Filament\Integration\Base\AbstractTableColumn;
use Relaticle\CustomFields\Filament\Integration\Concerns\Forms\ConfiguresFieldName;
use Relaticle\CustomFields\Filament\Integration\Concerns\Tables\ConfiguresColumnLabel;
use Relaticle\CustomFields\Filament\Integration\Concerns\Tables\ConfiguresSortable;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\CustomField;

class IconColumn extends AbstractTableColumn
{
    use ConfiguresColumnLabel;
    use ConfiguresFieldName;
    use ConfiguresSortable;

    public function make(CustomField $customField): Column
    {
        $column = BaseIconColumn::make($this->getFieldName($customField))->boolean();

        $this->configureLabel($column, $customField);
        $this->configureSortable($column, $customField);

        $column
            ->searchable(false)
            ->getStateUsing(fn (HasCustomFields $record): mixed => $record->getCustomFieldValue($customField) ?? false);

        return $column;
    }
}
