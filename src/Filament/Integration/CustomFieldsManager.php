<?php

namespace Relaticle\CustomFields\Filament\Integration;

use Relaticle\CustomFields\Filament\Integration\Builders\FormBuilder;
use Relaticle\CustomFields\Filament\Integration\Builders\InfolistBuilder;
use Relaticle\CustomFields\Filament\Integration\Builders\TableBuilder;
use Relaticle\CustomFields\Filament\Integration\Components\Tables\CustomFieldsColumn;
use Relaticle\CustomFields\Filament\Integration\Tables\Filters\CustomFieldsFilter;

class CustomFieldsManager
{
    public function table(): TableBuilder
    {
        return new TableBuilder;
    }

    public function form(): FormBuilder
    {
        return new FormBuilder;
    }

    public function infolist(): InfolistBuilder
    {
        return new InfolistBuilder;
    }

    // Legacy methods for backward compatibility if needed
    public function tableColumns()
    {
        return app(CustomFieldsColumn::class);
    }

    public function tableFilters()
    {
        return app(CustomFieldsFilter::class);
    }
}
