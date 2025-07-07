<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Tables;

use Filament\Tables\Columns\ColorColumn as BaseColorColumn;
use Filament\Tables\Columns\Column as BaseColumn;
use Relaticle\CustomFields\Filament\Integration\Base\AbstractTableColumn;
use Relaticle\CustomFields\Filament\Integration\Concerns\Forms\ConfiguresFieldName;
use Relaticle\CustomFields\Filament\Integration\Concerns\Tables\ConfiguresColumnLabel;
use Relaticle\CustomFields\Filament\Integration\Concerns\Tables\ConfiguresColumnState;
use Relaticle\CustomFields\Filament\Integration\Concerns\Tables\ConfiguresSearchable;
use Relaticle\CustomFields\Models\CustomField;

final class ColorColumn extends AbstractTableColumn
{
    use ConfiguresColumnLabel;
    use ConfiguresColumnState;
    use ConfiguresFieldName;
    use ConfiguresSearchable;

    public function make(CustomField $customField): BaseColumn
    {
        $column = BaseColorColumn::make($this->getFieldName($customField));

        $this->configureLabel($column, $customField);
        $this->configureSearchable($column, $customField);
        $this->configureState($column, $customField);

        return $column;
    }
}
