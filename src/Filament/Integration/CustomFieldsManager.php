<?php

namespace Relaticle\CustomFields\Filament\Integration;

use Relaticle\CustomFields\Filament\Integration\Components\Tables\CustomFieldsColumn;
use Relaticle\CustomFields\Filament\Integration\Forms\CustomFieldsForm;
use Relaticle\CustomFields\Filament\Integration\Infolists\CustomFieldsInfolists;
use Relaticle\CustomFields\Filament\Integration\Tables\Filters\CustomFieldsFilter;

class CustomFieldsManager
{
    public function tableColumns()
    {
        return app(CustomFieldsColumn::class);
    }

    public function tableFilters()
    {
        return app(CustomFieldsFilter::class);
    }

    public function formComponent()
    {
        return app(CustomFieldsForm::class);
    }

    public function infolistEntries()
    {
        return app(CustomFieldsInfolists::class);
    }
}
