<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Base;

use Filament\Tables\Filters\BaseFilter;
use Relaticle\CustomFields\Filament\Integration\Tables\Filters\FilterInterface;
use Relaticle\CustomFields\Models\CustomField;

/**
 * ABOUTME: Abstract base class for table filter components providing common structure.
 * ABOUTME: Standardizes filter creation pattern across different filter types.
 */
abstract class AbstractTableFilter implements FilterInterface
{
    /**
     * Create and configure a table filter.
     */
    abstract public function make(CustomField $customField): BaseFilter;
}
