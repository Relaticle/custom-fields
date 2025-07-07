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
 *
 * @see FieldTypeManager
 */
class CustomFields extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CustomFieldsManager::class;
    }

    //    /**
    //     * @param  array<string, array<int | string, string | int> | string> | Closure  $fieldTypes
    //     */
    //    public static function register(array | Closure $fieldTypes): void
    //    {
    //        static::resolved(function (FieldTypeManager $fieldTypeManager) use ($fieldTypes): void {
    //            $fieldTypeManager->register($fieldTypes);
    //        });
    //    }
}
