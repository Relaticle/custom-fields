<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Tables;

use Closure;
use Filament\Tables\Columns\Column as BaseColumn;
use Filament\Tables\Columns\TextColumn as BaseTextColumn;
use Relaticle\CustomFields\Filament\Integration\Base\AbstractTableColumn;
use Relaticle\CustomFields\Filament\Integration\Concerns\Forms\ConfiguresFieldName;
use Relaticle\CustomFields\Filament\Integration\Concerns\Tables\ConfiguresColumnLabel;
use Relaticle\CustomFields\Filament\Integration\Concerns\Tables\ConfiguresSearchable;
use Relaticle\CustomFields\Filament\Integration\Concerns\Tables\ConfiguresSortable;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Support\FieldTypeUtils;

class DateTimeColumn extends AbstractTableColumn
{
    use ConfiguresColumnLabel;
    use ConfiguresFieldName;
    use ConfiguresSearchable;
    use ConfiguresSortable;

    protected ?Closure $locale = null;

    public function make(CustomField $customField): BaseColumn
    {
        $column = BaseTextColumn::make($this->getFieldName($customField));

        $this->configureLabel($column, $customField);
        $this->configureSortable($column, $customField);
        $this->configureSearchable($column, $customField);

        $column->getStateUsing(function ($record) use ($customField) {
            $value = $record->getCustomFieldValue($customField);

            if ($this->locale instanceof Closure) {
                $value = $this->locale->call($this, $value);
            }

            if ($value && $customField->type === 'date_time') {
                return $value->format(FieldTypeUtils::getDateTimeFormat());
            }

            if ($value && $customField->type === 'date') {
                return $value->format(FieldTypeUtils::getDateFormat());
            }

            return $value;
        });

        return $column;
    }

    /**
     * @return $this
     */
    public function localize(Closure $locale): static
    {
        $this->locale = $locale;

        return $this;
    }
}
