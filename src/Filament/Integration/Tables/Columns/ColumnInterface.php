<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Tables\Columns;

use Filament\Tables\Columns\Column;
use Relaticle\CustomFields\Models\CustomField;

interface ColumnInterface
{
    public function make(CustomField $customField): Column;
}
