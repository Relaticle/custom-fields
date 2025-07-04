<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Facades;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Relaticle\CustomFields\Data\FieldTypeData;
use Relaticle\CustomFields\FieldTypes\FieldTypeManager;

/**
 * @method static Collection<string, FieldTypeData> toCollection()
 *
 * @see FieldTypeManager
 */
class CustomFieldsType extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FieldTypeManager::class;
    }

    /**
     * @param  array<string, array<int | string, string | int> | string> | Closure  $fieldTypes
     */
    public static function register(array|Closure $fieldTypes): void
    {
        static::resolved(function (FieldTypeManager $fieldTypeManager) use ($fieldTypes): void {
            $fieldTypeManager->register($fieldTypes);
        });
    }
}
