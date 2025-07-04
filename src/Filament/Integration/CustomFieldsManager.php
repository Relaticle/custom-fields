<?php

namespace Relaticle\CustomFields\Filament\Integration;

use Relaticle\CustomFields\Filament\Integration\Forms\CustomFieldsForm;
use Relaticle\CustomFields\Filament\Integration\Tables\Columns\CustomFieldsColumn;
use Relaticle\CustomFields\Filament\Integration\Tables\Filters\CustomFieldsFilter;

class CustomFieldsManager
{
    public function formComponent()
    {
        return app(CustomFieldsForm::class);
    }

    public function tableColumns()
    {
        return app(CustomFieldsColumn::class);
    }

    public function tableFilters()
    {
        return app(CustomFieldsFilter::class);
    }
}
