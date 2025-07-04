<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Tables\Filters;

use Filament\Tables\Filters\BaseFilter;
use Relaticle\CustomFields\Models\CustomField;

interface FilterInterface
{
    public function make(CustomField $customField): BaseFilter;
}
