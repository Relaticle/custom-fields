<?php

namespace Relaticle\CustomFields\Filament\Integration;

use Relaticle\CustomFields\Filament\Integration\Builders\FormBuilder;
use Relaticle\CustomFields\Filament\Integration\Builders\InfolistBuilder;
use Relaticle\CustomFields\Filament\Integration\Builders\TableBuilder;

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
}
