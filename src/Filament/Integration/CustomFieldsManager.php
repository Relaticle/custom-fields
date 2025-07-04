<?php

namespace Relaticle\CustomFields\Filament\Integration;

use Relaticle\CustomFields\Filament\Integration\Forms\CustomFieldsForm;
use Relaticle\CustomFields\Filament\Integration\Tables\Columns\CustomFieldsColumn;

class CustomFieldsManager
{
    public function formComponent()
    {
        return app(CustomFieldsForm::class);
    }

    public function tableColumns($model)
    {
        $instance = app($model);
        return CustomFieldsColumn::all($instance);
    }
}
