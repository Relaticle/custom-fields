<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Tables;

use Filament\Tables\Columns\Column as BaseColumn;
use Filament\Tables\Columns\TextColumn as BaseTextColumn;
use Relaticle\CustomFields\Filament\Integration\Base\AbstractTableColumn;
use Relaticle\CustomFields\Filament\Integration\Concerns\Forms\ConfiguresFieldName;
use Relaticle\CustomFields\Filament\Integration\Concerns\Shared\ConfiguresBadgeColors;
use Relaticle\CustomFields\Filament\Integration\Concerns\Tables\ConfiguresColumnLabel;
use Relaticle\CustomFields\Filament\Integration\Concerns\Tables\ConfiguresSortable;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\ValueResolver\LookupSingleValueResolver;

final readonly class SingleValueColumn extends AbstractTableColumn
{
    use ConfiguresBadgeColors;
    use ConfiguresColumnLabel;
    use ConfiguresFieldName;
    use ConfiguresSortable;

    public function __construct(public LookupSingleValueResolver $valueResolver) {}

    public function make(CustomField $customField): BaseColumn
    {
        $column = BaseTextColumn::make($this->getFieldName($customField));

        $this->configureLabel($column, $customField);
        $this->configureSortable($column, $customField);

        $column
            ->getStateUsing(fn (HasCustomFields $record): string => $this->valueResolver->resolve($record, $customField))
            ->searchable(false);

        return $this->applyBadgeColorsIfEnabled($column, $customField);
    }
}
