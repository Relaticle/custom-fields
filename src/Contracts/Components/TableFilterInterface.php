<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Contracts\Components;

use Filament\Tables\Filters\BaseFilter;
use Relaticle\CustomFields\Models\CustomField;

interface TableFilterInterface
{
    public function makeTableFilter(CustomField $customField): BaseFilter;
}
