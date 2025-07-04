<?php

namespace Relaticle\CustomFields\Facades;

use Filament\Schemas\Components\Component;
use Illuminate\Support\Facades\Facade;
use Relaticle\CustomFields\Filament\Integration\CustomFieldsManager;

/**
 * @method static Component makeFormComponent()
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
