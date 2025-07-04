<?php

namespace Relaticle\CustomFields\Filament\Integration;

use Relaticle\CustomFields\Filament\Integration\Forms\CustomFieldsForm;

class CustomFieldsManager
{
    public function makeFormComponent()
    {
        return app(CustomFieldsForm::class);
    }
}
