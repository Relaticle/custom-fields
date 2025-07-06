<?php

namespace Relaticle\CustomFields\Facades;

use Illuminate\Support\Facades\Facade;
use Relaticle\CustomFields\Filament\Integration\Builders\FormBuilder;
use Relaticle\CustomFields\Filament\Integration\Builders\InfolistBuilder;
use Relaticle\CustomFields\Filament\Integration\Builders\TableBuilder;
use Relaticle\CustomFields\Filament\Integration\CustomFieldsManager;

/**
 * @method static FormBuilder form()
 * @method static TableBuilder table()
 * @method static InfolistBuilder infolist()
 * @method static FormBuilder forms()
 * @method static TableBuilder tables()
 * @method static InfolistBuilder infolists()
 *
 * @see CustomFieldsManager
 */
class CustomFields extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CustomFieldsManager::class;
    }

}
