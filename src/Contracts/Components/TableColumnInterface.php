<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Contracts\Components;

use Filament\Tables\Columns\Column;
use Relaticle\CustomFields\Models\CustomField;

interface TableColumnInterface
{
    public function makeTableColumn(CustomField $customField): Column;
}
